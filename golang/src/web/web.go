package main

import (
    "fmt"
    "log"
    "net/http"
    //"net/url"
    //"os"
    //"os/exec"
)

func handler(w http.ResponseWriter, r *http.Request) {
    //cmd := exec.Command("/opt/maximapool/2017121800/maxima-optimised","-r",r.URL.Query().Get("input"))
    //cmd.Stdout = w
    //cmd.Stderr = os.Stderr
    //cmd.Run()
    fmt.Printf(r.URL.Query().Get("input"))
}

func main() {
    http.HandleFunc("/", handler)
    log.Fatal(http.ListenAndServe(":8080", nil))
}
