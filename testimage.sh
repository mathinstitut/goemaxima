#!/bin/bash
export PATH="/ci/bin:/ci/vendor/bin:$PATH"
. ~/.nvm/nvm.sh
git clone --branch "$QSTACK_VERSION" https://github.com/maths/moodle-qtype_stack
cd moodle-qtype_stack || exit 1
patch -p1 << "EOF"
diff --git a/db/install.php b/db/install.php
index d573cde9..5db9ba34 100644
--- a/db/install.php
+++ b/db/install.php
@@ -73,12 +73,5 @@ function xmldb_qtype_stack_install() {
         set_config('maximalibraries', '', 'qtype_stack');
         set_config('casdebugging', 1, 'qtype_stack');
         set_config('mathsdisplay', 'mathjax', 'qtype_stack');
-
-        if (!defined('QTYPE_STACK_TEST_CONFIG_PLATFORM') || QTYPE_STACK_TEST_CONFIG_PLATFORM !== 'server') {
-            list($ok, $message) = stack_cas_configuration::create_auto_maxima_image();
-            if (!$ok) {
-                throw new coding_exception('maxima_opt_auto creation failed.', $message);
-            }
-        }
     }
 }
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


