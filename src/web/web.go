package main

import (
	"archive/zip"
	"bufio"
	"bytes"
	"errors"
	"fmt"
	"io"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"os/exec"
	"os/user"
	"path/filepath"
	"strconv"
	"strings"
	"syscall"
	"time"

	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promhttp"
)

var tmp_prefix string
var debug uint
var privilege_drop_channel chan<- ExecutionInfo

const MAXIMA_SPAWN_TIMEOUT = time.Duration(10) * time.Second
const HEALTHCHECK_CMD = `
	f: concat(maxima_tempdir, "/test")$
	stringout(f, concat("healthcheck ", "successful"))$
	printfile(f)$`

type User struct {
	Id   uint
	Name string
	Uid  int
	Gid  int
}

func (user *User) go_execute_as(f func(error)) {
	uid16 := uint16(user.Uid)
	gid16 := uint16(user.Gid)
	privilege_drop_channel <- ExecutionInfo{
		Uid: uid16,
		Gid: gid16,
		F:   f,
	}
}

func (user *User) sync_execute_as(timeout time.Duration, f func(error) error) error {
	err_chan := make(chan error)
	user.go_execute_as(func(err error) {
		err_chan <- f(err)
	})
	select {
	case result := <-err_chan:
		return result
	case <-time.After(timeout):
		return fmt.Errorf("timed out executing function with lowered privileges")
	}
}

type Metrics struct {
	ResponseTime prometheus.Histogram
	SpawnTime    prometheus.Histogram
	NumSuccess   prometheus.Counter
	NumIntError  prometheus.Counter
	NumTimeout   prometheus.Counter
	QueueLen     prometheus.Gauge
}

type MotherProcess struct {
	Cmd    *exec.Cmd
	Input  *io.PipeWriter
	Output *io.PipeReader
}

type ChildProcess struct {
	User    *User
	Input   *os.File
	Output  *bufio.Reader
	Outfile *os.File
	TempDir string
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
		shortbuf := make([]byte, 1)
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
			break
		}
	}
	return &MotherProcess{
		Cmd:    cmd,
		Input:  in_pipe_r,
		Output: out_pipe_w,
	}, nil
}

func (p *MotherProcess) spawn_new(user *User) (*ChildProcess, float64, error) {
	start := time.Now()
	var result ChildProcess
	err := user.sync_execute_as(MAXIMA_SPAWN_TIMEOUT, func(err error) error {
		if err != nil {
			return err
		}
		tmp_dir, err := ioutil.TempDir(tmp_prefix, "maxima-plot-")
		if err != nil {
			return fmt.Errorf("unable to create temp dir: %s", err)
		}

		err = os.Chmod(tmp_dir, 0755)
		if err != nil {
			return fmt.Errorf("unable to change permissions of temp dir: %s", err)
		}

		// create named pipe for process stdout
		pipe_name_out := filepath.Clean(filepath.Join(tmp_dir, "outpipe"))

		err = syscall.Mkfifo(pipe_name_out, 0600)
		if err != nil {
			return fmt.Errorf("could not create named pipe in temp folder: %s", err)
		}

		// create named pipe for process stdin
		pipe_name_in := filepath.Clean(filepath.Join(tmp_dir, "inpipe"))

		err = syscall.Mkfifo(pipe_name_in, 0600)
		if err != nil {
			return fmt.Errorf("could not create named pipe in temp folder: %s", err)
		}

		_, err = fmt.Fprintf(p.Input, "%d%s\n", user.Id, tmp_dir)
		if err != nil {
			return fmt.Errorf("unable to communicate with process: %s", err)
		}

		debugf("Debug: Opening pipes to child")
		// note: open outpipe before inpipe to avoid deadlock
		out, err := os.OpenFile(pipe_name_out, os.O_RDONLY, os.ModeNamedPipe)
		if err != nil {
			return fmt.Errorf("could not open temp dir outpipe: %s", err)
		}

		in, err := os.OpenFile(pipe_name_in, os.O_WRONLY, os.ModeNamedPipe)
		if err != nil {
			return fmt.Errorf("could not open temp dir inpipe: %s", err)
		}

		result.User = user
		result.Input = in
		result.Outfile = out
		result.TempDir = tmp_dir
		return nil
	})
	if err != nil {
		return nil, 0.0, fmt.Errorf("unable to start child process: %s", err)
	}

	debugf("Debug: Attempting to read from child")
	err = result.Outfile.SetReadDeadline(time.Now().Add(MAXIMA_SPAWN_TIMEOUT))
	if err != nil {
		return nil, 0.0, fmt.Errorf("unable to set read deadline: %s", err)
	}
	bufout := bufio.NewReader(result.Outfile)
	_, err = fmt.Fscanf(bufout, "\nT")
	if err != nil {
		return nil, 0.0, fmt.Errorf("not able to find end marker: %s", err)
	}
	result.Output = bufout

	total := time.Since(start)
	total_ms := float64(total.Microseconds()) / 1000
	debugf("Debug: child took %s for startup", total)
	return &result, total_ms, nil
}

type MaximaResponse struct {
	Response *bytes.Buffer
	Time     float64
	Err      error
}

// takes a child process and evaluates a maxima command in it, while timing out if timeout is reached
func (p *ChildProcess) eval_command(command string, timeout uint64) MaximaResponse {
	start := time.Now()
	in_err := make(chan error, 1)
	// write to stdin in separate goroutine to prevent deadlocks
	go func() {
		err := p.Input.SetWriteDeadline(time.Now().Add(time.Duration(timeout+10) * time.Millisecond))
		if err != nil {
			in_err <- err
			return
		}
		_, err = io.Copy(p.Input, strings.NewReader(command))
		p.Input.Close()
		in_err <- err
	}()
	var outbuf bytes.Buffer
	// read from stdout
	err := p.Outfile.SetReadDeadline(time.Now().Add(time.Duration(timeout+10) * time.Millisecond))
	if err != nil {
		return MaximaResponse{nil, 0.0, err}
	}
	_, err = io.Copy(&outbuf, p.Output)
	p.Outfile.Close()
	input_err := <-in_err
	if input_err != nil {
		return MaximaResponse{nil, 0.0, input_err}
	}
	if err != nil {
		return MaximaResponse{nil, 0.0, err}
	}
	total := time.Since(start)
	debugf("Debug: child took %s for evaluation", total)
	return MaximaResponse{&outbuf, float64(total.Microseconds()) / 1000, nil}
}

// kills all processes of user and remove temporary directories
func process_cleanup(user *User, user_queue chan<- *User, tmp_dir string) {
	user.go_execute_as(func(err error) {
		if err != nil {
			log.Fatalf("Fatal: Error dropping privilege for process cleanup: %s", err)
			return
		}
		// we have an real uid of goemaxima-nobody in this context and an effective id
		// of the target user maxima-%d
		//
		// this allows us to kill all processes of the user:
		// processes we are allowed to kill with our real uid:
		// 		just ourselves as long as goemaxima-nobody contains no other processes
		//      but kill -1 doesn't kill the process itself on linux
		// processes we are allowed to kill with our effective uid:
		//      all the processes that the target user maxima-%d contains
		// any CAP_KILL capability is also not effective because we changed our effective uid
		// to be non-zero
		//
		// note that we do not walk proc since that would not allow atomic killing of processes
		// which could allow someone to avoid getting killed by fork()ing very fast.
		// Also, since we are in a docker container, using cgroups does not work well right now
		//
		// We ignore the error because we may not actually kill any processes
		_ = syscall.Kill(-1, syscall.SIGKILL)
		err = os.RemoveAll(tmp_dir)
		if err != nil {
			log.Printf("Warn: could not clean up directories of child: %s", err)
		}
		user_queue <- user
		debugf("Debug: Process %d cleaned up", user.Id)
	})
}

type MaximaRequest struct {
	Health    bool
	Timeout   uint64
	Input     string
	Ploturl   string
	Proc      *ChildProcess
	Metrics   *Metrics
	User      *User
	UserQueue chan<- *User
	W         http.ResponseWriter
}

func (req *MaximaRequest) log_with_input(format string, a ...interface{}) {
	msg := fmt.Sprintf(format, a...)
	log.Printf("%s - input: `%s`, timeout: %d", msg, req.Input, req.Timeout)
}

func (req *MaximaRequest) write_timeout_err() {
	req.W.WriteHeader(http.StatusRequestedRangeNotSatisfiable)
	fmt.Fprint(req.W, "416 - timeout\n")
	req.log_with_input("Warn: Process %s had timeout", req.User.Name)
	req.Metrics.ResponseTime.Observe(float64(req.Timeout))
	req.Metrics.NumTimeout.Inc()
}

func (req *MaximaRequest) respond_with_log_error(format string, a ...interface{}) {
	req.W.WriteHeader(http.StatusInternalServerError)
	fmt.Fprint(req.W, "500 - internal server error\n")
	req.Metrics.NumIntError.Inc()
	req.log_with_input(format, a...)
}

func (req *MaximaRequest) WriteResponseWithoutPlots(response MaximaResponse) {
	req.W.Header().Set("Content-Type", "text/plain;charset=UTF-8")
	_, err := response.Response.WriteTo(req.W)
	if err != nil {
		req.respond_with_log_error("could not write response body")
	}
	req.Metrics.ResponseTime.Observe(response.Time)
	req.Metrics.NumSuccess.Inc()
}

func (req *MaximaRequest) WriteResponseWithPlots(response MaximaResponse, output_dir string, plots_output []os.FileInfo) {
	debugf("Debug: output (%d) is zip, OUTPUT len %d", req.User.Id, response.Response.Len())
	req.W.Header().Set("Content-Type", "application/zip;charset=UTF-8")
	zipfile := zip.NewWriter(req.W)

	out, err := zipfile.Create("OUTPUT")
	if err != nil {
		req.respond_with_log_error("Error: Could not add OUTPUT to zip archive: %s", err)
		return
	}

	_, err = response.Response.WriteTo(out)
	if err != nil {
		req.respond_with_log_error("could not write response body")
	}

	// loop over all plots in the output directory and put them into the zip
	// that is to be returned
	for _, file := range plots_output {
		ffilein, err := os.Open(filepath.Join(output_dir, file.Name()))
		if err != nil {
			req.respond_with_log_error("Error: could not open plot %s: %s", file.Name(), err)
			return
		}
		defer ffilein.Close()
		fzipput, err := zipfile.Create("/" + file.Name())
		if err != nil {
			req.respond_with_log_error("Error: could not add file %s to zip archive: %s", file.Name(), err)
			return
		}
		_, err = io.Copy(fzipput, ffilein)
		if err != nil {
			req.respond_with_log_error("could not write response body")
		}
	}
	zipfile.Close()
	req.Metrics.ResponseTime.Observe(response.Time)
	req.Metrics.NumSuccess.Inc()
}

func (req *MaximaRequest) WriteResponse(response MaximaResponse) {
	err := response.Err
	if errors.Is(err, os.ErrDeadlineExceeded) {
		debugf("Timeout with I/O pipe timeout")
		req.write_timeout_err()
		return
	}
	if err != nil {
		req.respond_with_log_error("Error: Communicating with maxima failed: %s", err)
		return
	}
	if req.Health {
		if bytes.Contains(response.Response.Bytes(), []byte("healthcheck successful")) {
			req.W.Header().Set("Content-Type", "text/plain;charset=UTF-8")
			_, err = response.Response.WriteTo(req.W)
			if err != nil {
				req.respond_with_log_error("could not write response body")
			}

			debugf("Healthcheck passed")
			// note: we don't update metrics here since they would get
			// too polluted by the healthchecks
			return
		} else {
			req.respond_with_log_error("Error: Healthcheck did not pass, output: %s", response.Response)
			return
		}
	}
	output_dir := filepath.Clean(filepath.Join(req.Proc.TempDir, "output")) + "/"
	// if there are any files inside the output dir, we give back a zip file containing all output
	// files and the command output inside a file named OUTPUT
	plots_output, err := ioutil.ReadDir(output_dir)
	if err != nil {
		// just return text if directory could not be read and assume no plots were generated
		req.log_with_input("Warn: could not read temp directory of maxima process: %s", err)
		req.WriteResponseWithoutPlots(response)
		return
	}
	// if there are no files produced, just give back the output directly
	if len(plots_output) == 0 {
		req.WriteResponseWithoutPlots(response)
	} else {
		req.WriteResponseWithPlots(response, output_dir, plots_output)
	}
}

func (req *MaximaRequest) Respond() {
	debugf("Debug: input (%d): %s", req.User.Id, req.Input)

	defer process_cleanup(req.User, req.UserQueue, req.Proc.TempDir)

	proc_out := make(chan MaximaResponse, 1)
	go func() {
		proc_out <- req.Proc.eval_command(req.Input, req.Timeout)
	}()

	select {
	case outstr := <-proc_out:
		req.WriteResponse(outstr)
		return
	case <-time.After(time.Duration(req.Timeout+10) * time.Millisecond):
		debugf("Timeout with internal timer")
		req.write_timeout_err()
		return
	}
}

func handler(w http.ResponseWriter, r *http.Request, queue <-chan *ChildProcess, user_queue chan<- *User, metrics *Metrics) {
	health := r.FormValue("health") == "1"
	if r.Method == "GET" && r.FormValue("input") == "" && !health {
		hostname, _ := os.Hostname()
		fmt.Fprintf(w, "Hostname: %s, version: 1.1.6\n", hostname)
		return
	}
	// the maxima input to be evaluated
	input := r.FormValue("input")
	// template value for ploturl
	ploturl := r.FormValue("ploturlbase")
	var timeout uint64
	var err error
	if health {
		input = HEALTHCHECK_CMD
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

	request := MaximaRequest{
		Health:    health,
		Timeout:   timeout,
		Input:     real_input,
		Ploturl:   ploturl,
		Proc:      proc,
		Metrics:   metrics,
		User:      user,
		UserQueue: user_queue,
		W:         w,
	}

	request.Respond()
}

func generate_maximas(binpath string, libs []string, queue chan<- *ChildProcess, user_queue <-chan *User, metrics *Metrics) {
	mother_proc := make(chan *MotherProcess)
	go func() {
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
	case <-time.After(MAXIMA_SPAWN_TIMEOUT):
		log.Fatal("Fatal: Could not start the mother process, timed out")
	}
	fails := 0
	for {
		user := <-user_queue
		new_proc, tim, err := mother.spawn_new(user)
		if err != nil {
			fails += 1
			log.Printf("Error: Could not spawn child process - fail %d, %s", fails, err)
			if fails == 3 {
				log.Fatal("Fatal: Failed to spawn child process 3 times in a row, giving up")
			}
			continue
		} else {
			fails = 0
		}
		debugf("Debug: Spawning process with id %d", user.Id)
		metrics.QueueLen.Inc()
		metrics.SpawnTime.Observe(tim)
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

func init_metrics() Metrics {
	metrics := Metrics{
		ResponseTime: prometheus.NewHistogram(prometheus.HistogramOpts{
			Name:    "maxima_response_time",
			Help:    "Response time of maxima processes",
			Buckets: prometheus.ExponentialBuckets(1.19, 1.5, 25)}),
		SpawnTime: prometheus.NewHistogram(prometheus.HistogramOpts{
			Name:    "maxima_spawn_time",
			Help:    "Spawn time of maxima child",
			Buckets: prometheus.LinearBuckets(1.0, 1.0, 20)}),
		NumSuccess: prometheus.NewCounter(prometheus.CounterOpts{
			Name: "maxima_success_response",
			Help: "Count of successful responses"}),
		NumIntError: prometheus.NewCounter(prometheus.CounterOpts{
			Name: "maxima_500_response",
			Help: "Count of 500 responses"}),
		NumTimeout: prometheus.NewCounter(prometheus.CounterOpts{
			Name: "maxima_timeout_response",
			Help: "Count of timeouts"}),
		QueueLen: prometheus.NewGauge(prometheus.GaugeOpts{
			Name: "maxima_queue_len",
			Help: "Number of maxima processes waiting in queue"})}
	metrics.QueueLen.Set(0)
	prometheus.MustRegister(metrics.ResponseTime)
	prometheus.MustRegister(metrics.SpawnTime)
	prometheus.MustRegister(metrics.NumSuccess)
	prometheus.MustRegister(metrics.NumIntError)
	prometheus.MustRegister(metrics.NumTimeout)
	prometheus.MustRegister(metrics.QueueLen)
	return metrics
}

func main() {
	metrics := init_metrics()

	if len(os.Args) != 2 {
		log.Fatal("Fatal: wrong cli-argument usage: web [path to maxima executable]")
	}
	// number of maxima users
	user_number, err := get_env_number_positive("GOEMAXIMA_NUSER", 32)
	log.Printf("Info: Maximum number of processes is %d", user_number)
	if err != nil {
		log.Fatal("Fatal: GOEMAXIMA_NUSER contains invalid number")
	}
	// length of queue of ready maxima processes (-1)
	queue_len, err := get_env_number_positive("GOEMAXIMA_QUEUE_LEN", 3)
	if err != nil {
		log.Fatal("Fatal: GOEMAXIMA_QUEUE_LEN contains invalid number")
	}
	log.Printf("Info: Ready process queue length is %d", queue_len)
	// enable debug messages
	debug, err = get_env_number_positive("GOEMAXIMA_DEBUG", 0)
	if err != nil {
		log.Fatal("Fatal: GOEMAXIMA_DEBUG contains invalid number")
	}
	// where to store temp files (plots, named pipes)
	// should preferrably be tmpfs since it needs to be fast
	tmp_prefix = os.Getenv("GOEMAXIMA_TEMP_DIR")
	if tmp_prefix == "" {
		tmp_prefix = "/tmp/maxima"
	} else if !strings.HasPrefix(tmp_prefix, "/") {
		log.Fatal("Fatal: GOEMAXIMA_TEMP_DIR must be an absolute path")
	}
	err = os.MkdirAll(tmp_prefix, 0711)
	if err != nil {
		log.Fatalf("Fatal: Cannot create %s: %s", tmp_prefix, err)
	}

	err = os.Chmod(tmp_prefix, 01777)
	if err != nil {
		log.Fatalf("Fatal: Cannot set permission on %s: %s", tmp_prefix, err)
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
		user_queue <- &User{
			Id:   i,
			Name: user_name,
			Uid:  uid,
			Gid:  gid,
		}
	}

	drop_queue := make(chan ExecutionInfo, user_number)
	err = StartDropper(drop_queue)
	if err != nil {
		log.Fatalf("Fatal: cannot run privilege dropper: %s", err)
	}
	// run two droppers for more parallelism
	err = StartDropper(drop_queue)
	if err != nil {
		log.Fatalf("Fatal: cannot run privilege dropper: %s", err)
	}
	privilege_drop_channel = drop_queue

	libs := append(strings.Split(os.Getenv("GOEMAXIMA_EXTRA_PACKAGES"), ":"), os.Getenv("GOEMAXIMA_LIB_PATH"))
	// spawn maxima processes in separate goroutine
	go generate_maximas(os.Args[1], libs, queue, user_queue, &metrics)

	http.Handle("/metrics", promhttp.Handler())
	handler := func(w http.ResponseWriter, r *http.Request) {
		handler(w, r, queue, user_queue, &metrics)
	}
	http.HandleFunc("/maxima", handler)
	http.HandleFunc("/maxima/", handler)
	http.HandleFunc("/goemaxima", handler)
	http.HandleFunc("/goemaxima/", handler)
	log.Print("Info: goe handler started")
	err = http.ListenAndServe(":8080", nil)
	log.Printf("Fatal: http handler closed unexpectedly, %s", err)
}
