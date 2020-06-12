#!/bin/bash
export PATH="/ci/bin:/ci/vendor/bin:$PATH"
git clone --branch "$QSTACK_VERSION" https://github.com/maths/moodle-qtype_stack
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_dfexplicitvaildate
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_dfcbmexplicitvaildate
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_adaptivemultipart

moodle-plugin-ci install --plugin moodle-qtype_stack --db-host=postgres

moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_PLATFORM",        "server");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMAVERSION",   "5.41.0");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASTIMEOUT",      "10");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASRESULTSCACHE", "db");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMACOMMAND",   "http://kubecluster.test/godev/");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_PLOTCOMMAND",     "gnuplot");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMALIBRARIES", "stats, distrib, descriptive, simplex");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASDEBUGGING",    "0");'
moodle-plugin-ci phpunit


