Copyright 2017 Institut fuer Lern-Innovation,Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3 or later, see LICENSE

Includes a modified core part of STACK version 3.3
Copyright 2012 University of Birmingham
licensed under GPLv3 or later, see classes/stack/COPYING.txt
http://stack.bham.ac.uk

ILIAS STACK Question Type Plugin.
================================

- Author: Jesus Copado <jesus.copado@fim.uni-erlangen.de>, Fred Neumann <fred.neumann@fim.uni-erlangen.de>
- Forum: http://www.ilias.de/docu/goto_docu_frm_3474_2766.html
- Bug Reports: http://www.ilias.de/mantis (Choose project "ILIAS plugins" and filter by category "STACK Question Type")

This plugin is an ILIAS port of STACK, developed by Chris Sangwin. It provides a test question type
for mathematical questions that are calculated by a the Computer Algebra System (CAS) Maxima.
See the original STACK documentation at http://stack.bham.ac.uk/moodle

Additional Software Requirements
--------------------------------

* Maxima (http://maxima.sourceforge.net)

Maxima is a open sorce computer algebra system and part of most Linux distributions.
A version for windows is available, too. Maxima needs to be installed on the web server running
your ILIAS installation.
Either install the package from your linux distribution or download and install it from
sourceforge (http://sourceforge.net/projects/maxima/files/)

* GNUplot (http://www.gnuplot.info)

GNUplot is used by maxima to generate graphical plots of functions etc. It is freely available
and part of most Linux distrubutions. GNUplot needs to be installed on the web server
running your ILIAS and maxima installations.
Either install the package from your linux distribution or download and install it from
sourceforge (http://sourceforge.net/projects/gnuplot/files/)

* MathJax (http://www.mathjax.org)

MathJax is an open source JavaScript display engine for mathematics. It is used by the STACK plugin
to display maths in question, user input validation and feedback. It can either be linked from
cdn.mathjax.org or downloaded to your own web server. It has to be configured in ILIAS:

1. Go to Administration > Third Party Software > MathJax
2. Enable MathJax and enter the URL to MathJax (local or proposed cdn)
3. Save

First Installation of the plugin
--------------------------------
1. Copy the assStackQuestion directory to your ILIAS installation at the followin path
(create subdirectories, if neccessary):
Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion

2. Go to Administration > Plugins
3. Choose action "Update" for the assStackQuestion plugin
4. Choose action "Activate" for the assStackQuestion plugin
5. Choose action "Refresh Languages" for the assStackQuestion plugin

Configuration and test of the plugin
------------------------------------
1. Go to Administration > Plugins
2. Choose action "Configure" for the assStackQuestion plugin
3. Set the platform type and maxima version according your installation
4. Go to the tab "Health Check" and click "Do Health Check"
5. If some checks are not passed, click "Show Debugging Data" to get more information

Import of questions from moodleXML
----------------------------------
1. Create an ILIAS question pool
2. Click "Create question", choose "Stack Question" and click "Create"
3. Click "Create Question from MoodleXML"
4. Select a moodleXML package on your computer and click "Import"

Usage of STACK questions
------------------------
You can work with a STACK question like any other question in ILIAS. You can preview it in the question pool
and already try it out there. You can copy it to an ILIAS test and use it there.  A a test participant you will
normally answer a question in two steps. First you enter your answer as a formula in an input field and click "Validate"
beneath that field to check how your input is interpretet. This will give you a graphical version of you entry which may
already be simplified. If you entry can't be interpreted, you will get an error message. When you are satisfied with your
input you can evaluate your answer (in self assessment mode) or move to the next question (in an exam).

Version History
===============

* The stable version 3.0.x for **ILIAS 5.3** with new functionalities from STACK such a new input types is found in the Github branch **master-ilias53**
* The stable version 2.4.x for **ILIAS 5.2 to 5.3** is found in the GitHub branch **master-ilias52**
* The stable version 2.3.x for **ILIAS 5.0 to 5.1** is found in the GitHub branch **master**

Update from Version 2.x
-----------------------
After updating the code files of the plugin, the update is started in the plugin administration of ILIAS.
All STACK questions of your platform will be translated to the new syntax for CAS text in STACK (use of {@..@} instead of @...@)
This change is automatically done in the plugin update, but we recommend to check the questions before use it in tests.

PLEASE BACKUP YOUR DATABASE before you run the update from an older version. Depending on the number of questions, the update takes some minutes, please set the PHP variable max_execution_time high enough.

The translation is also done when importing questions from ILIAS or MoodleXML, but please notice that this conversion is one way.You can import "old CASText behaviour" questions to a platform with STACK plugin version 3.0+.
But if you import "new CASText behaviour" questions to a platform with a previous version of the plugin, your question will not be properly shown on that platform.

Version 3.0.24 (2019-09-10) for ILIAS 5.3
----------------------------------------
Solved some bugs related to Copy of PRTs

Version 3.0.22 (2019-05-03) for ILIAS 5.3
----------------------------------------
The following bug reports were fixed:
- https://mantis.ilias.de/view.php?id=22847 About validation in equivalence inputs
- https://mantis.ilias.de/view.php?id=24640 About model answer being variables which are a set of numbers.
- https://mantis.ilias.de/view.php?id=24998 About not showing best solution properly when best solution is 0.
- Partial solution for https://mantis.ilias.de/view.php?id=24273 for Algebraic inputs. Not solved for other question types.
- https://mantis.ilias.de/view.php?id=24835 About matrix brackets
- https://mantis.ilias.de/view.php?id=25256 About user input not shown in validation or test results

Version 3.0.20 (2019-04-03) for ILIAS 5.3
----------------------------------------
The following bug reports were fixed:
- https://mantis.ilias.de/view.php?id=24998

Version 3.0.17 (2019-03-13) for ILIAS 5.3
-----------------------------------------
- Configuration of multiple MaximaPool servers for different purposes (Authoring, Test Run)

Version 3.0.16 (2019-02-27) for ILIAS 5.3
-----------------------------------------
- Added a new feature Copy of Nodes and PRT: In question Authoring now exists the option to copy nodes and PRT, when clicking on copying Node or PRT, the chosen element is stored in the session, then, the user should go to the question or PRT the user wants to paste te node/prt and click on paste. A new PRT or a new node will be created with the values of the copied one. Please notice that when a node is copied to a PRT, the fields next node when true/false are not copied and should be edited by hand.
The following bug reports were fixed:
- https://mantis.ilias.de/view.php?id=24835 about matrix brackets

Version 3.0.12 (2018-11-30) for ILIAS 5.3
----------------------------------------
- Solved some problems with best solution display when question variables are used in model answer in algebraic inputs.
- Solved some problems with Matrix display in best solution
The following bug reports were fixed:
- https://mantis.ilias.de/view.php?id=23977
- https://mantis.ilias.de/view.php?id=23895 about syntax hints

Version 3.0.11 (2018-11-26) for ILIAS 5.3
----------------------------------------
- Solved problems to establish default values for options, inputs and PRT, now all already present default values works properly.
- Added the following default values to plugin configuration: Options: Matrix Parenthesis, Inputs: Syntax hint, Forbidden word, allowed words, show validation (as dropdown) and extra options for inputs, PRT: Simplification, First node of predefined PRT: Answertest, test options, quiet feedback, and mode, score, penalty and answernote for both positive and negative branch.
- Added TinyMCE editor for default feedback in configuration/options.
- Some text changes has been made in the german language. 
The following bug report were fixed:
- https://mantis.ilias.de/view.php?id=24003 about missing translation to german.
- https://mantis.ilias.de/view.php?id=23913 about grammatical error in german
- https://mantis.ilias.de/view.php?id=24121 about missing display of best solution

Version 3.0.10 (2018-10-24) for ILIAS 5.3
----------------------------------------
Validation button is now directly attached to the input for algebraic inputs, instead of having an space between them.
Validation buttons now doesn´t use the bootstrap style.
Some minor changes has been made, and the following bugs has been solved.
- https://mantis.ilias.de/view.php?id=23753 about checking the user response in the code.
- https://mantis.ilias.de/view.php?id=23314 about info messages
- https://mantis.ilias.de/view.php?id=23533 about validation of string inputs
- https://mantis.ilias.de/view.php?id=23414 about testcases

Version 3.0.9 (2018-10-10) for ILIAS 5.3
----------------------------------------
Validation button is now displayed as a small "check" button, and is always displayed next to the input it belongs.
Some minor bugs has been solved in this version, please use https://mantis.ilias.de to report bugs.

Version 3.0.8 (2018-09-07) for ILIAS 5.3
----------------------------------------
Inputs representation in validation and best solution now takes the minimal size as possible, depending on user  or teacher input, and it´s displayed as code text, instead of repeating the input again

Version 3.0.7 (2018-09-03) for ILIAS 5.3
----------------------------------------
Some important changes has been made to question view, either in preview, test mode or printview, the main goal of this changes is to fulfill the needs of SIG Mathe+ILIAS in terms of going back to previous 5.2 style of inputs presentation
- All inputs which can be validated got the validation view changed to a disabled input or textarea filled in with the user solution on the left side, and the validation feedback on the right side.
- Validation messages are now displayed with a white background in order to distinct it from question text.
- The behaviour of inputs presentation in best solution in aligned to validation, instead of showing only a message saying "a possible solution is..." a disabled input is presented filled in with the model answer, All these inputs have the same format as the question input.  
Some minor bugs has been solved in this version, please use https://mantis.ilias.de to report bugs.

Version 3.0.6 (2018-06-25) for ILIAS 5.3
----------------------------------------
Some index has been created in the DB, in order to improve performance.
Some code changes were made in order to allow STACK questions run in Learning modules through PCPluginQuestion plugin.
Some bugfix were made on this version:
- https://www.ilias.de/mantis/view.php?id=23135 About showing validation in dropdown, checkbox and radiobutton inputs
- https://www.ilias.de/mantis/view.php?id=22900 About showing validation after question has been evaluated
- https://www.ilias.de/mantis/view.php?id=22655 About error messages shown in wrong places.
- https://www.ilias.de/mantis/view.php?id=22954 About missing german text in units questions.
- https://www.ilias.de/mantis/view.php?id=23237 About problem when updating to 3.0 with prt feedback.

Version 3.0.5 (2018-05-28) for ILIAS 5.3
----------------------------------------
Some bugfix were made on this version:
- https://www.ilias.de/mantis/view.php?id=22847 About validation in new input types
- https://www.ilias.de/mantis/view.php?id=22969 About validation options
- https://www.ilias.de/mantis/view.php?id=23016 About equivalence input firstline option
- https://www.ilias.de/mantis/view.php?id=22900 about showing validation.

Version 3.0.4 (2018-04-26) for ILIAS 5.3
----------------------------------------
Some bugfix were made on this version:
- https://www.ilias.de/mantis/view.php?id=22945 About space between checkboxes and radiobuttons and texts
- https://www.ilias.de/mantis/view.php?id=22938 About problems installing the plugin in a fresh 5.3 client
- https://www.ilias.de/mantis/view.php?id=22925 About answertests names in german missing
- https://www.ilias.de/mantis/view.php?id=22912 About german text file
- https://www.ilias.de/mantis/view.php?id=22946 About validation in equivalence inputs
- https://www.ilias.de/mantis/view.php?id=22947 About syntax hint in equivalence inputs
- https://www.ilias.de/mantis/view.php?id=22847 About validation in equivalence inputs
- Some other minor changes.

Version 3.0.3 (2018-04-05) for ILIAS 5.3
----------------------------------------
Some bugfix were made on this version:
- https://www.ilias.de/mantis/view.php?id=22795 About Deployed seeds navigation
- https://www.ilias.de/mantis/view.php?id=22782 About testcases


Version 3.0.2 (2018-03-28) for ILIAS 5.3
----------------------------------------
Some bugfix were made on this version:
- https://www.ilias.de/mantis/view.php?id=22780 regarding br before inputs
- https://www.ilias.de/mantis/view.php?id=22779 about HTML in version 3.0

Version 3.0.0 (2018-03-07) for ILIAS 5.3
----------------------------------------
This is a major update. It uses the core classes from STACK version 4.0, the sample questions have also be changed. Please read the section "Update from version 2.x".

NEW FEATURES:
- 8 new input types (We highly recommend to read the Documentation of all new input types that can be found here: https://stack2.maths.ed.ac.uk/demo/question/type/stack/doc/doc.php/Authoring/Inputs.md):
	- Numerical input:
      This input type requires the student to type in a number of some kind. Any expression with a variable will be rejected as invalid.
    - Scientific units input:
    The support for scientific units includes an input type which enables teachers to check units as valid/invalid.
    - Equivalence reasoning input:
      The purpose of this input type is to enable students to work line by line and reason by equivalence. Note, the teacher's answer and any syntax hint must be a list! If you just pass in an expression strange behaviour may result.
    - Dropdown/Checkbox/Radio:
      The dropdown, checkbox and radio input types enable teachers to create multiple choice questions.
    - String input:
      This is a normal input into which students may type whatever they choose. It is always converted into a Maxima string internally. Note that there is no way whatsoever to parse the student's string into a Maxima expression. If you accept a string, then it will always remain a string! You can't later check for algebraic equivalence, the only tests available will be simple string matches, regular expressions etc
    - Notes input
      This input is a text area into which students may type whatever they choose. It can be used to gather their notes or "working". However, this input always returns an empty value to the CAS, so that the contents are never assessed.
 - CASText now supports conditional statements and adaptive blocks.
 - Healthcheck has been rebuilt, now shows more information about the CAS connection and the Maxima version used.
 - Maxima Libraries can be added to maximalocal from plugin configuration (Notice that this feature doesn't work with server configuration)
 
