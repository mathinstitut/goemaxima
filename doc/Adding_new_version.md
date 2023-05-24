Adding a new stackmaxima version
--------------------------------

The maxima instances depend on libraries specific to the stack version being used.
The library files are stored in the `stack` directory in this repository.

When updating `goemaxima` to use a new version, the following has to be done:

* Get the commit of the [moodle repository](https://github.com/maths/moodle-qtype_stack) corresponding to the wanted version.
  The libraries will be found in in the /stack/maxima directory.
* Create a new directory in /stack of this repository named after the stackmaxima version number.
  The version number can be found at the bottom of the file `moodle-qtype_stack/stack/maxima/stackmaxima.mac`.
* Copy the whole `maxima` directory unchanged into the new directory.
* In the moodle instance running the new stack version, go to the healthcheck site of the stack plugin
  and copy the generated maxima configuration file into the new version dir as `maximalocal.mac.template`.
  Make sure the correct maxima version is selected.
* Replace the site specific library, log, tmp and plot path with `${LIB}`, `${LOG}`, `${TMP}` and `${PLOT}`.
* Comment out the `load("stackmaxima.mac)` at the end of the file.
* Add a line for the new version to the `versions` file in the top of the repository.
* Update the default version in the `docker-compose.yml`.
* Add the new version to the table in the README.
* Run `./build.sh` to build the new image.
  It will be available as `goemaxima:$version-dev`.
* Test the image with the moodle instance (for example using the answer-test script).
