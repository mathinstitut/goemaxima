<?php

/**
 * Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once "./Modules/TestQuestionPool/classes/class.assQuestionGUI.php";
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';


/**
 * STACK Question GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$$
 * @ingroup    ModulesTestQuestionPool
 * @ilCtrl_isCalledBy assStackQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilCtrl_Calls assStackQuestionGUI: ilFormPropertyDispatchGUI
 *
 */
class assStackQuestionGUI extends assQuestionGUI
{
	protected $rte_module = "xqcas";
	protected $rte_tags = array();

	private $plugin;

	public function __construct($id = -1)
	{
		parent::__construct();

		//Set plugin object
		require_once "./Services/Component/classes/class.ilPlugin.php";
		$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assStackQuestion");

		$this->object = new assStackQuestion();
		if ($id >= 0)
		{
			$this->object->loadFromDb($id);
		}

		//Initialization and load of stack wrapper classes
		$this->plugin->includeClass('utils/class.assStackQuestionInitialization.php');
	}

	/**
	 * Init the STACK specific rich text editing support
	 * The allowed html tags are stored in an own settings module instead of "assessment"
	 * This enabled an independent tag set from the editor settings in ILIAS administration
	 * Text area fields will be initialized with SetRTESupport using this module
	 */
	public function initRTESupport()
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$this->rte_tags = ilObjAdvancedEditing::_getUsedHTMLTags($this->rte_module);

		$this->required_tags = array("a", "blockquote", "br", "cite", "code", "div", "em", "h1", "h2", "h3", "h4", "h5", "h6", "hr", "img", "li", "ol", "p", "pre", "span", "strike", "strong", "sub", "sup", "table", "caption", "thead", "th", "td", "tr", "u", "ul", "i", "b", "gap");

		if (serialize($this->rte_tags) != serialize(($this->required_tags)))
		{

			$this->rte_tags = $this->required_tags;
			$obj_advance = new ilObjAdvancedEditing();
			$obj_advance->setUsedHTMLTags($this->rte_tags, $this->rte_module);
		}
	}


	/**
	 * Set the STACK specific rich text editing support in textarea fields
	 * This uses an own module instead of "assessment" to determine the allowed tags
	 */
	public function setRTESupport(ilTextAreaInputGUI $field)
	{
		if (empty($this->rte_tags))
		{
			$this->initRTESupport();
		}
		$field->setUseRte(true);
		$field->setRteTags($this->rte_tags);
		$field->addPlugin("latex");
		$field->addButton("latex");
		$field->addButton("pastelatex");
		$field->setRTESupport($this->object->getId(), "qpl", $this->rte_module);
	}

	/**
	 * Get a list of allowed RTE tags
	 * This is used for ilUtil::stripSpashes() when saving the RTE fields
	 *
	 * @return string    allowed html tags, e.g. "<em><strong>..."
	 */
	public function getRTETags()
	{
		if (empty($this->rte_tags))
		{
			$this->initRTESupport();
		}

		return '<' . implode('><', $this->rte_tags) . '>';
	}


	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 * (called frm generic commands in assQuestionGUI)
	 *
	 * @return integer    0: question can be saved / 1: form is not complete
	 */
	public function writePostData($always = FALSE)
	{

		$hasErrors = (!$always) ? $this->editQuestion(TRUE) : FALSE;
		if (!$hasErrors)
		{
			$this->deletionManagement();
			$this->writeQuestionGenericPostData();
			$this->writeQuestionSpecificPostData();

			// save the taxonomy assignments
			// a checkInput() is needed on the taxonomy inputs
			// otherwise a reset of taxonomy assignmentd will prodice an error
			require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
			$form = new ilPropertyFormGUI();
			$this->populateTaxonomyFormSection($form);
			$form->checkInput();
			$this->saveTaxonomyAssignments();

			//Get errors from authoring
			$this->getErrors();

			return 0;
		}

		return 1;
	}

	public function deletionManagement()
	{
		global $DIC;
		$lng = $DIC->language();

		if (is_array($_POST['cmd']['save']))
		{
			foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt)
			{
				if (isset($_POST['cmd']['save']['delete_full_prt_' . $prt_name]))
				{
					if ($this->checkPRTForDeletion($prt))
					{
						return FALSE;
					}
					$prt->delete();
					$ptrs = $this->object->getPotentialResponsesTrees();
					unset($ptrs[$prt_name]);
					$this->object->setPotentialResponsesTrees($ptrs);

					//#18703 Should delete also nodes
					foreach ($prt->getPRTNodes() as $node_name => $node)
					{
						$node->delete();
					}

					return TRUE;
				}
				foreach ($prt->getPRTNodes() as $node_name => $node)
				{

					if (isset($_POST['cmd']['save']['delete_prt_' . $prt_name . '_node_' . $node->getNodeName()]))
					{
						if ($this->checkPRTNodeForDeletion($prt, $node))
						{
							return FALSE;
						}
						$node->delete();
						$nodes = $prt->getPRTNodes();
						unset($nodes[$node_name]);
						$prt->setPRTNodes($nodes);
						$this->object->setPotentialResponsesTrees($prt, $prt_name);

						return TRUE;
					}

					//Copy Node
					if (isset($_POST['cmd']['save']['copy_prt_' . $prt_name . '_node_' . $node->getNodeName()]))
					{
						//Do node copy here
						$_SESSION['copy_node'] = $this->object->getId() . "_" . $prt_name . "_" . $node->getNodeName();
						ilUtil::sendInfo($lng->txt("qpl_qst_xqcas_node_copied_to_clipboard"), TRUE);

						return TRUE;
					}

					//Paste Node
					if (isset($_POST['cmd']['save']['paste_node_in_' . $prt_name]))
					{
						//Do node paste here
						$raw_data = explode("_", $_SESSION['copy_node']);
						$paste_question_id = $raw_data[0];
						$paste_prt_name = $raw_data[1];
						$paste_node_name = $raw_data[2];

						$paste_prt_node_list = assStackQuestionPRTNode::_read($paste_question_id, $paste_prt_name);
						$paste_node = $paste_prt_node_list[$paste_node_name];

						//Change values
						if (is_a($paste_node, "assStackQuestionPRTNode"))
						{
							$paste_node->setNodeId("");
							$paste_node->setQuestionId($this->object->getId());
							$paste_node->setPRTName($prt_name);
							$paste_node->setNodeName((string)$prt->getLastNodeName() + 1);
							$paste_node->setTrueNextNode("");
							$paste_node->setFalseNextNode("");

							$paste_node->save();

							unset($_SESSION['copy_node']);
							ilUtil::sendInfo($lng->txt("qpl_qst_xqcas_node_paste"), TRUE);
						}

					}
				}

				//PRT COpy

				if (isset($_POST['cmd']['save']['copy_prt_' . $prt_name]))
				{
					//Do node copy here
					$_SESSION['copy_prt'] = $this->object->getId() . "_" . $prt_name;
					ilUtil::sendInfo($lng->txt("qpl_qst_xqcas_prt_copied_to_clipboard"), TRUE);


					return TRUE;
				}

				//Paste Node
				if (isset($_POST['cmd']['save']['paste_prt']))
				{
					$raw_data = explode("_", $_SESSION['copy_prt']);
					$paste_question_id = $raw_data[0];
					$paste_prt_name = $raw_data[1];

					$generated_prt_name = "prt" . rand(0, 1000);
					$paste_prt_list = assStackQuestionPRT::_read($paste_question_id);
					$paste_prt = $paste_prt_list[$paste_prt_name];

					if (is_a($paste_prt, 'assStackQuestionPRT'))
					{
						$paste_prt->setPRTId(-1);
						$paste_prt->setQuestionId($this->object->getId());
						$paste_prt->setPRTName($generated_prt_name);
						$paste_prt->save();

						foreach ($paste_prt->getPRTNodes() as $prt_node)
						{
							if (is_a($prt_node, 'assStackQuestionPRTNode'))
							{
								$prt_node->setNodeId(-1);
								$prt_node->setQuestionId($this->object->getId());
								$prt_node->setPRTName($generated_prt_name);
								$prt_node->save();
							}
						}
						//Solve #26077
						//Include placeholder in specific feedback
						$current_specific_feedback = $this->object->getOptions()->getSpecificFeedback();
						$new_specific_feedback = "<p>" . $current_specific_feedback . "[[feedback:" . $generated_prt_name . "]]</p>";
						$_POST["options_specific_feedback"] = $new_specific_feedback;
					}
					unset($_SESSION['copy_prt']);
					ilUtil::sendInfo($lng->txt("qpl_qst_xqcas_prt_paste"), TRUE);

				}
			}
		}

		return TRUE;
	}


	public function checkPRTForDeletion(assStackQuestionPRT $prt)
	{
		if (sizeof($this->object->getPotentialResponsesTrees()) < 2)
		{
			$this->object->setErrors($this->object->getPlugin()->txt('deletion_error_not_enought_prts'));

			return TRUE;
		}

		return FALSE;
	}

	public function checkPRTNodeForDeletion(assStackQuestionPRT $prt, assStackQuestionPRTNode $node)
	{

		if (sizeof($prt->getPRTNodes()) < 2)
		{
			$this->object->setErrors($this->object->getPlugin()->txt('deletion_error_not_enought_prt_nodes'));

			return TRUE;
		}

		if ((int)$prt->getFirstNodeName() == (int)$node->getNodeName())
		{
			$this->object->setErrors($this->object->getPlugin()->txt('deletion_error_first_node'));

			return TRUE;
		}

		foreach ($prt->getPRTNodes() as $prt_node)
		{
			if ($prt_node->getTrueNextNode() == $node->getNodeName() OR $prt_node->getFalseNextNode() == $node->getNodeName())
			{
				$this->object->setErrors($this->object->getPlugin()->txt('deletion_error_connected_node'));

				return TRUE;
			}
		}

		return FALSE;
	}

	public function writeQuestionSpecificPostData()
	{
		//OPTIONS
		$this->object->getOptions()->writePostData($this->getRTETags());
		$this->object->getExtraInfo()->writePostData($this->getRTETags());

		//INPUTS
		/*
		 * Management of Input addition and deletion done here
		 * In STACK new inputs are created if a placeholder in question text exist so, addition and deletion must be managed here.
		 */
		$text_inputs = stack_utils::extract_placeholders($this->object->getQuestion(), 'input');

		//Edition and Deletion of inputs
		foreach ($this->object->getInputs() as $input_name => $input)
		{
			if (in_array($input_name, $text_inputs))
			{
				//Check if there exists placeholder in text
				if (isset($_POST[$input_name . '_input_type']))
				{
					$input->writePostData($input_name);
				}
			} else
			{
				//If doesn' exist, check if must be deleted
				if (sizeof($this->object->getInputs()) < 2)
				{
					//If there are less than two inputs you cannot delete it
					//Add placeholder to question text
					$this->object->setQuestion($this->object->getQuestion() . " [[input:{$input_name}]]  [[validation:{$input_name}]]");
				} else
				{
					//Delete input from object
					$db_inputs = $this->object->getInputs();
					unset($db_inputs[$input_name]);
					$this->object->setInputs($db_inputs);
					//Delete input from DB
					$input->delete();
				}
			}
		}
		//Addition of inputs
		foreach ($text_inputs as $input_name)
		{
			if (is_null($this->object->getInputs($input_name)))
			{
				//Create new Input
				$new_input = new assStackQuestionInput(-1, $this->object->getId(), $input_name, 'algebraic', "");
				$new_input->getDefaultInput();
				$new_input->checkInput(TRUE);
				$new_input->save();
				$this->object->setInputs($input, $input_name);

				//$this->object->setErrors(array("new_input" => $this->object->getPlugin()->txt("new_input_info_message")));
			}
		}

		//PRT
		if (is_array($this->object->getPotentialResponsesTrees()))
		{
			foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt)
			{
				if (isset($_POST['prt_' . $prt_name . '_value']))
				{
					$prt->writePostData($prt_name, "", $this->getRTETags());
				}
				//Add new node if info is filled in
				if ($_POST['prt_' . $prt->getPRTName() . '_node_' . $prt->getPRTName() . '_new_node_student_answer'] != "" AND $_POST['prt_' . $prt->getPRTName() . '_node_' . $prt->getPRTName() . '_new_node_teacher_answer'] != "")
				{
					$new_node = new assStackQuestionPRTNode(-1, $this->object->getId(), $prt->getPRTName(), $prt->getLastNodeName() + 1, $_POST['prt_' . $prt->getPRTName() . '_node_' . $prt->getPRTName() . '_new_node_pos_next'], $_POST['prt_' . $prt->getPRTName() . '_node_' . $prt->getPRTName() . '_new_node_neg_next']);
					$new_node->writePostData($prt_name, $prt_name . '_new_node', "", $new_node->getNodeName(), $this->getRTETags());
				}
			}
		}

		//Addition of PRT and Nodes
		//New PRT (and node) if the new prt is filled
		if (isset($_POST['prt_new_prt_name']) AND $_POST['prt_new_prt_name'] != 'new_prt' AND !preg_match('/\s/', $_POST['prt_new_prt_name']))
		{
			//the prt name given is not used in this question
			$new_prt = new assStackQuestionPRT(-1, $this->object->getId());
			$new_prt_node = new assStackQuestionPRTNode(-1, $this->object->getId(), ilUtil::stripSlashes($_POST['prt_new_prt_name']), '0', -1, -1);
			$new_prt->setPRTNodes(array('0' => $new_prt_node));
			$new_prt->writePostData('new_prt', ilUtil::stripSlashes($_POST['prt_new_prt_name']), $this->getRTETags());

			//Add new Token
			$specific_feedback = $this->object->getOptions()->getSpecificFeedback();
			$specific_feedback .= "<p>[[feedback:" . ilUtil::stripSlashes($_POST['prt_new_prt_name']) . "]]</p>";
			$this->object->getOptions()->setSpecificFeedback($specific_feedback);
		}

		if (preg_match('/\s/', $_POST['prt_new_prt_name']))
		{
			$this->question_gui->object->setErrors($this->object->getPlugin()->txt('error_not_valid_prt_name'));

			return FALSE;
		}

	}

	/*
	 * DISPLAY METHODS
	 */

	/**
	 * Show question preview for test and question pools
	 * @param bool $show_question_only
	 * @param bool $showInlineFeedback
	 * @return string HTML Preview of the question in a question pool or in a test
	 */
	public function getPreview($show_question_only = FALSE, $showInlineFeedback = false)
	{
		global $DIC, $tpl;

		$tabs = $DIC->tabs();
		//Get solutions if given
		$solutions = is_object($this->getPreviewSession()) ? (array)$this->getPreviewSession()->getParticipantsSolution() : array();

		//Include preview classes and set tab
		$this->plugin->includeClass("model/question_display/class.assStackQuestionPreview.php");
		$this->plugin->includeClass("GUI/question_display/class.assStackQuestionPreviewGUI.php");

		//Tab management
		if ($_GET['cmd'] == 'edit')
		{
			$tabs->setTabActive('edit_page');
		} elseif ($_GET['cmd'] == 'preview')
		{
			$tabs->setTabActive('preview');
		}

		//Seed management
		if (isset($_REQUEST['fixed_seed']))
		{
			$seed = $_REQUEST['fixed_seed'];
			$_SESSION['q_seed_for_preview_' . $this->object->getId() . ''] = $seed;
		} else
		{
			if (isset($_SESSION['q_seed_for_preview_' . $this->object->getId() . '']))
			{
				$seed = $_SESSION['q_seed_for_preview_' . $this->object->getId() . ''];
			} else
			{
				$seed = -1;
			}
		}

		//Get question preview data
		$question_preview_object = new assStackQuestionPreview($this->plugin, $this->object, $seed, $solutions);
		$question_preview_data = $question_preview_object->getQuestionPreviewData();

		//Get question preview GUI
		$question_preview_gui_object = new assStackQuestionPreviewGUI($this->plugin, $question_preview_data);
		$question_preview_gui = $question_preview_gui_object->getQuestionPreviewGUI();


		//Set preview mode
		$this->preview_mode = $question_preview_data;

		//addCSS
		$tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_question_feedback.css'));
		$tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_question_preview.css'));
		$tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_question_display.css'));

		$questionoutput = $question_preview_gui->get();
		//Returns output (with page if needed)
		if (!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}

		return $questionoutput;
	}

	/**
	 * Get the HTML output of the question for a test
	 * @param int $active_id
	 * @param int $pass
	 * @param bool $is_question_postponed
	 * @param bool $user_post_solutions
	 * @param $show_specific_inline_feedback
	 * @return mixed|string
	 */
	public function getTestOutput($active_id, $pass = NULL, $is_question_postponed = FALSE, $user_post_solutions = FALSE, $show_specific_inline_feedback)
	{
		$solutions = NULL;
		// get the solution of the user for the active pass or from the last pass if allowed
		if ($active_id)
		{

			require_once './Modules/Test/classes/class.ilObjTest.php';
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass))
				{
					$pass = ilObjTest::_getPass($active_id);
				}
			}#

			//If ILIAS 5.1  or 5.0 using intermediate
			if (method_exists($this->object, "getUserSolutionPreferingIntermediate"))
			{
				$solutions = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass);
			} else
			{
				$solutions =& $this->object->getSolutionValues($active_id, $pass);
			}
		}
		//Create STACK Question object if doesn't exists
		if (!is_a($this->object->getStackQuestion(), 'assStackQuestionStackQuestion'))
		{
			//Determine seed for current test run
			$seed = $this->object->getQuestionSeedForCurrentTestRun($active_id, $pass);

			$this->plugin->includeClass("model/class.assStackQuestionStackQuestion.php");
			$this->object->setStackQuestion(new assStackQuestionStackQuestion($active_id, $pass));
			$this->object->getStackQuestion()->init($this->object, '', $seed);
		}

		//Generate the question output and filling output template with question output and page output.
		$question_output = $this->getTestQuestionOutput($solutions, $show_specific_inline_feedback);
		$page_output = $this->outQuestionPage("", $is_question_postponed, $active_id, $question_output);

		return $page_output;
	}

	/**
	 * Test view for STACK Questions
	 * @param mixed $solutions
	 * @param bool $show_specific_inline_feedback
	 * @return mixed
	 * @throws stack_exception
	 */
	public function getTestQuestionOutput($solutions, $show_specific_inline_feedback)
	{
		global $tpl;
		//Create feedback output from feedback class
		$this->plugin->includeClass("GUI/question_display/class.assStackQuestionFeedbackGUI.php");
		$question_feedback_object = new assStackQuestionFeedbackGUI($this->plugin, $solutions);
		$feedback_data = $question_feedback_object->getFeedback();
		//Include display classes
		$this->plugin->includeClass("model/question_display/class.assStackQuestionDisplay.php");
		$this->plugin->includeClass("GUI/question_display/class.assStackQuestionDisplayGUI.php");
		//Get question display data
		$tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_question_display.css'));
		$value_format_user_response = assStackQuestionUtils::_getUserResponse($this->object->getId(), $this->object->getStackQuestion()->getInputs(), $feedback_data);
		$question_display_object = new assStackQuestionDisplay($this->plugin, $this->object->getStackQuestion(), $value_format_user_response, $feedback_data);
		$question_display_data = $question_display_object->getQuestionDisplayData(TRUE);
		//Get question display GUI
		$question_display_gui_object = new assStackQuestionDisplayGUI($this->plugin, $question_display_data);
		$question_display_gui = $question_display_gui_object->getQuestionDisplayGUI($show_specific_inline_feedback);
		//fill question container with HTML from assStackQuestionDisplay
		$container_tpl = $this->plugin->getTemplate("tpl.il_as_qpl_xqcas_question_container.html");
		$container_tpl->setVariable('QUESTION', $question_display_gui->get());
		$question_output = $container_tpl->get();

		return $question_output;
	}

	/**
	 * Get the question solution output
	 *
	 * @param integer $active_id The active user id
	 * @param integer $pass The test pass
	 * @param boolean $graphicalOutput Show visual feedback for right/wrong answers
	 * @param boolean $result_output Show the reached points for parts of the question
	 * @param boolean $show_question_only Show the question without the ILIAS content around
	 * @param boolean $show_feedback Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring Show specific information for the manual scoring output
	 * @param boolean $show_question_text
	 * @return string The solution output of the question as HTML code
	 */
	function getSolutionOutput($active_id, $pass = NULL, $graphicalOutput = FALSE, $result_output = FALSE, $show_question_only = TRUE, $show_feedback = TRUE, $show_correct_solution = FALSE, $show_manual_scoring = FALSE, $show_question_text = TRUE)
	{
		$solution_template = new ilTemplate("tpl.il_as_tst_solution_output.html", TRUE, TRUE, "Modules/TestQuestionPool");
		//Check for PASS
		if ($active_id)
		{

			require_once './Modules/Test/classes/class.ilObjTest.php';
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass))
				{
					$pass = ilObjTest::_getPass($active_id);
				}
			}
		}

		//Is preview or Test
		if (is_array($this->preview_mode))
		{
			$solutions = $this->preview_mode["question_feedback"];
		} else
		{
			//If ILIAS 5.1  or 5.0 using intermediate
			if (method_exists($this->object, "getUserSolutionPreferingIntermediate"))
			{
				$solutions = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass);
			} else
			{
				$solutions =& $this->object->getSolutionValues($active_id, $pass);
			}
		}

		if (($active_id > 0) && (!$show_correct_solution))
		{
			//User Solution
			//Returns user solution HTML
			$solution_output = $this->getQuestionOutput($solutions, FALSE, $show_feedback, TRUE);
			//2.3.12 add feedback to solution
			$solution_output .= $this->getSpecificFeedbackOutput($active_id, $pass);

		} else
		{
			//Correct solution
			//Returns best solution HTML.
			$solution_output = $this->getQuestionOutput($solutions, TRUE, $show_feedback);
		}

		$question_text = $this->object->getQuestion();
		if ($show_question_text == true)
		{
			$solution_template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question_text, TRUE));
		}


		//Bug 0020117 regarding feedback
		//Feedback in STACK works in a different way
		/*
		$feedback = '';
		if ($show_feedback)
		{
			if (!$this->isTestPresentationContext())
			{
				$fb = $this->getGenericFeedbackOutput($active_id, $pass);
				$feedback .= strlen($fb) ? $fb : '';
			}

			$fb = $this->getSpecificFeedbackOutput($active_id, $pass);
			$feedback .= strlen($fb) ? $fb : '';
		}
		if (strlen($feedback))
		{
			//$solution_template->setVariable("FEEDBACK", $this->object->prepareTextareaOutput($feedback, true));
		}
		*/

		//2.4.0 Print view on test, just show the questions
		if ($active_id == "" AND $pass == "" AND $_REQUEST["cmd"] == "print")
		{
			return $this->getQuestionOutput($solutions, FALSE, $show_feedback);
		}

		$solution_template->setVariable("SOLUTION_OUTPUT", $solution_output);

		$solution_output = $solution_template->get();
		if (!$show_question_only)
		{
			// get page object output
			$solution_output = $this->getILIASPage($solution_output);
		}

		return $solution_output;
	}

	/**
	 * Shows the question filled in with the user response or the best solution for feedback.
	 * @param $solutions array with Solution from DB or from Preview
	 * @param $best_solution TRUE is best solution must be shown.
	 * @param bool $show_feedback TRUE if specific feedback per PRT must be shown.
	 * @return string
	 */
	public function getQuestionOutput($solutions, $best_solution, $show_feedback, $just_show = FALSE)
	{
		if (isset($solutions["question_text"]) AND strlen($solutions["question_text"]))
		{
			$question_text = $solutions["question_text"];

			//Get Model answer from solutions and replace placeholders
			if (isset($solutions["prt"]))
			{
				foreach ($solutions["prt"] as $prt_name => $prt)
				{
					if (isset($prt["response"]))
					{
						foreach ($prt["response"] as $input_name => $input_answer)
						{
							//Get input type for showing it properly
							$input = $this->object->getInputs($input_name);

							//Replace input depending on input type
							switch ($input->getInputType())
							{
								case "dropdown":
								case "checkbox":
								case "radio":
									if ($best_solution)
									{
										$input_replacement = $input_answer["model_answer"];
										$validation_replacement = $input_answer["model_answer_display"];
										$question_text = str_replace("[[input:" . $input_name . "]]", $input_replacement, $question_text);
										$question_text = str_replace("[[validation:" . $input_name . "]]", $validation_replacement, $question_text);
									} else
									{
										if ($just_show)
										{
											$input_replacement = "</br>" . $input_answer["display"];
											$question_text = str_replace("[[validation:" . $input_name . "]]", "", $question_text);

										} else
										{
											$input_replacement = $input_answer["value"];
											$question_text = str_replace("[[validation:" . $input_name . "]]", $this->object->getStackQuestion()->getInputs($input_name)->render_validation($this->object->getStackQuestion()->getInputState($input_name, $input_replacement), $input_name), $question_text);
										}
									}

									$question_text = str_replace("[[input:" . $input_name . "]]", $input_replacement, $question_text);
									break;
								case "matrix":
									//Select replace depending on mode if $best_solution is TRUE, best solution when FALSE user solution.
									if ($best_solution)
									{
										$input_replacement = $input_answer["model_answer"];
										$validation_replacement = $input_answer["model_answer_display"];
										$question_text = str_replace("[[input:" . $input_name . "]]", $input_replacement, $question_text);
										$question_text = str_replace("[[validation:" . $input_name . "]]", $validation_replacement, $question_text);
									} else
									{
										$input_replacement = $input_answer["display"];
									}
									$question_text = str_replace("[[input:" . $input_name . "]]", $input_replacement, $question_text);
									break;
								case "textarea";
								case "equiv";
									if ($best_solution)
									{
										$input_replacement = $input_answer["model_answer"];
										$validation_replacement = $input_answer["model_answer_display"];
										$question_text = str_replace("[[input:" . $input_name . "]]", $input_replacement, $question_text);
										$question_text = str_replace("[[validation:" . $input_name . "]]", $validation_replacement, $question_text);
									} else
									{
										$input_replacement = "<textarea rows=\"4\" cols=\"50\">" . $input_answer["value"]. "</textarea>";
									}
									$size = $input->getBoxSize();
									$input_text = "";
									$input_text .= $input_replacement;
									$question_text = str_replace("[[input:" . $input_name . "]]", $input_text, $question_text);
									break;
								default:
									if ($best_solution)
									{
										$input_replacement = $input_answer["model_answer"];
										$validation_replacement = $input_answer["model_answer_display"];
										$question_text = str_replace("[[input:" . $input_name . "]]", $input_replacement, $question_text);
										$question_text = str_replace("[[validation:" . $input_name . "]]", $validation_replacement, $question_text);
									} else
									{
										$input_replacement = $input_answer["value"];
										if ($show_feedback)
										{
											if (strlen($input_answer["display"]))
											{
												$validation_replacement = stack_string('studentValidation_yourLastAnswer', $input_answer["display"]);
											} else
											{
												$validation_replacement = $input_answer["display"];
											}
											$question_text = str_replace("[[validation:" . $input_name . "]]", $validation_replacement, $question_text);
										}
									}
									$size = strlen($input_replacement) + 5;
									$input_html_display = '<input type="text" size="' . $size . '" value="' . $input_replacement . '" disabled="disabled">';

									$question_text = str_replace("[[input:" . $input_name . "]]", $input_html_display, $question_text);
									break;
							}


							//Replace feedback placeholder if required
							if ($show_feedback)
							{
								$string = "";
								//feedback
								$string .= '<div class="alert alert-warning" role="alert">';
								//Generic feedback
								$string .= $prt["status"]["message"];
								//$string .= '<br>';
								//Specific feedback
								$string .= $prt["feedback"];
								$string .= $prt["errors"];
								$string .= '</div>';

								$question_text = str_replace("[[feedback:" . $prt_name . "]]", $string, $question_text);
							}
						}
					}

				}
			}

			//Delete other place holders
			$question_text = preg_replace('/\[\[validation:(.*?)\]\]/', "", $question_text);
			if (!$show_feedback)
			{
				$question_text = preg_replace('/\[\[feedback:(.*?)\]\]/', "", $question_text);
			}

			if ($best_solution)
			{
				$question_text .= $solutions["general_feedback"];
			}

			//Return the question text with LaTeX problems solved.
			return assStackQuestionUtils::_getLatex($question_text);
		} else
		{
			return "";
		}
	}

	/**
	 * Return the specific feedback
	 * @param int $active_id
	 * @param int $pass
	 * @return string
	 **/
	public function getSpecificFeedbackOutput($active_id, $pass)
	{
		//Check for PASS
		if ($active_id)
		{
			require_once './Modules/Test/classes/class.ilObjTest.php';
			if (!ilObjTest::_getUsePreviousAnswers($active_id, true))
			{
				if (is_null($pass))
				{
					$pass = ilObjTest::_getPass($active_id);
				}
			}
		}
		//Is preview or Test
		if (is_array($this->preview_mode))
		{
			$solutions = $this->preview_mode["question_feedback"];
		} else
		{
			//If ILIAS 5.1  or 5.0 using intermediate
			if (method_exists($this->object, "getUserSolutionPreferingIntermediate"))
			{
				$solutions = $this->object->getUserSolutionPreferingIntermediate($active_id, $pass);
			} else
			{
				$solutions =& $this->object->getSolutionValues($active_id, $pass);
			}
		}
		$specific_feedback = $this->object->getOptions()->getSpecificFeedback();
		//Search for feedback placeholders in specific feedback text.
		foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt)
		{
			if (preg_match("[[feedback:" . $prt_name . "]]", $specific_feedback))
			{
				if (isset($solutions["prt"][$prt_name]))
				{
					$string = "";
					//feedback
					$string .= '<div class="alert alert-warning" role="alert">';
					//Generic feedback
					$string .= $solutions["prt"][$prt_name]['status']['message'];
					//$string .= '<br>';
					//Specific feedback
					$string .= $solutions["prt"][$prt_name]["feedback"];
					$string .= $solutions["prt"][$prt_name]["errors"];
					$string .= '</div>';
					$specific_feedback = str_replace("[[feedback:" . $prt_name . "]]", $string, $specific_feedback);
				} else
				{
					$specific_feedback = str_replace("[[feedback:" . $prt_name . "]]", $this->object->getPlugin()->txt("preview_no_answer"), $specific_feedback);
				}
			}
		}

		//Return the question text with LaTeX problems solved.
		return assStackQuestionUtils::_getLatex($specific_feedback);
	}

	/**
	 * Returns the answer generic feedback depending on the results of the question
	 *
	 * @deprecated Use getGenericFeedbackOutput instead.
	 * @param integer $active_id Active ID of the user
	 * @param integer $pass Active pass
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	function getAnswerFeedbackOutput($active_id, $pass)
	{
		return $this->getGenericFeedbackOutput($active_id, $pass);
	}


	/*
	 * TABS MANAGEMENT
	 */

	/**
	 * Sets the ILIAS tabs for this question type
	 * called from ilObjTestGUI and ilObjQuestionPoolGUI
	 */
	public function setQuestionTabs()
	{
		global $DIC, $rbacsystem;

		$tabs = $DIC->tabs();

		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$this->plugin->includeClass('class.ilAssStackQuestionFeedback.php');

		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
		}

		if ($_GET["q_id"])
		{
			if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
			{
				// edit page
				$tabs->addTarget("edit_page", $this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"), array("edit", "insert", "exec_pg"), "", "", "");
			}

			// edit page
			$tabs->addTarget("preview", $this->ctrl->getLinkTargetByClass("ilAssQuestionPreviewGUI", "show"), array("preview"), "ilAssQuestionPageGUI", "", "");
		}

		$force_active = false;
		if ($rbacsystem->checkAccess('write', $_GET["ref_id"]))
		{
			$url = "";

			if ($classname)
			{
				$url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
			}
			$commands = $_POST["cmd"];
			if (is_array($commands))
			{
				foreach ($commands as $key => $value)
				{
					if (preg_match("/^suggestrange_.*/", $key, $matches))
					{
						$force_active = true;
					}
				}
			}
			// edit question properties
			$tabs->addTarget("edit_properties", $url, array("editQuestion", "save", "cancel", "addSuggestedSolution", "cancelExplorer", "linkChilds", "removeSuggestedSolution", "parseQuestion", "saveEdit", "suggestRange"), $classname, "", $force_active);

			$this->addTab_QuestionFeedback($tabs);

			if (in_array($_GET['cmd'], array('importQuestionFromMoodleForm', 'importQuestionFromMoodle', 'editQuestion', 'scoringManagement', 'scoringManagementPanel', 'deployedSeedsManagement', 'createNewDeployedSeed', 'deleteDeployedSeed', 'showUnitTests', 'runTestcases', 'createTestcases', 'post', 'exportQuestiontoMoodleForm', 'exportQuestionToMoodle',)))
			{
				$tabs->addSubTab('edit_question', $this->plugin->txt('edit_question'), $this->ctrl->getLinkTargetByClass($classname, "editQuestion"));
				$tabs->addSubTab('scoring_management', $this->plugin->txt('scoring_management'), $this->ctrl->getLinkTargetByClass($classname, "scoringManagementPanel"));
				$tabs->addSubTab('deployed_seeds_management', $this->plugin->txt('dsm_deployed_seeds'), $this->ctrl->getLinkTargetByClass($classname, "deployedSeedsManagement"));
				$tabs->addSubTab('unit_tests', $this->plugin->txt('ut_title'), $this->ctrl->getLinkTargetByClass($classname, "showUnitTests"));
				$tabs->addSubTab('import_from_moodle', $this->plugin->txt('import_from_moodle'), $this->ctrl->getLinkTargetByClass($classname, "importQuestionFromMoodleForm"));
				$tabs->addSubTab('export_to_moodle', $this->plugin->txt('export_to_moodle'), $this->ctrl->getLinkTargetByClass($classname, "exportQuestiontoMoodleForm"));
			}

		}

		// Assessment of questions sub menu entry
		if ($_GET["q_id"])
		{
			$tabs->addTarget("statistics", $this->ctrl->getLinkTargetByClass($classname, "assessment"), array("assessment"), $classname, "");
		}

		if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0))
		{
			$ref_id = $_GET["calling_test"];
			if (strlen($ref_id) == 0)
			{
				$ref_id = $_GET["test_ref_id"];
			}
			$tabs->setBackTarget($this->lng->txt("backtocallingtest"), "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id");
		} else
		{
			$tabs->setBackTarget($this->lng->txt("qpl"), $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions"));
		}

	}

	/*
	 * COMMANDS METHODS
	 */

	/*
	 * EDITION/CREATION OF QUESTIONS
	 */

	/**
	 * This method has been modified for authoring interface creation in version 1.6.2
	 */
	public function editQuestionForm()
	{
		global $DIC;

		if ($this->object->getSelfAssessmentEditingMode())
		{
			$this->getLearningModuleTabs();
		}

		//QP
		$tabs = $DIC->tabs();

		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('edit_question');
		$this->getQuestionTemplate();

		//Create GUI object
		$this->plugin->includeClass('GUI/question_authoring/class.assStackQuestionAuthoringGUI.php');
		$authoring_gui = new assStackQuestionAuthoringGUI($this->plugin, $this);
		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_authoring.css'));
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/multipart_form.css'));

		//Javascript

		//Show info messages
		$this->info_config = new stdClass();
		$ctrl = $DIC->ctrl();
		$this->info_config->ajax_url = $ctrl->getLinkTargetByClass("assstackquestiongui", "saveInfoState", "", TRUE);

		//Set to user's session value
		if (isset($_SESSION['stack_authoring_show']))
		{
			$this->info_config->show = (int)$_SESSION['stack_authoring_show'];
		} else
		{
			//first time must be shown
			$this->info_config->show = 1;
		}
		$this->tpl->addJavascript('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/js/ilEnableDisableInfo.js');
		$this->tpl->addOnLoadCode('il.EnableDisableInfo.initInfoMessages(' . json_encode($this->info_config) . ')');

		//Reform authoring interface
		$this->tpl->addJavascript('Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/js/ilMultipartFormProperty.js');

		//Returns Deployed seeds form
		$this->tpl->setVariable("QUESTION_DATA", $authoring_gui->showAuthoringPanel());

	}


	/**
	 * Populate taxonomy section in a form
	 * (made public to be called from authoring GUI)
	 * @param ilPropertyFormGUI $form
	 */
	public function populateTaxonomyFormSection(ilPropertyFormGUI $form)
	{
		parent::populateTaxonomyFormSection($form);
	}

	public function editQuestion($checkonly = FALSE)
	{
		$save = $this->isSaveCommand();

		$this->editQuestionForm();
	}

	public function enableDisableInfo()
	{
		if (isset($_SESSION['show_input_info_fields_in_form']))
		{
			if ($_SESSION['show_input_info_fields_in_form'] == TRUE)
			{
				$_SESSION['show_input_info_fields_in_form'] = FALSE;
			} else
			{
				$_SESSION['show_input_info_fields_in_form'] = TRUE;
			}
		} else
		{
			$_SESSION['show_input_info_fields_in_form'] = TRUE;
		}

		$this->editQuestionForm();
	}

	/*
	 * DEPLOYED SEEDS METHODS
	 */

	public function deployedSeedsManagement()
	{
		global $DIC;
		$tabs = $DIC->tabs();

		if ($this->object->getSelfAssessmentEditingMode())
		{
			$this->getLearningModuleTabs();
		}
		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('deployed_seeds_management');
		$this->getQuestionTemplate();

		//Create GUI object
		$this->plugin->includeClass('GUI/question_authoring/class.assStackQuestionDeployedSeedsGUI.php');
		$deployed_seeds_gui = new assStackQuestionDeployedSeedsGUI($this->plugin, $this->object->getId(), $this);

		//Add MathJax (Ensure MathJax is loaded)
		include_once "./Services/Administration/classes/class.ilSetting.php";
		$mathJaxSetting = new ilSetting("MathJax");
		$this->tpl->addJavaScript($mathJaxSetting->get("path_to_mathjax"));

		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_deployed_seeds_management.css'));

		//Returns Deployed seeds form
		$this->tpl->setVariable("QUESTION_DATA", $deployed_seeds_gui->showDeployedSeedsPanel());
	}

	public function createNewDeployedSeed()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('deployed_seeds_management');
		$this->getQuestionTemplate();

		//New seed creation
		$seed = (int)$_POST['deployed_seed'];
		$question_id = (int)$_POST['question_id'];

		$this->plugin->includeClass('model/ilias_object/class.assStackQuestionDeployedSeed.php');
		$deployed_seed = new assStackQuestionDeployedSeed('', $question_id, $seed);
		if (!$deployed_seed->save())
		{
			$this->question_gui->object->setErrors($this->plugin->txt('dsm_not_allowed_seed'));
		}

		$this->deployedSeedsManagement();
	}

	public function deleteDeployedSeed()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('deployed_seeds_management');
		$this->getQuestionTemplate();

		//New seed creation
		$seed = $_POST['deployed_seed'];
		$question_id = $_POST['question_id'];

		$this->plugin->includeClass('model/ilias_object/class.assStackQuestionDeployedSeed.php');
		$deployed_seeds = assStackQuestionDeployedSeed::_read($question_id);
		foreach ($deployed_seeds as $deployed_seed)
		{
			if ($deployed_seed->getSeed() == $seed)
			{
				$deployed_seed->delete();
				ilUtil::sendSuccess($this->plugin->txt('dsm_deployed_seed_deleted'));
				break;
			}
		}

		$this->deployedSeedsManagement();
	}

	/*
	 * SCORING MANAGEMENT
	 */

	/**
	 * This function is called when scoring tab is activated.
	 * Shows the evaluation structure of the question by potentialresponse tree and a simulation
	 * of the value of each PRT in real points, in order to change it.
	 * @param float $new_question_points
	 */
	public function scoringManagementPanel($new_question_points = '')
	{
		global $DIC;
		$tabs = $DIC->tabs();
		if ($this->object->getSelfAssessmentEditingMode())
		{
			$this->getLearningModuleTabs();
		}
		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('scoring_management');
		$this->getQuestionTemplate();

		//Create GUI object
		$this->plugin->includeClass('GUI/question_authoring/class.assStackQuestionScoringGUI.php');
		$scoring_gui = new assStackQuestionScoringGUI($this->plugin, $this->object->getId(), $this->object->getPoints());

		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_scoring_management.css'));

		//Returns Deployed seeds form
		$this->tpl->setVariable("QUESTION_DATA", $scoring_gui->showScoringPanel($new_question_points));
	}

	/**
	 * This command is called when user requires a comparison between current evaluation
	 * structure and a new one with the point value he insert in the input field.
	 */
	public function showScoringComparison()
	{
		//Get new points value
		if (isset($_POST['new_scoring']) AND (float)$_POST['new_scoring'] > 0.0)
		{
			$new_question_points = (float)ilUtil::stripSlashes($_POST['new_scoring']);
		} else
		{
			$this->question_gui->object->setErrors($this->plugin->txt('sco_invalid_value'));
		}
		//Show scoring panel with comparison
		$this->scoringManagementPanel($new_question_points);
	}

	/**
	 * This command is called when the user wants to change the points value of the
	 * question to the value inserted in the input field.
	 */
	public function saveNewScoring()
	{
		//Get new points value and save it to the DB
		if (isset($_POST['new_scoring']) AND (float)$_POST['new_scoring'] > 0.0)
		{
			$this->object->setPoints(ilUtil::stripSlashes($_POST['new_scoring']));
			$this->object->saveQuestionDataToDb($this->object->getId());
		} else
		{
			$this->question_gui->object->setErrors($this->plugin->txt('sco_invalid_value'));
		}
		//Show scoring panel
		$this->scoringManagementPanel();
	}

	/*
	 * UNIT TESTS
	 */

	/**
	 * MAIN METHOD FOR SHOWING UNIT TESTS
	 */
	public function showUnitTests()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		if ($this->object->getSelfAssessmentEditingMode())
		{
			$this->getLearningModuleTabs();
		}

		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('unit_tests');
		$this->getQuestionTemplate();

		//Create GUI object
		$this->plugin->includeClass('GUI/test/class.assStackQuestionTestGUI.php');
		$unit_test_gui = new assStackQuestionTestGUI($this, $this->plugin);

		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_unit_tests.css'));

		//Returns Deployed seeds form
		$this->tpl->setVariable("QUESTION_DATA", $unit_test_gui->showUnitTestsPanel());
	}

	/*
	 * UNIT TEST COMMANDS
	 */

	/**
	 * Command for run testcases
	 */
	public function runTestcases()
	{
		global $DIC;
		$tabs = $DIC->tabs();

		//Set all parameters required
		$this->plugin->includeClass('utils/class.assStackQuestionStackFactory.php');
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('unit_tests');
		$this->getQuestionTemplate();

		//get Post vars
		if (isset($_POST['test_id']))
		{
			$test_id = $_POST['test_id'];
		}
		if (isset($_POST['question_id']))
		{
			$question_id = $_POST['question_id'];
		}
		if (isset($_POST['testcase_name']))
		{
			$testcase_name = $_POST['testcase_name'];
		} else
		{
			$testcase_name = FALSE;
		}

		//Create STACK Question object if doesn't exists
		if (!is_a($this->object->getStackQuestion(), 'assStackQuestionStackQuestion'))
		{
			$this->plugin->includeClass("model/class.assStackQuestionStackQuestion.php");
			$this->object->setStackQuestion(new assStackQuestionStackQuestion());
			$this->object->getStackQuestion()->init($this->object);
		}

		//Create Unit test object
		$this->plugin->includeClass("model/ilias_object/test/class.assStackQuestionUnitTests.php");
		$unit_tests_object = new assStackQuestionUnitTests($this->plugin, $this->object);
		$unit_test_results = $unit_tests_object->runTest($testcase_name);

		//Create GUI object
		$this->plugin->includeClass('GUI/test/class.assStackQuestionTestGUI.php');
		$unit_test_gui = new assStackQuestionTestGUI($this, $this->plugin, $unit_test_results);

		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_unit_tests.css'));

		//Returns Deployed seeds form
		$this->tpl->setVariable("QUESTION_DATA", $unit_test_gui->showUnitTestsPanel(TRUE));
	}

	/**
	 * Command for edit testcases
	 */
	public function editTestcases()
	{
		global $DIC;
		$tabs = $DIC->tabs();

		//Set all parameters required
		$this->plugin->includeClass('utils/class.assStackQuestionStackFactory.php');
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('unit_tests');
		$this->getQuestionTemplate();

		//get Post vars
		if (isset($_POST['test_id']))
		{
			$test_id = $_POST['test_id'];
		}
		if (isset($_POST['question_id']))
		{
			$question_id = $_POST['question_id'];
		}
		if (isset($_POST['testcase_name']))
		{
			$testcase_name = $_POST['testcase_name'];
		} else
		{
			$testcase_name = FALSE;
		}

		//Create unit test object
		$this->plugin->includeClass("model/ilias_object/test/class.assStackQuestionUnitTests.php");
		$unit_tests_object = new assStackQuestionUnitTests($this->plugin, $this->object);

		//Create GUI object
		$this->plugin->includeClass('GUI/test/class.assStackQuestionTestGUI.php');
		$unit_test_gui = new assStackQuestionTestGUI($this, $this->plugin);

		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_unit_tests.css'));

		//Returns Deployed seeds form
		$this->tpl->setVariable("QUESTION_DATA", $unit_test_gui->editTestcaseForm($testcase_name, $this->object->getInputs(), $this->object->getPotentialResponsesTrees()));
	}

	/**
	 * Calling command for edit testcases
	 */
	public function doEditTestcase()
	{
		if (isset($_POST['testcase_name']))
		{
			$testcase_name = $_POST['testcase_name'];
			$test = $this->object->getTests($testcase_name);
		} else
		{
			$testcase_name = FALSE;
		}

		if (is_a($test, 'assStackQuestionTest'))
		{
			//Creation of inputs
			foreach ($this->object->getInputs() as $input_name => $q_input)
			{
				$exists = FALSE;
				foreach ($test->getTestInputs() as $input)
				{
					if ($input->getTestInputName() == $input_name)
					{
						if (isset($_REQUEST[$input->getTestInputName()]))
						{
							$input->setTestInputValue($_REQUEST[$input->getTestInputName()]);
							$input->checkTestInput();
							$input->save();
							$exists = TRUE;
						}
					}
				}

				//Correct current mistakes
				if (!$exists)
				{
					$new_test_input = new assStackQuestionTestInput(-1, $this->object->getId(), $testcase_name);
					$new_test_input->setTestInputName($input_name);
					$new_test_input->setTestInputValue("");
					$new_test_input->save();
				}
			}


			//Creation of expected results
			foreach ($test->getTestExpected() as $index => $prt)
			{
				if (isset($_REQUEST['score_' . $prt->getTestPRTName()]))
				{
					$prt->setExpectedScore(ilUtil::stripSlashes($_REQUEST['score_' . $prt->getTestPRTName()]));
				}
				if (isset($_REQUEST['penalty_' . $prt->getTestPRTName()]))
				{
					$prt->setExpectedPenalty(ilUtil::stripSlashes($_REQUEST['penalty_' . $prt->getTestPRTName()]));
				}
				if (isset($_REQUEST['answernote_' . $prt->getTestPRTName()]))
				{
					$prt->setExpectedAnswerNote(ilUtil::stripSlashes($_REQUEST['answernote_' . $prt->getTestPRTName()]));
				}
				$prt->checkTestExpected();
				$prt->save();
			}
		}

		$this->showUnitTests();
	}

	/*
	 * Command for create testcases
	 */
	public function createTestcases()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		//Set all parameters required
		$this->plugin->includeClass('utils/class.assStackQuestionStackFactory.php');
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('unit_tests');
		$this->getQuestionTemplate();

		//Create GUI object
		$this->plugin->includeClass('GUI/test/class.assStackQuestionTestGUI.php');
		$unit_test_gui = new assStackQuestionTestGUI($this, $this->plugin);

		//Add CSS
		$this->tpl->addCss($this->plugin->getStyleSheetLocation('css/qpl_xqcas_unit_tests.css'));

		//Returns Deployed seeds form
		$testcase_name = assStackQuestionUtils::_getNewTestCaseNumber($this->object->getId());
		$this->tpl->setVariable("QUESTION_DATA", $unit_test_gui->createTestcaseForm($testcase_name, $this->object->getInputs(), $this->object->getPotentialResponsesTrees()));
	}

	/*
	 * Calling command for create testcases
	 */
	public function doCreateTestcase()
	{
		//boolean correct
		$testcase = assStackQuestionUtils::_getNewTestCaseNumber($this->object->getId());
		$new_test = new assStackQuestionTest(-1, $this->object->getId(), $testcase);

		//Creation of inputs
		foreach ($this->object->getInputs() as $input_name => $input)
		{
			$new_test_input = new assStackQuestionTestInput(-1, $this->object->getId(), $testcase);
			$new_test_input->setTestInputName($input_name);

			if (isset($_REQUEST[$input_name]))
			{
				$new_test_input->setTestInputValue(ilUtil::stripSlashes($_REQUEST[$input_name]));
			} else
			{
				$new_test_input->setTestInputValue("");
			}

			$new_test_input->save();
			$test_inputs[] = $new_test_input;
		}

		//Creation of expected results
		foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt)
		{
			//Getting the PRT name
			$new_test_expected = new assStackQuestionTestExpected(-1, $this->object->getId(), $testcase, $prt_name);

			if (isset($_REQUEST['score_' . $prt_name]))
			{
				$new_test_expected->setExpectedScore(ilUtil::stripSlashes($_REQUEST['score_' . $prt_name]));
			} else
			{
				$new_test_expected->setExpectedScore("");
			}

			if (isset($_REQUEST['penalty_' . $prt_name]))
			{
				$new_test_expected->setExpectedPenalty(ilUtil::stripSlashes($_REQUEST['penalty_' . $prt_name]));
			} else
			{
				$new_test_expected->setExpectedPenalty("");
			}

			if (isset($_REQUEST['answernote_' . $prt_name]))
			{
				$new_test_expected->setExpectedAnswerNote(ilUtil::stripSlashes($_REQUEST['answernote_' . $prt_name]));
			} else
			{
				$new_test_expected->setExpectedAnswerNote("");
			}
			$new_test_expected->save();
			$test_expected[] = $new_test_expected;
		}

		$new_test->setTestExpected($test_expected);
		$new_test->setTestInputs($test_inputs);
		$new_test->save();

		$this->showUnitTests();
	}

	/*
	 * Command for deleting testcases
	 */
	public function doDeleteTestcase()
	{
		//get Post vars
		if (isset($_POST['test_id']))
		{
			$test_id = $_POST['test_id'];
		}
		if (isset($_POST['question_id']))
		{
			$question_id = $_POST['question_id'];
		}
		if (isset($_POST['testcase_name']))
		{
			$testcase_name = $_POST['testcase_name'];
		} else
		{
			$testcase_name = FALSE;
		}

		$new_tests = assStackQuestionTest::_read($question_id, $testcase_name);
		$new_test = $new_tests[$testcase_name];
		$new_test->delete($question_id, $testcase_name);

		$this->showUnitTests();
	}

	/*
	 * IMPORT FROM MOODLE
	 */

	public function importQuestionFromMoodleForm()
	{
		global $DIC;

		$lng = $DIC->language();
		$tabs = $DIC->tabs();
		if ($this->object->getSelfAssessmentEditingMode())
		{
			$this->getLearningModuleTabs();
		}
		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('import_from_moodle');

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($lng->txt("qpl_qst_xqcas_import_xml"));

		//Upload XML file
		$item = new ilFileInputGUI($lng->txt("qpl_qst_xqcas_import_xml_file"), 'questions_xml');
		$item->setSuffixes(array('xml'));
		$form->addItem($item);

		$hiddenFirstId = new ilHiddenInputGUI('first_question_id');
		$hiddenFirstId->setValue($_GET['q_id']);
		$form->addItem($hiddenFirstId);

		$form->addCommandButton("importQuestionFromMoodle", $lng->txt("import"));
		$form->addCommandButton("editQuestion", $lng->txt("cancel"));

		$this->tpl->setContent($form->getHTML());
	}

	public function importQuestionFromMoodle()
	{
		global $DIC;
		$tabs = $DIC->tabs();

		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('import_from_moodle');

		//Getting the xml file from $_FILES
		if (file_exists($_FILES["questions_xml"]["tmp_name"]))
		{
			$xml_file = $_FILES["questions_xml"]["tmp_name"];
		} else
		{
			$this->object->setErrors($this->plugin->txt('error_import_question_in_test'), true);

			return;
		}

		//CHECK FOR NOT ALLOW IMPROT QUESTIONS DIRECTLY IN TESTS
		if (isset($_GET['calling_test']))
		{
			$this->object->setErrors($this->plugin->txt('error_import_question_in_test'), true);

			return;
		} else
		{
			//Include import class and prepare object
			$this->plugin->includeClass('model/import/MoodleXML/class.assStackQuestionMoodleImport.php');
			$import = new assStackQuestionMoodleImport($this->plugin, (int)$_POST['first_question_id'], $this->object);
			$import->setRTETags($this->getRTETags());
			$import->import($xml_file);
		}
	}

	public function exportQuestiontoMoodleForm()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		$lng = $DIC->language();

		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('export_to_moodle');

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($lng->txt("qpl_qst_xqcas_export_to_moodlexml"));

		$options = new ilRadioGroupInputGUI($lng->txt("qpl_qst_xqcas_all_from_pool"), "xqcas_all_from_pool");
		$only_question = new ilRadioOption($lng->txt("qpl_qst_xqcas_export_only_this"), "xqcas_export_only_this", $lng->txt("qpl_qst_xqcas_export_only_this_info"));
		if (isset($_GET['calling_test']))
		{
			$all_from_pool = new ilRadioOption($lng->txt("qpl_qst_xqcas_export_all_from_test"), "xqcas_export_all_from_test", $lng->txt("qpl_qst_xqcas_export_all_from_test_info"));
		} else
		{
			$all_from_pool = new ilRadioOption($lng->txt("qpl_qst_xqcas_export_all_from_pool"), "xqcas_export_all_from_pool", $lng->txt("qpl_qst_xqcas_export_all_from_pool_info"));
		}

		$options->addOption($only_question);
		$options->addOption($all_from_pool);

		if (isset($_GET['calling_test']))
		{
			$options->setValue("xqcas_export_all_from_test");
		} else
		{
			$options->setValue("xqcas_export_all_from_pool");
		}

		$form->addItem($options);

		$hiddenFirstId = new ilHiddenInputGUI('first_question_id');
		$hiddenFirstId->setValue($_GET['q_id']);
		$form->addItem($hiddenFirstId);

		$form->addCommandButton("exportQuestionToMoodle", $lng->txt("export"));
		$form->addCommandButton("editQuestion", $lng->txt("cancel"));

		$this->tpl->setContent($form->getHTML());
	}

	public function exportQuestionToMoodle()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		$lng = $DIC->language();

		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/export/MoodleXML/class.assStackQuestionMoodleXMLExport.php';

		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('export_to_moodle');

		//Getting data from POST
		if (isset($_POST['first_question_id']) AND isset($_POST['xqcas_all_from_pool']))
		{
			$id = $_POST['first_question_id'];
			$mode = "";
			if ($_POST['xqcas_all_from_pool'] == 'xqcas_export_all_from_pool')
			{
				//Get all questions from a pool
				$export_to_moodle = new assStackQuestionMoodleXMLExport($this->object->getAllQuestionsFromPool());
				$xml = $export_to_moodle->toMoodleXML();
			} elseif ($_POST['xqcas_all_from_pool'] == 'xqcas_export_only_this')
			{
				//get current stack question info.
				$export_to_moodle = new assStackQuestionMoodleXMLExport(array($id => $this->object));
				$xml = $export_to_moodle->toMoodleXML();
			} elseif ($_POST['xqcas_all_from_pool'] == 'xqcas_export_all_from_test')
			{
				//get current stack question info.
				$export_to_moodle = new assStackQuestionMoodleXMLExport($this->object->getAllQuestionsFromTest());
				$xml = $export_to_moodle->toMoodleXML();
			} else
			{
				throw new Exception($lng->txt('qpl_qst_xqcas_error_exporting_to_moodle_mode'));
			}
		} else
		{
			throw new Exception($lng->txt('qpl_qst_xqcas_error_exporting_to_moodle_question_id'));
		}
	}

	public function showFeedback()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		//Set all parameters required
		$tabs->activateTab('edit_properties');
		$tabs->activateSubTab('feedback');

		return "";
	}

	/**
	 * Save the showing info messages state in the user session
	 * (This keeps info messages state between page moves)
	 * @see self::addToPage()
	 */
	public function saveInfoState()
	{
		$_SESSION['stack_authoring_show'] = (int)$_GET['show'];

		// debugging output (normally ignored by the js part)
		echo json_encode(array('show' => $_SESSION['stack_authoring_show']));
		exit;
	}

	public function getErrors()
	{
		$isComplete = TRUE;

		//Check all inputs have a model answer
		$incomplete_model_answers = "";
		foreach ($this->object->getInputs() as $input_name => $input)
		{
			if ($input->getTeacherAnswer() == "" OR $input->getTeacherAnswer() == " ")
			{
				$isComplete = FALSE;
				$incomplete_model_answers .= $input_name . ", ";
			}
		}
		$incomplete_model_answers = substr($incomplete_model_answers, 0, -2);

		//Check student answer is always filled in
		$incomplete_student_answers = "";
		foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt)
		{
			foreach ($prt->getPRTNodes() as $node_name => $node)
			{
				if ($node->getStudentAnswer() == "" OR $node->getStudentAnswer() == " ")
				{
					$isComplete = FALSE;
					$incomplete_student_answers .= $prt_name . " / " . $node_name . ", ";
				}
			}
		}
		$incomplete_student_answers = substr($incomplete_student_answers, 0, -2);

		//Check teacher answer is always filled in
		$incomplete_teacher_answers = "";
		foreach ($this->object->getPotentialResponsesTrees() as $prt_name => $prt)
		{
			foreach ($prt->getPRTNodes() as $node_name => $node)
			{
				if ($node->getTeacherAnswer() == "" OR $node->getTeacherAnswer() == " ")
				{
					$isComplete = FALSE;
					$incomplete_teacher_answers .= $prt_name . " / " . $node_name . ", ";
				}
			}
		}
		$incomplete_teacher_answers = substr($incomplete_teacher_answers, 0, -2);

		if (!$isComplete AND $this->object->getTitle() != NULL)
		{
			if ($incomplete_model_answers != "")
			{
				$this->object->setErrors($this->getPlugin()->txt("error_model_answer_missing") . " " . $incomplete_model_answers);
			}
			if ($incomplete_student_answers != "")
			{
				$this->object->setErrors($this->getPlugin()->txt("error_student_answer_missing") . " " . $incomplete_student_answers);
			}
			if ($incomplete_teacher_answers != "")
			{
				$this->object->setErrors($this->getPlugin()->txt("error_teacher_answer_missing") . " " . $incomplete_teacher_answers);
			}
		}
	}

	/**
	 * @return null
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param null $plugin
	 */
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	public function getLearningModuleTabs()
	{
		global $DIC;
		$tabs = $DIC->tabs();

		$this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
		include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
		$this->plugin->includeClass('class.ilAssStackQuestionFeedback.php');

		$q_type = $this->object->getQuestionType();

		if (strlen($q_type))
		{
			$classname = $q_type . "GUI";
			$this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
			$this->ctrl->setParameterByClass(strtolower($classname), "q_id", $this->object->getId());
		}

		$force_active = false;
		$url = "";

		if ($classname)
		{
			$url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
		}
		$commands = $_POST["cmd"];
		if (is_array($commands))
		{
			foreach ($commands as $key => $value)
			{
				if (preg_match("/^suggestrange_.*/", $key, $matches))
				{
					$force_active = true;
				}
			}
		}
		// edit question properties
		$tabs->addTarget("edit_properties", $url, array("editQuestion", "save", "cancel", "addSuggestedSolution", "cancelExplorer", "linkChilds", "removeSuggestedSolution", "parseQuestion", "saveEdit", "suggestRange"), $classname, "", $force_active);

		if (in_array($_GET['cmd'], array('importQuestionFromMoodleForm', 'importQuestionFromMoodle', 'editQuestion', 'scoringManagement', 'scoringManagementPanel', 'deployedSeedsManagement', 'createNewDeployedSeed', 'deleteDeployedSeed', 'showUnitTests', 'runTestcases', 'createTestcases', 'post', 'exportQuestiontoMoodleForm', 'exportQuestionToMoodle',)))
		{
			$tabs->addSubTab('edit_question', $this->plugin->txt('edit_question'), $this->ctrl->getLinkTargetByClass($classname, "editQuestion"));
			$tabs->addSubTab('scoring_management', $this->plugin->txt('scoring_management'), $this->ctrl->getLinkTargetByClass($classname, "scoringManagementPanel"));
			$tabs->addSubTab('deployed_seeds_management', $this->plugin->txt('dsm_deployed_seeds'), $this->ctrl->getLinkTargetByClass($classname, "deployedSeedsManagement"));
			$tabs->addSubTab('unit_tests', $this->plugin->txt('ut_title'), $this->ctrl->getLinkTargetByClass($classname, "showUnitTests"));
		}

	}

}
