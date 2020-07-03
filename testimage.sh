#!/bin/bash
export PATH="/ci/bin:/ci/vendor/bin:$PATH"
. ~/.nvm/nvm.sh
git clone --branch "$QSTACK_VERSION" https://github.com/maths/moodle-qtype_stack
cd moodle-qtype_stack || exit 1
patch -p1 << "EOF"
diff --git a/db/install.php b/db/install.php
index d573cde9..63f6a16e 100644
--- a/db/install.php
+++ b/db/install.php
@@ -58,7 +58,7 @@ function xmldb_qtype_stack_install() {
         // Set to the same defaults as in settings.php - however, that has not been done
         // yet in the Moodle install code flow, so we have to duplicate here.
         set_config('maximaversion', 'default', 'qtype_stack');
-        set_config('castimeout', 10, 'qtype_stack');
+        set_config('castimeout', 120, 'qtype_stack');
         set_config('casresultscache', 'db', 'qtype_stack');
         set_config('maximacommand', '', 'qtype_stack');
         set_config('serveruserpass', '', 'qtype_stack');
EOF
cd ..

moodle-plugin-ci add-plugin maths/moodle-qbehaviour_dfexplicitvaildate
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_dfcbmexplicitvaildate
moodle-plugin-ci add-plugin maths/moodle-qbehaviour_adaptivemultipart

moodle-plugin-ci install --plugin moodle-qtype_stack --db-host=postgres

moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_PLATFORM",        "server");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMAVERSION",   "5.41.0");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASTIMEOUT",      "10");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASRESULTSCACHE", "db");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMACOMMAND",   "http://2020042000.kub00.math.uni-goettingen.de/goemaxima");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_PLOTCOMMAND",     "gnuplot");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_MAXIMALIBRARIES", "stats, distrib, descriptive, simplex");'
moodle-plugin-ci add-config 'define("QTYPE_STACK_TEST_CONFIG_CASDEBUGGING",    "0");'
moodle-plugin-ci phpunit


