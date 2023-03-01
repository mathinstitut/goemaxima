#!/bin/bash
# This script uses the moodle-plugin-ci utility (https://moodlehq.github.io/moodle-plugin-ci/)
# To run all the STACK unit tests against a particular Maxima server, whose URL is given
# in the environment variable $TEST_URL.
#
# $1: optional path where moodle is installed

MOODLE_PATH="$1"
if [ -z $1 ]; then
	MOODLE_PATH="./moodle"
fi

export PATH="/ci/bin:/ci/vendor/bin:$PATH"
. ~/.nvm/nvm.sh
git clone --branch "$QSTACK_VERSION" https://github.com/maths/moodle-qtype_stack

moodle-plugin-ci add-plugin maths/moodle-qbehaviour_dfexplicitvaildate
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_dfcbmexplicitvaildate
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_adaptivemultipart

moodle-plugin-ci install --moodle="$MOODLE_PATH" --no-init --plugin moodle-qtype_stack --db-host=postgres

moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_PLATFORM",        "server");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMAVERSION",   "5.44.0");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASTIMEOUT",      "10");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASRESULTSCACHE", "db");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMACOMMAND",   "'"$TEST_URL"'");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMACOMMANDSERVER",   "'"$TEST_URL"'");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_PLOTCOMMAND",     "gnuplot");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMALIBRARIES", "stats, distrib, descriptive, simplex");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASDEBUGGING",    "0");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMACOMMANDOPT", "");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASPREPARSE", "true");'

cd "$MOODLE_PATH"
php admin/tool/phpunit/cli/init.php
cd -

moodle-plugin-ci phpunit
