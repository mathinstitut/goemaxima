How it works
============
API
---
The basic API stack uses for web access is simple:

1. send a POST-request with `timeout`, `ploturlbase`, `version` and `input`
2. put the value of `ploturlbase` into a maxima variable
3. send `input` to the maxima process
4. if the maxima process is not finished after `timeout` milliseconds, return a 416 error
5. else return the output of `maxima`

Implementation
--------------
The web interface to the maxima processes is implemented in golang.

At startup, the server process starts the maxima mother process.
The server process maintains a queue of some maxima processes derived from the mother process and ready to use.
Each maxima process gets assigned a user of the form `maxima-%d` which is a valid unix user (these are already there if you use the Docker image).
If a new request comes in, it takes a process from the queue and communicates with the process through some named pipes.
After it is finished (or the process timed out) all leftover process with the process' username are kill-9'd and temporary files/pipes deleted.
The user id is then again available for other processes.

Process Lifecycle
-----------------
At first, the server process creates a temporary directory for the future process and creates 2 pipes, inpipe and outpipe for stdin and stdout of maxima.
The server then sends to the mother process the maxima user id and the path of the temporary directory.

The mother process is in a loop which is called at server startup:
`(forking-loop)` is called, which was loaded through `/assets/maxima-fork.lisp` which uses sbcl's FFI to call the forking loop `fork_new_process()` in `/src/maxima_fork.c`.
When it receives a new id and path, it forks a new maxima process from the mother process and does some setup.

Forking a process from an existing one is vastly more efficient (faster with a factor of around 10x) than execve'ing a new maxima process, and the memory consumption is reduced, too.
It does have the caveat that, at run time, there will be more page faults because of the copy-on-write mechanics, which slows the process down.

After the child process responds that it is ready, the server process puts its description into a queue, where it is taken later for evaluating the request.
