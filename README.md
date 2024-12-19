goemaxima
=========
This project, originally developed at the Mathematical Institute of Goettingen, provides an alternative CAS server implementation for stack, which is a question type for ilias and moodle focused on math.
The image was originally developed to be used in a cluster/loadbalancing setup, but for most workloads it is viable to use it as a single container.

This implementation in golang strives to have faster startup and processing times than the corresponding java version by using fork instead of starting a new process for each request.
For some more information on how this works, [see the documentation](/doc/How_it_works.md).


Using the Docker Image
======================

The prebuilt docker images are available from the docker hub at `mathinstitut/goemaxima:[version]-latest`.

To use it, you first have to know what stackmaxima version you need.
Note that this is different from the moodle stack version, ilias stack version and maxima version.
The needed stackmaxima version can be seen in the ["What Stackmaxima version do I need?"](#what-stackmaxima-version-do-i-need) section.

They can be simply run with docker compose in the top-directory of the repository (setting the `STACKMAXIMA_VERSION` to the desired value):
```
echo STACKMAXIMA_VERSION=2024111900 > stackmaxima-version
docker compose --env-file=stackmaxima-version up -d
```
The container should then be available on port 8080 (from outside the host too, keep this behind a firewall so it is not reachable from the general internet).

The port of the server inside the container is `8080` and the path that has to be input into stack is `http://[address:port]/goemaxima`.
Some older versions of the image accept only `http://[address:port]/maxima` and this url should work in newer versions as well.

Stack expects a maxima version to be set in the settings.
You can find out which maxima version to use for a particular `STACKMAXIMA_VERSION` by taking a look at the last column in the ["What Stackmaxima version do I need?"](#what-stackmaxima-version-do-i-need) section

If you do not wish to use the docker-compose configuration, you can also run the image with
```
$ docker run --restart=always --tmpfs /tmp -p $address:$port:8080 $imagename
```
where `$address:$port` is the ip and port you want to make the service available on and `$imagename` is the name of the docker image you chose (e.g. `mathinstitut/goemaxima:2020120600-latest`).
Use `0.0.0.0` as address to listen to all addresses.

Note that this program prefers to quit on errors it can not recover from, so setting `restart=always` is strongly recommended.

The memory usage of the container is around 50MB when idle and can quickly spike to 250MB on heavy use.
The process startup time for a single request is a few milliseconds and negligible in comparison to processing the CAS request itself.

For advanced users, one can also deploy the image in kubernetes by using the helm chart provided in the `helmmaxima` directory.

What Stackmaxima version do I need?
===================================

| Ilias Stack Version | Moodle Stack Version | Stackmaxima version | Included Maxima version |
| ------------------- | -------------------- | ------------------- | ----------------------- |
| `6`, `7`            | `4.2.2a`             | 2019090200          | 5.41.0                  |
| -                   | `4.3.1`              | 2020042000          | 5.41.0                  |
| -                   | `4.3.2`              | 2020052700          | 5.41.0                  |
| -                   | `4.3.3`              | 2020061000          | 5.41.0                  |
| -                   | `4.3.4`              | 2020070100          | 5.41.0                  |
| -                   | `4.3.6`              | 2020100900          | 5.41.0                  |
| -                   | `4.3.7`              | 2020101501          | 5.41.0                  |
| -                   | `4.3.8`              | 2020120600          | 5.41.0                  |
| `7-DEV`             | `4.3.10`             | 2021120900          | 5.44.0                  |
| -                   | `4.3.11`             | 2022060100          | 5.44.0                  |
| -                   | `4.4.0`              | 2022071300          | 5.44.0                  |
| -                   | `4.4.1`              | 2022082900          | 5.44.0                  |
| -                   | `4.4.2`              | 2023010400          | 5.44.0                  |
| -                   | `4.4.3`              | 2023052400          | 5.44.0                  |
| -                   | `4.4.4`              | 2023060500          | 5.44.0                  |
| -                   | `4.4.5`              | 2023072101          | 5.44.0                  |
| -                   | `4.4.6`              | 2023102700          | 5.44.0                  |
| `7.5`, `8.5`        | `4.5.0`              | 2023121100          | 5.44.0                  |
| -                   | `4.5.0-hf2`          | 2024012900          | 5.44.0                  |
| -                   | `4.6.0`              | 2024060300          | 5.44.0                  |
| -                   | `4.7.0`              | 2024072400          | 5.44.0                  |
| -                   | `4.8.0`              | 2024111500          | 5.44.0                  |
| -                   | `4.8.1`              | 2024111900          | 5.44.0                  |


Building a Docker Image
=======================

## Using a bundled stackmaxima
There are prebuilt images are already available on the [dockerhub](https://hub.docker.com/r/mathinstitut/goemaxima).
This section just describes the build process in case you want to build your own image anyway.
Normally, you can just skip this step and go to [Using the Docker Image](#using-the-docker-image) directly.

The docker container for a particular stackmaxima version can be built by invoking `buildimage.sh STACKMAXIMA_VERSION` with `STACKMAXIMA_VERSION` being the stackmaxima version to base the container off.

Example:
```
$ ./buildimage.sh 2020061000
```

The image should then be available as `goemaxima:2020061000-dev`.

The supported stackmaxima versions can be seen by looking at the versions file of the root of this repository.

## Dynamically building against qtype_stack
The Docker buildfile can also build an image that will work with a specified version of [Moodle's qtype_stack plugin](https://github.com/maths/moodle-qtype_stack).
By providing the build arguments `QTYPE_STACK_REMOTE` and `QTYPE_STACK_COMMIT` the appropriate stackmaxima libraries 
will be extracted and the image will be built using the appropriate Maxima and SBCL versions (based on the versions
file in this repository).

If the build args `MAXIMA_VERSION` or `SBCL_VERSION` are specified alongside `QTYPE_STACK_REMOTE` and `QTYPE_STACK_COMMIT`
then the specified Maxima or SBCL version will be used, instead of being inferred from the versions file.

Environment Variables
=====================
A few internal options can be set from environment variables.

* `GOEMAXIMA_DEBUG` activates some debug logs with `GOEMAXIMA_DEBUG=1` (or more with `GOEMAXIMA_DEBUG=2`).

* `GOEMAXIMA_EXTRA_PACKAGES` is a colon-separated list of maxima packages that gets loaded at startup at the server.
   The load time of the packages mostly influences the startup time of the server itself and should not significantly affect the serve time of a request.
   The entries simply get loaded through a load(entry) call in maxima.
   This means you can also mount your own maxima libraries into the docker image and load them by using a path.

* `GOEMAXIMA_NUSER` sets the number of processes that can run at the same time. The maximum number is 32 and it is the default.

* `GOEMAXIMA_QUEUE_LEN` sets the target number of processes that are idle and ready to accept a request. The default is 3 (but note that there is always one extra process waiting).

* `GOEMAXIMA_TEMP_DIR` is the temporary directory where maxima processes write plots and have their named pipes. Its default is `/tmp/maxima`. It is the only location where the server writes to and should be mapped to tmpfs to increase speed.

* `GOEMAXIMA_LIB_PATH` is the path to a library that is loaded on startup of the maxima process.
   If you do not modify the container or use the webserver executable in another context than the image, you probably want to leave this variable unset.
   If you want to load extra packages/libraries, use the `GOEMAXIMA_EXTRA_PACKAGES` variable instead.

* `GOEMAXIMA_PROCESS_ISOLATION` is either `none` or `normal`.
   If `normal` (the default), the maxima processes are run under separate users to prevent them from interfering with each other.
   As a consequence of this, the server needs to be the root user (so that `CAP_SETUID`/`CAP_SETGID` are available).
   However, in the case of `none`, the processes all run as the same user and the server must not run as root.
   This is useful in environments where the root user is not allowed, however it does mean that, in case of a hijacked process, the attacker can also hijack the server process.

Metrics
=======
Prometheus metrics are published on `/metrics` and include number of success, timeout and internal error responses and also histograms of process startup and response times.

License
=======
goemaxima is licensed under the GPLv3.
