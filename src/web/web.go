package main

import (
	"archive/zip"
	"bytes"
	"bufio"
	"fmt"
	"path/filepath"
	"io"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"os/exec"
	"os/user"
	"strconv"
	"time"
	"strings"
	"syscall"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promhttp"
)

var tmp_prefix string
var debug uint

type User struct {
	Id		uint
	Name		string
	Uid		int
	Gid		int
}

type Metrics struct {
	ResponseTime	prometheus.Histogram
	SpawnTime	prometheus.Histogram
	NumSuccess	prometheus.Counter
	NumIntError	prometheus.Counter
	NumTimeout	prometheus.Counter
	QueueLen	prometheus.Gauge
}

type MotherProcess struct {
	Cmd				*exec.Cmd
	Input				*io.PipeWriter
	Output				*io.PipeReader
}

type ChildProcess struct {
	User				*User
	Input				*os.File
	Output				*bufio.Reader
	Outfile				*os.File
	TempDir				string
}

func debugf(format string, a ...interface{}) {
	if debug > 0 {
		log.Printf(format, a...)
	}
}

func new_mother_proc(binpath string, libs []string) (*MotherProcess, error) {
	cmd := exec.Command(binpath)
	cmd.Stderr = os.Stderr

	in_pipe_w, in_pipe_r := io.Pipe()
	cmd.Stdin = in_pipe_w

	out_pipe_w, out_pipe_r := io.Pipe()
	cmd.Stdout = out_pipe_r

	err := cmd.Start()
	if err != nil {
		return nil, fmt.Errorf("cannot start process: %s", err)
	}

	for _, lib := range libs {
		if lib == "" {
			continue
		}
		// this should be fine since this string is given at startup and therefore trusted
		lib = strings.ReplaceAll(lib, "\\", "\\\\")
		lib = strings.ReplaceAll(lib, "\"", "\\\"")
		debugf("Debug: load(\"%s\")$\n", lib)
		_, err = fmt.Fprintf(in_pipe_r, "load(\"%s\")$\n", lib)
		if err != nil {
			return nil, fmt.Errorf("cannot send command to process: %s", err)
		}
		debugf("Debug: Loaded file")
	}

	// start the lisp forking loop, which calls a functino from a c library
	// that loops over stdin input and forks a new process for each line
	_, err = fmt.Fprint(in_pipe_r, ":lisp (maxima-fork:forking-loop)\n")
	if err != nil {
		return nil, fmt.Errorf("cannot send command to process: %s", err)
	}
	debugf("Debug: Attempting to find out readiness of mother process")
	i := 0
	for {
		shortbuf := make([]byte,1)
		n, err := out_pipe_w.Read(shortbuf)
		if err != nil {
			return nil, fmt.Errorf("cannot read readiness text from process: %s", err)
		}
		i += 1
		if debug >= 2 {
			fmt.Fprintf(os.Stderr, "%c", shortbuf[0])
		}
		if n != 1 {
			return nil, fmt.Errorf("unexpected short read")
		}
		if shortbuf[0] == '\x02' {
			break;
		}
	}
	return &MotherProcess{
		Cmd: cmd,
		Input: in_pipe_r,
		Output: out_pipe_w,
	}, nil
}


func (p *MotherProcess) spawn_new(user *User) (*ChildProcess, float64, error) {
	start := time.Now()
	tmp_dir, err := ioutil.TempDir(tmp_prefix, "maxima-plot-")
	if err != nil {
		return nil, 0.0, fmt.Errorf("unable to create temp dir: %s", err)
	}
	// right permission for folders
	err = os.Chown(tmp_dir, user.Uid, user.Gid)
	if err != nil {
		return nil, 0.0, fmt.Errorf("not able to change tempdir permission: %s", err)
	}

	// create named pipe for process stdout
	pipe_name_out := filepath.Clean(filepath.Join(tmp_dir, "outpipe"))

	err = syscall.Mkfifo(pipe_name_out, 0600)
	if err != nil {
		return nil, 0.0, fmt.Errorf("could not create named pipe in temp folder: %s", err)
	}

	// create named pipe for process stdin
	pipe_name_in := filepath.Clean(filepath.Join(tmp_dir, "inpipe"))

	err = syscall.Mkfifo(pipe_name_in, 0600)
	if err != nil {
		return nil, 0.0, fmt.Errorf("could not create named pipe in temp folder: %s", err)
	}

	_, err = fmt.Fprintf(p.Input, "%d%s\n", user.Id, tmp_dir)
	if err != nil {
		return nil, 0.0, fmt.Errorf("unable to communicate with process: %s", err)
	}

	debugf("Debug: Opening pipes to child")
	// note: open outpipe before inpipe to avoid deadlock
	out, err := os.OpenFile(pipe_name_out, os.O_RDONLY, os.ModeNamedPipe)
	if err != nil {
		return nil, 0.0, fmt.Errorf("could not open temp dir outpipe: %s", err)
	}

	in, err := os.OpenFile(pipe_name_in, os.O_WRONLY, os.ModeNamedPipe)
	if err != nil {
		return nil, 0.0, fmt.Errorf("could not open temp dir inpipe: %s", err)
	}

	out.SetReadDeadline(time.Now().Add(10*time.Second))
	bufout := bufio.NewReader(out)
	_, err = fmt.Fscanf(bufout, "\nT")
	if err != nil {
		return nil, 0.0, fmt.Errorf("not able to find end marker: %s", err)
	}
	total := time.Since(start)
	debugf("Debug: child took %s for startup", total)
	return &ChildProcess {
		User: user,
		Input: in,
		Output: bufout,
		Outfile: out,
		TempDir: tmp_dir,
	}, float64(total.Microseconds())/1000, nil
}

// takes a child process and evaluates a maxima command in it, while timing out if timeout is reached
func (p *ChildProcess) eval_command(command string, timeout uint64) (*bytes.Buffer, float64, error) {
	start := time.Now()
	in_err := make(chan error, 1)
	// write to stdin in separate goroutine to prevent deadlocks
	go func() {
		p.Input.SetWriteDeadline(time.Now().Add(time.Duration(timeout)*time.Millisecond))
		_, err := io.Copy(p.Input, strings.NewReader(command))
		p.Input.Close()
		in_err<-err
	}()
	var outbuf bytes.Buffer
	// read from stdout
	p.Outfile.SetReadDeadline(time.Now().Add(time.Duration(timeout)*time.Millisecond))
	_, err := io.Copy(&outbuf, p.Output)
	p.Outfile.Close()
	input_err := <-in_err
	if input_err != nil {
		return nil, 0.0, input_err
	}
	if err != nil {
		return nil, 0.0, err
	}
	total := time.Since(start)
	debugf("Debug: child took %s for evaluation", total)
	return &outbuf, float64(total.Microseconds())/1000, nil
}

func write_500(w http.ResponseWriter) {
	w.WriteHeader(http.StatusInternalServerError)
	fmt.Fprint(w, "500 - internal server error\n")
}

// kills all processes of user and remove temporary directories
func process_cleanup(user *User, user_queue chan<- *User, tmp_dir string) {
	defer os.RemoveAll(tmp_dir)
	defer func() {user_queue <- user}()

	// TODO: replace with cgroups-v2 based freeze solution once docker support for cgroups2 lands
	procs, err := ioutil.ReadDir("/proc")
	if err != nil {
		return
	}
	for _, dir := range procs {
		pid, err := strconv.Atoi(dir.Name())
		if err != nil {
			continue
		}
		stat, ok := dir.Sys().(*syscall.Stat_t)
		if !ok {
			continue
		}
		if int(stat.Uid) != user.Uid {
			continue
		}
		syscall.Kill(pid, syscall.SIGKILL)
	}
	debugf("Debug: Process %d cleand up", user.Id)
}

func handler(w http.ResponseWriter, r *http.Request, queue <-chan *ChildProcess, user_queue chan<- *User, metrics *Metrics) {
	health := r.FormValue("health") == "1"
	if r.Method == "GET" && r.FormValue("input") == "" && !health {
		hostname, _ := os.Hostname()
		fmt.Fprintf(w, "Hostname: %s, version: 1.1.2\n", hostname)
		return
	}
	// the maxima input to be evaluated
	input := r.FormValue("input")
	// template value for ploturl
	ploturl := r.FormValue("ploturlbase")
	var timeout uint64
	var err error
	if health {
		input = "print(\"healthcheck successful\");"
		timeout = 1000
		debugf("Debug: doing healthcheck")
	} else {
		timeout, err = strconv.ParseUint(r.FormValue("timeout"), 10, 64)
		if err != nil {
			w.WriteHeader(http.StatusBadRequest)
			fmt.Fprint(w, "400 - bad request (invalid timeout)\n")
			log.Printf("Warn: Invalid timeout: %s", err)
			return
		}
	}

	if timeout > 30000 {
		log.Printf("Warn: timeout %d was out of range range, reduced to 30000", timeout)
		timeout = 30000
	}
	// put the temporary directories into maxima variables
	var real_input string
	if ploturl != "!ploturl!" {
		real_input = fmt.Sprintf("URL_BASE: \"%s\"$\n%s", ploturl, input)
	} else {
		real_input = input
	}
	proc := <-queue
	metrics.QueueLen.Dec()
	user := proc.User
	debugf("Debug: input (%d): %s", user.Id, input)
	defer process_cleanup(user, user_queue, proc.TempDir)
	proc_out := make(chan struct {buf *bytes.Buffer; time float64; err error}, 1)
	go func() {
		out, tim, err := proc.eval_command(real_input, timeout)
		// grrr why doesn't go support tuples first-class!?
		proc_out <- struct {buf *bytes.Buffer; time float64; err error}{out, tim, err}
	}()
	select {
	case outstr := <-proc_out:
		outbuf := outstr.buf
		tim := outstr.time
		err := outstr.err
		if err != nil {
			write_500(w)
			metrics.NumIntError.Inc()
			log.Printf("Error: Communicating with maxima failed: %s", err)
			return
		}
		if health {
			if bytes.Contains(outbuf.Bytes(), []byte("healthcheck successful")) {
				w.Header().Set("Content-Type", "text/plain;charset=UTF-8")
				outbuf.WriteTo(w)
				debugf("Healthcheck passed")
				// note: we don't update metrics here since they would get
				// too polluted by the healthchecks
				return
			} else {
				write_500(w)
				metrics.NumIntError.Inc()
				log.Printf("Error: Healthcheck did not pass")
				return
			}
		}
		output_dir := filepath.Clean(filepath.Join(proc.TempDir, "output")) + "/"
		// if there are any files inside the output dir, we give back a zip file containing all output
		// files and the command output inside a file named OUTPUT
		plots_output, err := ioutil.ReadDir(output_dir)
		if err != nil {
			// just return text if directory could not be read and assume no plots were generated
			w.Header().Set("Content-Type", "text/plain;charset=UTF-8")
			outbuf.WriteTo(w)
			log.Printf("Warn: could not read temp directory of maxima process: %s", err)
			metrics.ResponseTime.Observe(tim)
			metrics.NumSuccess.Inc()
		}
		// if there are no files produced, just give back the output directly
		if len(plots_output) == 0 {
			debugf("Debug: output (%d) is text, len %d", user.Id, outbuf.Len())
			w.Header().Set("Content-Type", "text/plain;charset=UTF-8")
			outbuf.WriteTo(w)
		} else {
			debugf("Debug: output (%d) is zip, OUTPUT len %d", user.Id, outbuf.Len())
			w.Header().Set("Content-Type", "application/zip;charset=UTF-8")
			zipfile := zip.NewWriter(w)
			out, err := zipfile.Create("OUTPUT")
			if err != nil {
				write_500(w)
				metrics.NumIntError.Inc()
				log.Printf("Error: Could not add OUTPUT to zip archive: %s", err)
				return
			}
			outbuf.WriteTo(out)
			// loop over all plots in the output directory and put them into the zip
			// that is to be returned
			for _, file := range plots_output {
				ffilein, err := os.Open(filepath.Join(output_dir, file.Name()))
				defer ffilein.Close()
				if err != nil {
					write_500(w)
					metrics.NumIntError.Inc()
					log.Printf("Error: could not open plot %s: %s", file.Name(), err)
					return
				}
				fzipput, err := zipfile.Create("/" + file.Name())
				if err != nil {
					write_500(w)
					metrics.NumIntError.Inc()
					log.Printf("Error: could not add file %s to zip archive: %s", file.Name(), err)
					return
				}
				io.Copy(fzipput, ffilein)
			}
			zipfile.Close()
		}
		metrics.ResponseTime.Observe(tim)
		metrics.NumSuccess.Inc()
	case <-time.After(time.Duration(timeout) * time.Millisecond):
		w.WriteHeader(http.StatusRequestedRangeNotSatisfiable)
		fmt.Fprint(w, "416 - timeout\n")
		log.Printf("Warn: Process %s had timeout", user.Name)
		metrics.ResponseTime.Observe(float64(timeout))
		metrics.NumTimeout.Inc()
	}
}

func generate_maximas(binpath string, libs []string, queue chan<- *ChildProcess, user_queue <-chan *User, metrics *Metrics) {
	mother_proc := make(chan *MotherProcess, 0)
	go func () {
		mother, err := new_mother_proc(binpath, libs)
		if err != nil {
			log.Fatalf("Fatal: Could not start mother process: %s", err)
		}
		mother_proc <- mother
	}()
	var mother *MotherProcess
	select {
	case mom := <-mother_proc:
		mother = mom
	case <- time.After(10*time.Second):
		log.Fatal("Fatal: Could not start the mother process, timed out")
	}
	fails := 0
	for {
		user := <-user_queue
		new_proc, time, err := mother.spawn_new(user)
		if err != nil {
			fails += 1
			log.Printf("Error: Could not spawn child process - fail %d, %s", fails, err)
			if fails == 3 {
				log.Fatal("Fatal: Failed to spawn child process 3 times in a row, giving up")
			}
		} else {
			fails = 0
		}
		debugf("Debug: Spawning process with id %d", user.Id)
		metrics.QueueLen.Inc()
		metrics.SpawnTime.Observe(time)
		queue <- new_proc
		debugf("Debug: Spawned process with id %d", user.Id)
	}
}

func get_env_number_positive(varname string, def uint) (uint, error) {
	value, exists := os.LookupEnv(varname)
	if !exists {
		return def, nil
	}
	number, err := strconv.ParseUint(value, 10, 32)
	if err != nil {
		return 0, err
	}
	return uint(number), nil
}

func main() {
	// register/initialize various prometheus metrics
	metrics := Metrics {
		ResponseTime: prometheus.NewHistogram(prometheus.HistogramOpts{
			Name:	"maxima_response_time",
			Help:	"Response time of maxima processes",
			Buckets:prometheus.ExponentialBuckets(1.19, 1.5, 25)}),
		SpawnTime: prometheus.NewHistogram(prometheus.HistogramOpts{
			Name:	"maxima_spawn_time",
			Help:	"Spawn time of maxima child",
			Buckets:prometheus.LinearBuckets(1.0, 1.0, 20)}),
		NumSuccess: prometheus.NewCounter(prometheus.CounterOpts{
			Name:	"maxima_success_response",
			Help:	"Count of successful responses"}),
		NumIntError: prometheus.NewCounter(prometheus.CounterOpts{
			Name:	"maxima_500_response",
			Help:	"Count of 500 responses"}),
		NumTimeout: prometheus.NewCounter(prometheus.CounterOpts{
			Name:	"maxima_timeout_response",
			Help:	"Count of timeouts"}),
		QueueLen: prometheus.NewGauge(prometheus.GaugeOpts{
			Name:	"maxima_queue_len",
			Help:	"Number of maxima processes waiting in queue"})}
	metrics.QueueLen.Set(0)
	prometheus.MustRegister(metrics.ResponseTime)
	prometheus.MustRegister(metrics.SpawnTime)
	prometheus.MustRegister(metrics.NumSuccess)
	prometheus.MustRegister(metrics.NumIntError)
	prometheus.MustRegister(metrics.NumTimeout)
	prometheus.MustRegister(metrics.QueueLen)

	if len(os.Args) != 2 {
		log.Fatal("Fatal: wrong cli-argument usage: web [path to maxima executable]")
	}
	// number of maxima users
	user_number, err := get_env_number_positive("GOEMAXIMA_NUSER", 32)
	log.Printf("Info: Maximum number of processes is %d", user_number)
	if err != nil {
		log.Fatal("Fatal: GOEMAXIMA_NUSER contains invalid number");
	}
	// length of queue of ready maxima processes (-1)
	queue_len, err := get_env_number_positive("GOEMAXIMA_QUEUE_LEN", 3)
	if err != nil {
		log.Fatal("Fatal: GOEMAXIMA_QUEUE_LEN contains invalid number");
	}
	log.Printf("Info: Ready process queue length is %d", queue_len)
	// enable debug messages
	debug, err = get_env_number_positive("GOEMAXIMA_DEBUG", 0)
	if err != nil {
		log.Fatal("Fatal: GOEMAXIMA_DEBUG contains invalid number");
	}
	// where to store temp files (plots, named pipes)
	// should preferrably be tmpfs since it needs to be fast
	tmp_prefix = os.Getenv("GOEMAXIMA_TEMP_DIR")
	if tmp_prefix == "" {
		tmp_prefix = "/tmp/maxima"
	} else if !strings.HasPrefix(tmp_prefix, "/") {
		log.Fatal("Fatal: GOEMAXIMA_TEMP_DIR must be an absolute path");
	}
	err = os.MkdirAll(tmp_prefix, 0711)
	if err != nil {
		log.Fatalf("Fatal: Cannot create %s: %s", tmp_prefix, err)
	}

	// queue of ready maxima processes
	queue := make(chan *ChildProcess, queue_len)
	// queue of available user ids
	user_queue := make(chan *User, user_number)

	// look up all the users
	for i := (uint)(1); i <= user_number; i++ {
		user_name := fmt.Sprintf("maxima-%d", i)
		user, err := user.Lookup(user_name)
		if err != nil {
			log.Fatalf("Fatal: Could not look up user with id %d: %s", i, err)
		}
		uid, err := strconv.Atoi(user.Uid)
		if err != nil {
			log.Fatalf("Fatal: Cannot parse uid %s as number: %s", user.Uid, err)
		}
		gid, err := strconv.Atoi(user.Gid)
		if err != nil {
			log.Fatalf("Fatal: Cannot parse uid %s as number: %s", user.Gid, err)
		}
		user_queue <- &User {
			Id: i,
			Name: user_name,
			Uid: uid,
			Gid: gid,
		}
	}

	libs := append(strings.Split(os.Getenv("GOEMAXIMA_EXTRA_PACKAGES"), ":"),  os.Getenv("GOEMAXIMA_LIB_PATH"))
	// spawn maxima processes in separate goroutine
	go generate_maximas(os.Args[1], libs, queue, user_queue, &metrics)

	http.Handle("/metrics", promhttp.Handler())
	handler := func (w http.ResponseWriter, r *http.Request) {
			handler(w, r, queue, user_queue, &metrics)
	}
	http.HandleFunc("/maxima", handler)
	http.HandleFunc("/maxima/", handler)
	http.HandleFunc("/goemaxima", handler)
	http.HandleFunc("/goemaxima/", handler)
	log.Print("Info: goe handler started")
	err = http.ListenAndServe(":8080", nil)
	log.Printf("Fatal: http handler closed unexpectedly, %s", err)
	return
}
