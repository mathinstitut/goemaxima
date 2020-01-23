package main

import (
    "os"
    "os/exec"
    //"fmt"
    //"log"
)

func main() {
    cmd := exec.Command("ls", "/go")
    cmd.Stdout = os.Stdout
    cmd.Stderr = os.Stderr
    cmd.Run()
}
