<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question Unit tests GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 1.8$
 * @ingroup    ModulesTestQuestionPool
 *
 * @ilCtrl_isCalledBy assStackQuestionTestGUI: ilObjQuestionPoolGUI
 */
class assStackQuestionTestGUI
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var ilTemplate for
	 */
	private $template;

	/**
	 * @var int
	 */
	private $question_id;


	/**
	 * @var mixed Array with the assStackQuestionTest object of the current question.
	 */
	private $tests;

	/**
	 * @var mixed Unit test results from assStackQuestionUnitTests
	 */
	private $unit_test_results;


	/**
	 * Sets required data for unit tests management
	 * @param $a_parent_obj assStackQuestionGUI
	 * @param $unit_test_results array
	 */
	function __construct($a_parent_obj, $plugin, $unit_test_results = array())
	{
		//Set plugin and template objects
		$this->setPlugin($plugin);
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_unit_tests_container.html'));
		$this->setQuestionId($a_parent_obj->object->getId());

		//Set Unit tests data
		$this->setTests(assStackQuestionTest::_read($this->getQuestionId()));
		$this->setUnitTestResults($unit_test_results);
		$this->question_gui = $a_parent_obj;

		global $tpl;
		$this->tpl = $tpl;
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * @return HTML
	 */
	public function showUnitTestsPanel($a_mode = FALSE)
	{
		global $DIC;

		//Set mode to TRUE if test run data exists
		if ($a_mode === TRUE)
		{
			$this->mode = TRUE;
		}
		//Toolbar creation
		include_once("./Services/UIComponent/Toolbar/classes/class.ilToolbarGUI.php");
		include_once('./Services/UIComponent/Button/classes/class.ilButton.php');
		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();

		$toolbar = new ilToolbarGUI();
		$create_test_case = ilButton::getInstance();
		$create_test_case->setCaption($lng->txt("create"), FALSE);
		$create_test_case->setName("createTestcases", TRUE);
		$create_test_case->setFormAction($ctrl->getLinkTargetByClass("assSTACKQuestionGUI", "createTestcases"));
		$toolbar->addButtonInstance($create_test_case);

		$run_all_tests = ilButton::getInstance();
		$run_all_tests->setCaption($this->getPlugin()->txt("ut_run_all_tests"), FALSE);
		$run_all_tests->setName("runTestcases", TRUE);
		$run_all_tests->setFormAction($ctrl->getLinkTargetByClass("assSTACKQuestionGUI", "runTestcases"));
		$toolbar->addButtonInstance($run_all_tests);
		$toolbar->setFormAction($ctrl->getLinkTargetByClass("assSTACKQuestionGUI"));

		if (sizeof($this->getTests()))
		{
			include_once './Services/Accordion/classes/class.ilAccordionGUI.php';
			$unit_tests_accordion = new ilAccordionGUI();
			foreach ($this->getTests() as $test)
			{
				$unit_tests_accordion->addItem($this->fillTestCaseHeader($test), $this->fillTestCaseContent($test), TRUE);
			}

			return $toolbar->getHTML() . $unit_tests_accordion->getHTML();
		} else
		{
			return $toolbar->getHTML();
		}

	}

	/*
	 * FILLING TEMPLATES
	 */

	/**
	 * @param assStackQuestionTest $test
	 * @return string
	 */
	public function fillTestCaseHeader($test)
	{
		require_once 'Services/UIComponent/Glyph/classes/class.ilGlyphGUI.php';
		if ($this->mode)
		{
			$unit_test_results = $this->getUnitTestResults($test->getTestCase());
			if (isset($unit_test_results["test_passed"]))
			{
				if ($unit_test_results["test_passed"])
				{
					$icon = ilUtil::getImagePath("icon_ok.svg");
				} else
				{
					$icon = ilUtil::getImagePath("icon_not_ok.svg");
				}

				return $this->getPlugin()->txt("ut_testcase_name") . " " . $test->getTestCase() . " " . '<img src="' . $icon . '" /> ';

			} else
			{
				return $this->getPlugin()->txt("ut_testcase_name") . " " . $test->getTestCase();
			}
		} else
		{
			return $this->getPlugin()->txt("ut_testcase_name") . " " . $test->getTestCase();
		}

	}

	/**
	 * @param assStackQuestionTest $test
	 * @return string
	 */
	public function fillTestCaseContent($test)
	{
		global $DIC;

		include_once "Services/UIComponent/Panel/classes/class.ilPanelGUI.php";
		include_once 'Services/Table/classes/class.ilTable2GUI.php';
		include_once('./Services/UIComponent/Button/classes/class.ilButton.php');
		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();

		$container_panel = ilPanelGUI::getInstance();
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

		$testcase_toolbar = new ilPropertyFormGUI();
		$testcase_toolbar->addCommandButton("runTestcases", $this->getPlugin()->txt("ut_run_testcase"));
		$testcase_toolbar->addCommandButton("editTestcases", $lng->txt("edit"));
		$testcase_toolbar->addCommandButton("doDeleteTestcase", $lng->txt("delete"));
		$testcase_toolbar->setFormAction($ctrl->getFormActionByClass('assStackQuestionGUI'));
		$testcase_name = new ilHiddenInputGUI('testcase_name');
		$testcase_name->setValue($test->getTestCase());
		$testcase_toolbar->addItem($testcase_name);
		$question_id = new ilHiddenInputGUI('question_id');
		$question_id->setValue($this->getQuestionId());
		$testcase_toolbar->addItem($question_id);

		//Part 1: Inputs panel
		$inputs_panel = ilPanelGUI::getInstance();
		$inputs_panel->setPanelStyle(ilPanelGUI::PANEL_STYLE_PRIMARY);
		$inputs_panel->setHeadingStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		$inputs_panel->setHeading($this->getPlugin()->txt('inputs'));
		$body = "";

		$inputs_panel->setBody($this->getQuestionFilledIn($test->getInputsForUnitTest()));

		//Part 2: PRT panel
		$prts_panel = ilPanelGUI::getInstance();
		$prts_panel->setPanelStyle(ilPanelGUI::PANEL_STYLE_PRIMARY);
		$prts_panel->setHeadingStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		$prts_panel->setHeading($this->getPlugin()->txt('prts'));
		$body = $this->getPRTTable($test);
		$prts_panel->setBody($body);

		//Join both in Container panel
		$container_panel->setBody($testcase_toolbar->getHTML() . $inputs_panel->getHTML() . $prts_panel->getHTML());

		return $container_panel->getHTML();
	}

	public function getQuestionFilledIn($inputs)
	{
		$body = "";
		foreach ($inputs as $input_name => $input)
		{
			$body .= $input_name . ": " . $input. "</br>";
		}

		return $body;
	}

	/**
	 * @param $testcase
	 * @param bool $mode
	 * @return mixed
	 */
	public function getPRTTable($testcase)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/GUI/tables/class.assStackQuestionTestTableGUI.php';
		$prts_table = new assStackQuestionTestTableGUI($this, "showUnitTests");
		if ($this->mode)
		{
			$prts_table->prepareData($this->getUnitTestResults($testcase->getTestCase()));
		} else
		{
			$prts_table->prepareData($testcase);
		}

		return $prts_table->getHTML();
	}

	/**
	 * This methos returns the form GUI for edit a testcase
	 * @param string $testcase_name
	 * @param array $question_inputs
	 * @param array $question_prts
	 */
	public function editTestcaseForm($testcase_name, $question_inputs, $question_prts)
	{
		global $DIC;
		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();

		include_once "Services/UIComponent/Panel/classes/class.ilPanelGUI.php";
		$form_container = ilPanelGUI::getInstance();
		$form_container->setPanelStyle(ilPanelGUI::PANEL_STYLE_PRIMARY);
		$form_container->setHeadingStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		$form_container->setHeading($this->getPlugin()->txt("ut_testcase_name") . " " . $testcase_name);

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ctrl->getFormActionByClass('assStackQuestionGUI'));

		//Get testcase data
		$unit_test = $this->getTests($testcase_name);

		//Initialization
		$testcase_name_hidden = new ilHiddenInputGUI('testcase_name');
		$testcase_name_hidden->setValue($testcase_name);
		$form->addItem($testcase_name_hidden);

		//Student inputs
		$inputs_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_student_response'), 'inputs');
		$form->addItem($inputs_title);

		//Check all inputs are presented
		foreach ($question_inputs as $input_name => $q_input)
		{
			$input_field = new ilTextInputGUI($input_name, $input_name);
			foreach ($unit_test->getTestInputs() as $input)
			{
				if ($input_name == $input->getTestInputName())
				{
					$input_field->setValue($input->getTestInputValue());
					break;
				}
			}
			$form->addItem($input_field);
		}

		//Expected score
		$expected_score_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_expected_mark'), 'expected_score_title');
		$form->addItem($expected_score_title);
		foreach ($question_prts as $prt_name => $q_prt)
		{
			$expected_score = new ilTextInputGUI($this->getPlugin()->txt('ut_expected_score_for') . ' ' . $prt_name, 'score_' . $prt_name);
			foreach ($unit_test->getTestExpected() as $prt => $expected)
			{
				if ($prt_name == $expected->getTestPRTName())
				{
					$expected_score->setValue($expected->getExpectedScore());
					break;
				}
			}
			$form->addItem($expected_score);
		}

		//Expected penalty
		$expected_penalty_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_expected_penalty'), 'expected_penalty_title');
		$form->addItem($expected_penalty_title);
		foreach ($question_prts as $prt_name => $q_prt)
		{
			$expected_penalty = new ilTextInputGUI($this->getPlugin()->txt('ut_expected_penalty_for') . ' ' . $prt_name, 'penalty_' . $prt_name);
			foreach ($unit_test->getTestExpected() as $prt => $expected)
			{
				if ($prt_name == $expected->getTestPRTName())
				{
					$expected_penalty->setValue($expected->getExpectedPenalty());
					break;
				}
			}
			$form->addItem($expected_penalty);
		}

		//Expected answernote
		$expected_answernote_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_expected_answer_note'), 'expected_answernote_title');
		$form->addItem($expected_answernote_title);
		foreach ($question_prts as $prt_name => $q_prt)
		{
			$expected_answernote = new ilTextInputGUI($this->getPlugin()->txt('ut_expected_answernote_for') . ' ' . $prt_name, 'answernote_' . $prt_name);
			foreach ($unit_test->getTestExpected() as $prt => $expected)
			{
				if ($prt_name == $expected->getTestPRTName())
				{
					$expected_answernote->setValue($expected->getExpectedAnswerNote());
					break;
				}
			}
			$form->addItem($expected_answernote);
		}

		//Commands
		$form->addCommandButton("doEditTestcase", $lng->txt('save'));
		$form->addCommandButton("showUnitTests", $lng->txt('cancel'));

		$body = $form->getHTML();
		$form_container->setBody($body);

		return $form_container->getHTML();
	}

	/**
	 * Returns the unit test creation form for the current question
	 * @param $question_id
	 * @return HTML
	 */
	public function createTestcaseForm($testcase_name, $question_inputs, $question_prts)
	{
		global $DIC;
		$lng = $DIC->language();
		$ctrl = $DIC->ctrl();

		include_once "Services/UIComponent/Panel/classes/class.ilPanelGUI.php";
		$form_container = ilPanelGUI::getInstance();
		$form_container->setPanelStyle(ilPanelGUI::PANEL_STYLE_PRIMARY);
		$form_container->setHeadingStyle(ilPanelGUI::PANEL_STYLE_SECONDARY);
		$form_container->setHeading($this->getPlugin()->txt("ut_testcase_name") . " " . $testcase_name);

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ctrl->getFormActionByClass('assStackQuestionGUI'));

		//Initialization
		$testcase_name_hidden = new ilHiddenInputGUI('testcase_name');
		$testcase_name_hidden->setValue($testcase_name);
		$form->addItem($testcase_name_hidden);

		//Student inputs
		$inputs_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_student_response'), 'inputs');
		$form->addItem($inputs_title);

		//Check all inputs are presented
		foreach ($question_inputs as $input_name => $q_input)
		{
			$input_field = new ilTextInputGUI($input_name, $input_name);
			$input_field->setValue("");
			$form->addItem($input_field);
		}

		//Expected score
		$expected_score_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_expected_mark'), 'expected_score_title');
		$form->addItem($expected_score_title);
		foreach ($question_prts as $prt_name => $q_prt)
		{
			$expected_score = new ilTextInputGUI($this->getPlugin()->txt('ut_expected_score_for') . ' ' . $prt_name, 'score_' . $prt_name);
			$expected_score->setValue("");
			$form->addItem($expected_score);
		}

		//Expected penalty
		$expected_penalty_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_expected_penalty'), 'expected_penalty_title');
		$form->addItem($expected_penalty_title);
		foreach ($question_prts as $prt_name => $q_prt)
		{
			$expected_penalty = new ilTextInputGUI($this->getPlugin()->txt('ut_expected_penalty_for') . ' ' . $prt_name, 'penalty_' . $prt_name);
			$expected_penalty->setValue("");
			$form->addItem($expected_penalty);
		}

		//Expected answernote
		$expected_answernote_title = new ilNonEditableValueGUI($this->getPlugin()->txt('ut_expected_answer_note'), 'expected_answernote_title');
		$form->addItem($expected_answernote_title);
		foreach ($question_prts as $prt_name => $q_prt)
		{
			$expected_answernote = new ilTextInputGUI($this->getPlugin()->txt('ut_expected_answernote_for') . ' ' . $prt_name, 'answernote_' . $prt_name);
			$expected_answernote->setValue("");
			$form->addItem($expected_answernote);
		}

		//Commands
		$form->addCommandButton("doCreateTestcase", $lng->txt('save'));
		$form->addCommandButton("showUnitTests", $lng->txt('cancel'));

		$body = $form->getHTML();
		$form_container->setBody($body);

		return $form_container->getHTML();
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param \ilassStackQuestionPlugin $plugin
	 */
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return \ilassStackQuestionPlugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param int $question_id
	 */
	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	/**
	 * @return int
	 */
	public function getQuestionId()
	{
		return $this->question_id;
	}

	/**
	 * @param \ilTemplate $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * @return \ilTemplate
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param mixed $tests
	 */
	public function setTests($tests)
	{
		$this->tests = $tests;
	}

	/**
	 * @return mixed
	 */
	public function getTests($selector = '')
	{
		if ($selector)
		{
			return $this->tests[$selector];
		} else
		{
			return $this->tests;
		}
	}

	/**
	 * @param mixed $unit_test_results
	 */
	public function setUnitTestResults($unit_test_results)
	{
		$this->unit_test_results = $unit_test_results;
	}

	/**
	 * @return mixed
	 */
	public function getUnitTestResults($selector = '')
	{
		if ($selector)
		{
			return $this->unit_test_results[$selector];
		} else
		{
			return $this->unit_test_results;
		}
	}


}