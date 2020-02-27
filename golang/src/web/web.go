package main

import (
    "bytes"
    "fmt"
    "log"
    "net/http"
    "os"
    "io"
    "os/exec"
    "io/ioutil"
    "path/filepath"
    "archive/zip"
)

func handler(w http.ResponseWriter, r *http.Request) {
    ploturl := r.PostFormValue("ploturlbase")

    tmp_dir, err := ioutil.TempDir("", "max-plot-")
    if err != nil {
        log.Fatal(err)
    }
    work_dir := filepath.Clean(filepath.Join(tmp_dir, "work")) + "/"
    output_dir := filepath.Clean(filepath.Join(tmp_dir, "output")) + "/"

    err = os.Mkdir(work_dir, 0777)
    if err != nil {
        log.Fatal(err)
    }
    err = os.Mkdir(output_dir, 0777)
    if err != nil {
        log.Fatal(err)
    }

    plot_vars := fmt.Sprintf("maxima_tempdir: \"%s\"$ IMAGE_DIR: \"%s\"$ URL_BASE: \"%s\"$", work_dir, output_dir, ploturl)
    command := plot_vars+"load(\"/opt/maximapool/2017121800/maximalocal.mac\");" + r.PostFormValue("input") + "quit();"
    cmd := exec.Command("/opt/maximapool/2017121800/maxima-optimised", "-r", command)
    cmd.Dir = "/opt/maximapool/2017121800/tmp"
    output, err := cmd.Output()
    if err != nil {
        log.Fatal(err)
    }
    outbuf := bytes.NewBuffer(output)
    plots_output, err := ioutil.ReadDir(output_dir)
    if err != nil {
        log.Fatal(err)
    }
    if len(plots_output) == 0 {
        log.Print("Text")
        outbuf.WriteTo(w)
    } else {
        log.Print("Plot")
        w.Header().Set("Content-Type", "application/zip;charset=UTF-8")
	zipfile := zip.NewWriter(w)
	out, err := zipfile.Create("OUTPUT")
	if err != nil {
            log.Fatal(err)
	}
	outbuf.WriteTo(out)
	for _, file := range plots_output {
            ffilein, _ := os.Open(filepath.Join(output_dir, file.Name()))
	    defer ffilein.Close()
	    if err != nil {
                log.Fatal(err)
	    }
	    fzipput, err := zipfile.Create("/" + file.Name())
	    if err != nil {
                log.Fatal(err)
            }
	    io.Copy(fzipput, ffilein)
	}
	zipfile.Close()
    }
    os.RemoveAll(tmp_dir)
}

func main() {
    fmt.Printf("goe handler started")
    http.HandleFunc("/", handler)
    log.Fatal(http.ListenAndServe(":8080", nil))
}
