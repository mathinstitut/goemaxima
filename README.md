goemaxima
=========
This project developed at the Mathematical Institute of Goettingen provides an alternative webservice for stack, which is a question type for ilias and moodle focused on math.
It is mainly intended to be used as a docker container in a cluster, optimally one supporting autoscaling, as scaling in the service itself is not supported.

This implementation in golang strives to have faster startup and processing times than the corresponding java version by using fork instead of starting a new process for each request.
For some more information on how this works, [see the documentation](/doc/How_it_works.md).


Building a Docker Image
=======================

A docker image can be built by first building the web server with buildweb.sh, which will place the web server executable into `./bin/`.
In order for that to work, `go` needs to be installed.

After that, the docker container for a particular stackmaxima version can be built by invoking `buildimage.sh STACKMAXIMA_VERSION` with `STACKMAXIMA_VERSION` substitutet to be the stackmaxima version to base the container off.

Example:
```
$ ./buildweb.sh
$ ./buildimage.sh 2020061000
```

The image should then be available as `goemaxima:2020061000-dev`.

The supported stackmaxima versions can be seen by looking at the versions file of the root of this repository.

Using the Docker Image
======================

The prebuilt docker images are available from the docker hub at `mathinstitut/goemaxima:[version]-latest`.

The port of the server in the container is `8080` and the path that has to be input into stack is `http://[address:port]/goemaxima`.
Some older versions of the image accept only `http://[address:port]/maxima` and this url should work in newer versions as well.

You can the image with
```
$ docker run --restart=always --tmpfs /tmp -p $port:8080 $imagename
```
where `$port` is the port you want to make the service available on and `$imagename` is the name of the docker image you chose (e.g. `mathinstitut/goemaxima:2020120600-latest`).

Note that this program prefers to quit on errors it can not recover from, so setting `restart=always` is strongly recommended.

Environment Variables
=====================
A few internal options can be set from environment variables.

Some debug logs can be activated with `GOEMAXIMA_DEBUG=1` (or more with `GOEMAXIMA_DEBUG=2`).

`GOEMAXIMA_NUSER` sets the number of processes that can run at the same time. The maximum number is 32 and it is the default.

`GOEMAXIMA_QUEUE_LEN` sets the target number of processes that are idle and ready to accept a request. The default is 3 (but note that there is always one extra process waiting).

`GOEMAXIMA_TEMP_DIR` is the temporary directory where maxima processes write plots and have their named pipes. Its default is `/tmp/maxima`. It is the only location where the server writes to and should be mapped to tmpfs to increase speed.

`GOEMAXIMA_LIB_PATH` is the path to a library that is loaded on startup of the maxima process.

Metrics
=======
Prometheus metrics are published on `/metrics` and include number of success, timeout and internal error responses and also histograms of process startup and response times.

License
=======
goemaxima is licensed under the GPLv3.
