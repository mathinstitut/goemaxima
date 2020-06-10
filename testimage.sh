#!/bin/bash
apt-get update -y && apt-get install -y git-core postgresql-client texinfo maxima maxima-share
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.35.3/install.sh | bash
. $HOME/.nvm/nvm.sh
nvm install 14
nvm use 14
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer create-project -n --no-dev --prefer-dist blackboard-open-source/moodle-plugin-ci ci ^2
export PATH="$(cd ci/bin; pwd):$(cd ci/vendor/bin; pwd):$PATH"
chmod u+x ci/bin/moodle-plugin-ci
chmod u+x ci/bin/*
umask u+x
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


