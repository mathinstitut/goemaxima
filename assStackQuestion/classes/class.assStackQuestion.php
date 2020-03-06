<?php

/**
 * Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once './Modules/TestQuestionPool/classes/class.assQuestion.php';
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/exceptions/class.assStackQuestionException.php';

// Interface for FormATest
include_once './Modules/TestQuestionPool/interfaces/interface.iQuestionCondition.php';

/**
 * STACK Question OBJECT
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.3$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestion extends assQuestion implements iQuestionCondition
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	//STACK Question structure variables

	/**
	 * Options for this question
	 * @var assStackQuestionOptions
	 */
	private $options;

	/**
	 * Inputs for this question
	 * @var array of assStackQuestionInput
	 */
	private $inputs = array();

	/**
	 * Potential Response Trees for this question
	 * @var array of assStackQuestionPRT
	 */
	private $potential_responses_trees = array();

	/**
	 * Unit tests created for this question
	 * @var array of assStackQuestionTest
	 */
	private $tests = array();

	/**
	 * Deployed variants that have been deployed
	 * @var array of assStackQuestionDeployedSeed
	 */
	private $deployed_seeds = array();

	/**
	 * Maxima's random number generator
	 * @var integer
	 */
	private $seed;

	/**
	 * Extra info taken from XML that can be used
	 * @var assStackQuestionExtraInfo
	 */
	private $extra_info;


	/**
	 * This object contains variables needed by stack classes
	 * @var assStackQuestionStackQuestion
	 */
	private $stack_question;


	/**
	 * @var bool
	 */
	private $instant_validation;

	/**
	 * CONSTRUCTOR.
	 * @param string $title
	 * @param string $comment
	 * @param string $author
	 * @param int $owner
	 * @param string $question
	 */
	function __construct($title = "", $comment = "", $author = "", $owner = -1, $question = "")
	{
		parent::__construct($title, $comment, $author, $owner, $question);
		// init the plugin object
		$this->getPlugin();
	}

	/*
	 * QUESTION EVALUATION AND RUNNING PARAMETERS
	 */

	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers including checks
	 * for all special scoring options in the test container.
	 *
	 * @param integer $active The Id of the active learner
	 * @param integer $pass The Id of the test pass
	 * @param boolean $returndetails (deprecated !!)
	 * @return integer/array $points/$details (array $details is deprecated !!)
	 * @access public
	 */
	public function calculateReachedPoints($active_id, $pass = NULL, $authorizedSolution = true, $returndetails = FALSE)
	{
		/*As long as $returndetails is deprecated the exception it throws will not be thrown anymore
		if ($returndetails) {
			throw new ilTestException('return details not implemented for ' . __METHOD__);
		}*/

		global $DIC;
		$db = $DIC->database();

		if (is_null($pass))
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}

		//In case of check in test
		if ($authorizedSolution == FALSE)
		{
			return $this->stack_question->reached_points;
		}

		// get all saved part solutions with points assigned
		$result = $this->getCurrentSolutionResultSet($active_id, $pass, $authorizedSolution);

		// in some cases points may have been saved twice (see saveWorkingDataValue())
		// so collect them by the part result (value1)
		// and summarize them afterwards
		$points = array();
		while ($row = $db->fetchAssoc($result))
		{
			$points[$row['value1']] = (float)$row['points'];
		}

		return array_sum($points);
	}

	/**
	 * Saves the learners input of the question to the database.
	 *
	 * @param int $active_id
	 * @param int $pass
	 * @param bool $authorized
	 * @return bool
	 */
	public function saveWorkingData($active_id, $pass = NULL, $authorized = true)
	{
		global $DIC;
		$db = $DIC->database();

		if (is_null($pass))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		//Determine seed for current test run
		$seed = $this->getQuestionSeedForCurrentTestRun($active_id, $pass);

		//Create STACK Question object if doesn't exists
		if (!is_a($this->getStackQuestion(), 'assStackQuestionStackQuestion'))
		{
			$this->plugin->includeClass("model/class.assStackQuestionStackQuestion.php");
			$this->setStackQuestion(new assStackQuestionStackQuestion($active_id, $pass));
			$this->getStackQuestion()->init($this, '', $seed);
		}

		$entered_values = 0;

		$saved = true;

		$user_solution = $this->getSolutionSubmit();

		//Calculate results for user_solution before save it
		//Create evaluation object
		$this->plugin->includeClass("model/question_evaluation/class.assStackQuestionEvaluation.php");
		$evaluation_object = new assStackQuestionEvaluation($this->plugin, $this->getStackQuestion(), $user_solution);
		//Evaluate question
		$question_evaluation = $evaluation_object->evaluateQuestion();
		$question_evaluation->calculatePoints();

		//Get Feedback
		$this->plugin->includeClass('model/question_evaluation/class.assStackQuestionFeedback.php');
		$feedback_object = new assStackQuestionFeedback($this->plugin, $question_evaluation);
		$feedback_data = $feedback_object->getFeedback();

		//DB Operations
		//$this->getProcessLocker()->requestUserSolutionUpdateLock();

		//If ILIAS 5.1  or 5.0 using intermediate
		if (method_exists($this, "getUserSolutionPreferingIntermediate"))
		{
			//Remove current solutions depending on the authorized parameter.
			if ($authorized)
			{
				$this->removeExistingSolutions($active_id, $pass);
			} else
			{
				$this->removeIntermediateSolution($active_id, $pass);
			}

			//5.1
			//Save new user solution
			//Save question text instantiated
			$this->saveCurrentSolution($active_id, $pass, 'xqcas_text_' . $this->getStackQuestion()->getQuestionId(), $feedback_data['question_text'], $authorized);
			//Save question note
			$this->saveCurrentSolution($active_id, $pass, 'xqcas_solution_' . $this->getStackQuestion()->getQuestionId(), $feedback_data['question_note'], $authorized);
			//Save general feedback
			$this->saveCurrentSolution($active_id, $pass, 'xqcas_general_feedback_' . $this->getStackQuestion()->getQuestionId(), $feedback_data['general_feedback'], $authorized);

			//Save PRT information
			foreach ($feedback_data['prt'] as $prt_name => $prt)
			{
				//value1 = xqcas_input_name, $value2 = input_name
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_name', $prt_name, $authorized);

				//Save input information per PRT
				foreach ($prt['response'] as $input_name => $response)
				{
					//value1 = xqcas_input_*_value, value2 = student answer for this question input
					//Notes result change to real user input value
					if (is_a($this->getStackQuestion()->getInputs($input_name), "stack_notes_input"))
					{
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_value_' . $input_name, $this->getStackQuestion()->getInputStates($input_name)->__get("contents")[0], $authorized);
					} else
					{
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_value_' . $input_name, $response['value'], $authorized);
					}
					//value1 = xqcas_input_*_display, value2 = student answer for this question input in LaTeX
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_display_' . $input_name, $response['display'], $authorized);
					//value1 = xqcas_input_*_model_answer, value2 = student answer for this question input in LaTeX
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_model_answer_' . $input_name, $response['model_answer'], $authorized);
					//value1 = xqcas_input_*_model_answer_diplay_, value2 = model answer for this question input in LaTeX
					if (isset($response['model_answer_display']))
					{
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_model_answer_display_' . $input_name, $response['model_answer_display'], $authorized);
					}
					//value1 = xqcas_input_*_model_answer, value2 = student answer for this question input in LaTeX
					$this->removeOldSeeds($active_id,$pass);
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_seed', $seed, $authorized);
				}
				//value1 = xqcas_input_*_errors, $value2 = feedback given by CAS
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_errors', $prt['errors'], $authorized);
				//value1 = xqcas_input_*_feedback, $value2 = feedback given by CAS
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_feedback', $prt['feedback'], $authorized);
				//value1 = xqcas_input_*_status, $value2 = status
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_status', $prt['status']['value'], $authorized);
				//value1 = xqcas_input_*_status_message, $value2 = status message
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_status_message', $prt['status']['message'], $authorized);
				//value1 = xqcas_input_*_status_message, $value2 = status message
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_answernote', $prt['answernote'], $authorized);
				if ($prt_name)
				{
					$this->addPointsToPRTDBEntry($this->getStackQuestion()->getQuestionId(), $active_id, $pass, $prt_name, $prt['points'], $authorized);
				}
				//Set entered values as TRUE
				$entered_values = TRUE;
			}

		} else
		{
			//5.0
			//Save new user solution

			//Delete current data
			$query = "DELETE FROM tst_solutions" . " WHERE active_fi = " . $db->quote($active_id, "integer") . " AND pass = " . $db->quote($pass, "integer") . " AND question_fi = " . $db->quote($this->getId(), "integer");

			$db->manipulate($query);


			//Save question text instantiated
			$this->saveCurrentSolution($active_id, $pass, 'xqcas_text_' . $this->getStackQuestion()->getQuestionId(), $feedback_data['question_text']);
			//Save question note
			$this->saveCurrentSolution($active_id, $pass, 'xqcas_solution_' . $this->getStackQuestion()->getQuestionId(), $feedback_data['question_note']);
			//Save general feedback
			$this->saveCurrentSolution($active_id, $pass, 'xqcas_general_feedback_' . $this->getStackQuestion()->getQuestionId(), $feedback_data['general_feedback']);

			//Save PRT information
			foreach ($feedback_data['prt'] as $prt_name => $prt)
			{
				//value1 = xqcas_input_name, $value2 = input_name
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_name', $prt_name);
				//Save input information per PRT
				foreach ($prt['response'] as $input_name => $response)
				{
					//value1 = xqcas_input_*_value, value2 = student answer for this question input
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_value_' . $input_name, $response['value']);
					//value1 = xqcas_input_*_display, value2 = student answer for this question input in LaTeX
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_display_' . $input_name, $response['display']);
					//value1 = xqcas_input_*_model_answer, value2 = student answer for this question input in LaTeX
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_model_answer_' . $input_name, $response['model_answer']);
					//value1 = xqcas_input_*_model_answer_diplay_, value2 = model answer for this question input in LaTeX
					if (isset($response['model_answer_display']))
					{
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_model_answer_display_' . $input_name, $response['model_answer_display']);
					}
					//value1 = xqcas_input_*_model_answer, value2 = student answer for this question input in LaTeX
					$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_seed', $seed);
				}
				//value1 = xqcas_input_*_errors, $value2 = feedback given by CAS
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_errors', $prt['errors']);
				//value1 = xqcas_input_*_feedback, $value2 = feedback given by CAS
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_feedback', $prt['feedback']);
				//value1 = xqcas_input_*_status, $value2 = status
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_status', $prt['status']['value']);
				//value1 = xqcas_input_*_status_message, $value2 = status message
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_status_message', $prt['status']['message']);
				//value1 = xqcas_input_*_status_message, $value2 = status message
				$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt_name . '_answernote', $prt['answernote']);
				if ($prt_name)
				{
					$this->addPointsToPRTDBEntry($this->getStackQuestion()->getQuestionId(), $active_id, $pass, $prt_name, $prt['points']);
				}
				//Set entered values as TRUE
				$entered_values = TRUE;
			}

		}

		//$this->getProcessLocker()->releaseUserSolutionUpdateLock();

		if ($entered_values)
		{
			require_once './Modules/Test/classes/class.ilObjAssessmentFolder.php';
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		} else
		{
			include_once("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}

		return $saved;
	}

	/**
	 * Add points to the DB entrie for a PRT in tst_solutions
	 * @param $active_id
	 * @param $pass
	 * @param $prt_name
	 * @param $points
	 * @return int
	 */
	public function addPointsToPRTDBEntry($question_id, $active_id, $pass, $prt_name, $points, $authorized = NULL)
	{
		global $DIC;
		$db = $DIC->database();

		$fieldData = array("points" => array("float", (float)$points));

		//Get step in case it exists
		if ($this->getStep() !== null)
		{
			$fieldData['step'] = array("integer", $this->getStep());
		}

		//get Solution Id for prt_name field in tst_solutions
		$solution_id = NULL;
		$solution_values = parent::getSolutionValues($active_id, $pass, $authorized);
		foreach ($solution_values as $solution)
		{
			if ($solution['value1'] == 'xqcas_prt_' . $prt_name . '_name')
			{
				$solution_id = $solution['solution_id'];
				break;
			}
		}

		//Replace points in tst_solution solution_id entry
		if ($solution_id != NULL)
		{
			$db->update("tst_solutions", $fieldData, array('solution_id' => array('integer', (int)$solution_id)));
		}
	}

	/**
	 * Loads solutions of a given user from the database an returns it
	 * in a readable format.
	 *
	 * @param $active_id
	 * @param null $pass
	 * @param bool $authorized
	 * @return array
	 */
	function &getSolutionValues($active_id, $pass = NULL, $authorized = TRUE)
	{
		return $this->fromDBToReadableFormat(parent::getSolutionValues($active_id, $pass, $authorized));
	}

	/**
	 * Get raw data from DB and transforms it into a readable by
	 * STACK Question plugin format.
	 * @param array $db_values
	 * @return array
	 */
	private function fromDBToReadableFormat($db_values)
	{
		//Prepare array;
		$results = array();
		foreach ($db_values as $index => $value)
		{
			if ($value['value1'] == 'xqcas_text_' . $value['question_fi'])
			{
				$results['question_text'] = $value['value2'];
				$results['id'] = $value['question_fi'];
				$results['points'] = (float)$value['points'];
				unset($db_values[$index]);
			} elseif ($value['value1'] == 'xqcas_solution_' . $value['question_fi'])
			{
				$results['question_note'] = $value['value2'];
				unset($db_values[$index]);
			} elseif ($value['value1'] == 'xqcas_general_feedback_' . $value['question_fi'])
			{
				$results['general_feedback'] = $value['value2'];
				unset($db_values[$index]);
			} else
			{
				foreach ($this->getPotentialResponsesTrees() as $prt_name => $prt)
				{
					if ($value['value1'] == 'xqcas_prt_' . $prt_name . '_name')
					{
						$results['prt'][$prt_name]['points'] = $value['points'];
						unset($db_values[$index]);
					} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_errors')
					{
						$results['prt'][$prt_name]['errors'] = $value['value2'];
						unset($db_values[$index]);
					} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_feedback')
					{
						$results['prt'][$prt_name]['feedback'] = $value['value2'];
						unset($db_values[$index]);
					} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_status')
					{
						$results['prt'][$prt_name]['status']['value'] = $value['value2'];
						unset($db_values[$index]);
					} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_status_message')
					{
						$results['prt'][$prt_name]['status']['message'] = $value['value2'];
						unset($db_values[$index]);
					} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_answernote')
					{
						$results['prt'][$prt_name]['answernote'] = $value['value2'];
						unset($db_values[$index]);
					} else
					{
						foreach ($this->getInputs() as $input_name => $input)
						{
							if ($value['value1'] == 'xqcas_prt_' . $prt_name . '_value_' . $input_name)
							{
								$results['prt'][$prt_name]['response'][$input_name]['value'] = $value['value2'];
								unset($db_values[$index]);
							} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_display_' . $input_name)
							{
								$results['prt'][$prt_name]['response'][$input_name]['display'] = $value['value2'];
								unset($db_values[$index]);
							} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_model_answer_' . $input_name)
							{
								$results['prt'][$prt_name]['response'][$input_name]['model_answer'] = $value['value2'];
								unset($db_values[$index]);
							} elseif ($value['value1'] == 'xqcas_prt_' . $prt_name . '_model_answer_display_' . $input_name)
							{
								$results['prt'][$prt_name]['response'][$input_name]['model_answer_display'] = $value['value2'];
								unset($db_values[$index]);
							}
						}
					}
				}
			}
		}

		return $results;
	}


	/**
	 * Creates a question from a QTI file
	 *
	 * Receives parameters from a QTI parser and creates a valid ILIAS question object
	 *
	 * @param object $item The QTI item object
	 * @param integer $questionpool_id The id of the parent questionpool
	 * @param integer $tst_id The id of the parent test if the question is part of a test
	 * @param object $tst_object A reference to the parent test object
	 * @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
	 * @param array $import_mapping An array containing references to included ILIAS objects
	 */
	public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass('import/qti12/class.assStackQuestionImport.php');
		$import = new assStackQuestionImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}

	/**
	 * Returns a QTI xml representation of the question and sets the internal
	 * domxml variable with the DOM XML representation of the QTI xml representation
	 *
	 * @return string The QTI xml representation of the question
	 */
	public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass('model/export/qti12/class.assStackQuestionExport.php');
		$export = new assStackQuestionExport($this);

		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}


	/**
	 * {@inheritdoc}
	 */
	public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass)
	{
		parent::setExportDetailsXLS($worksheet, $startrow, $active_id, $pass);

		$solution = $this->getSolutionValues($active_id, $pass);
		global $DIC;
		$lng = $DIC->language();
		$answered_inputs = array();

		$worksheet->setCell($startrow, 0, $this->lng->txt($this->plugin->txt('assStackQuestion')), $format_title);
		$worksheet->setCell($startrow, 1, $this->getTitle(), $format_title);
		$i = 1;
		foreach ($solution as $solution_id => $solutionvalue)
		{
			if ($solution_id != 'prt')
			{
				if ($solution_id == 'question_text')
				{
					$worksheet->setCell($startrow + $i, 0, $this->plugin->txt('message_question_text'), $format_title);
					$worksheet->setCell($startrow + $i, 1, $solutionvalue);
					$i++;
				}
				if ($solution_id == 'question_note')
				{
					$worksheet->setCell($startrow + $i, 0, $this->plugin->txt('exp_question_note'), $format_title);
					$worksheet->setCell($startrow + $i, 1, $solutionvalue);
					$i++;
				}
				if ($solution_id == 'general_feedback')
				{
					$worksheet->setCell($startrow + $i, 0, $this->plugin->txt('exp_general_feedback'), $format_title);
					$worksheet->setCell($startrow + $i, 1, $solutionvalue);
					$i++;
				}
				if ($solution_id == 'points')
				{
					$worksheet->setCell($startrow + $i, 0, $lng->txt('points'), $format_title);
					$worksheet->setCell($startrow + $i, 1, $solutionvalue);
					$i++;
				}
			} else
			{
				foreach ($solutionvalue as $prt_name => $prt_value)
				{
					if (isset($prt_value['points']))
					{
						$worksheet->setCell($startrow + $i, 0, $prt_name . ' ' . $lng->txt('points'), $format_bold);
						$worksheet->setCell($startrow + $i, 1, $prt_value['points']);
						$i++;
					}
					if ($prt_value['answernote'])
					{
						$worksheet->setCell($startrow + $i, 0, $prt_name . ' ' . $this->plugin->txt('message_answernote_part'), $format_bold);
						$worksheet->setCell($startrow + $i, 1, $prt_value['answernote']);
						$i++;
					}
					if ($prt_value['response'])
					{
						foreach ($prt_value['response'] as $input_name => $input)
						{
							$worksheet->setCell($startrow + $i, 0, $input_name . ' ' . $this->plugin->txt('exp_student_answer'), $format_bold);
							$worksheet->setCell($startrow + $i, 1, $input['value']);
							$answered_inputs[$input_name] = $input['value'];
							$i++;
						}
					}
				}
			}
		}

		return $startrow + $i + 1;
	}

	/*
	 * COPYING AND MOVING
	 */

	/**
	 * Duplicates an assStackQuestion
	 *
	 * @param bool $for_test
	 * @param string $title
	 * @param string $author
	 * @param string $owner
	 * @param integer|null $testObjId
	 *
	 * @return void|integer Id of the clone or nothing.
	 */
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$this_id = $this->getId();

		if ((int)$testObjId > 0)
		{
			$thisObjId = $this->getObjId();
		}

		$clone = $this;
		include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");
		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;

		if ((int)$testObjId > 0)
		{
			$clone->setObjId($testObjId);
		}

		if ($title)
		{
			$clone->setTitle($title);
		}

		if ($author)
		{
			$clone->setAuthor($author);
		}
		if ($owner)
		{
			$clone->setOwner($owner);
		}

		if ($for_test)
		{
			$clone->saveToDb($original_id, TRUE);
		} else
		{
			$clone->saveToDb("", TRUE);
		}

		// copy question page content
		$clone->copyPageOfQuestion($this_id);

		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this_id);

		$clone->onDuplicate($thisObjId, $this_id, $clone->getObjId(), $clone->getId());

		return $clone->id;
	}

	/**
	 * Copies an assStackQuestion object
	 *
	 * @param integer $target_questionpool_id
	 * @param string $title
	 *
	 * @return void|integer Id of the clone or nothing.
	 */
	function copyObject($target_questionpool_id, $title = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}
		// duplicate the question in database
		$clone = $this;
		include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");

		$original_id = assQuestion::_getOriginalId($this->id);
		$clone->id = -1;
		$source_questionpool_id = $this->getObjId();
		$clone->setObjId($target_questionpool_id);
		if ($title)
		{
			$clone->setTitle($title);
		}
		$clone->saveToDb("", TRUE);
		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);

		$clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

		return $clone->id;
	}

	/**
	 * @param $targetParentId
	 * @param string $targetQuestionTitle
	 * @return int
	 */
	public function createNewOriginalFromThisDuplicate($targetParentId, $targetQuestionTitle = "")
	{
		if ($this->id <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}

		include_once("./Modules/TestQuestionPool/classes/class.assQuestion.php");

		$sourceQuestionId = $this->id;
		$sourceParentId = $this->getObjId();

		// duplicate the question in database
		$clone = $this;
		$clone->id = -1;

		$clone->setObjId($targetParentId);

		if ($targetQuestionTitle)
		{
			$clone->setTitle($targetQuestionTitle);
		}

		$clone->simpleSaveToDb();

		$clone->beforeCopy($clone->getId());
		$clone->saveToDb();

		// copy question page content
		$clone->copyPageOfQuestion($sourceQuestionId);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($sourceQuestionId);

		$clone->onCopy($sourceParentId, $sourceQuestionId, $clone->getObjId(), $clone->getId());

		return $clone->id;
	}

	/*
	 * DELETE
	 */

	/**
	 * @param int $question_id
	 */
	public function deleteAdditionalTableData($question_id)
	{
		global $DIC;
		$db = $DIC->database();
		$additional_table_name = $this->getAdditionalTableName();
		foreach ($additional_table_name as $table)
		{
			if (strlen($table))
			{
				$affectedRows = $db->manipulateF("DELETE FROM $table WHERE question_id = %s", array('integer'), array($question_id));
			}
		}
	}

	/*
	 * SAVE TO DB
	 */

	/**
	 * Saves an assStackQuestion object to a database.
	 * @param string $original_id
	 */
	public function saveToDb($original_id = "", $importing_questions = "", $edit_question = "")
	{
		if ($this->getTitle() != "" AND $this->getAuthor() != "" AND $this->getQuestion() != "")
		{
			//Check before save for errors
			if (!is_a($this->getStackQuestion(), 'assStackQuestionStackQuestion'))
			{
				$this->getPlugin()->includeClass("model/class.assStackQuestionStackQuestion.php");
				$this->setStackQuestion(new assStackQuestionStackQuestion());
				$this->getStackQuestion()->init($this, "", "", "", TRUE);
				$edit_question = TRUE;
			}

			$this->saveQuestionDataToDb($original_id);

			if (isset($_REQUEST["cmd"]["insertQuestions"]))
			{
				$adding_to_test = TRUE;
			} else
			{
				$adding_to_test = FALSE;
			}

			if (isset($_GET['new_type']) AND $_GET['new_type'] == 'qpl')
			{
				$importing_questions = TRUE;
			} else
			{
				if ($importing_questions)
				{
					$importing_questions = TRUE;
				} else
				{
					$importing_questions = FALSE;
				}
			}

			$this->saveAdditionalQuestionDataToDb($edit_question, $adding_to_test, $importing_questions);
			parent::saveToDb($original_id);
		} else
		{
			$this->setErrors($this->getPlugin()->txt('error_fields_missing'));

			return FALSE;
		}

	}

	public function simpleSaveToDb($original_id = "")
	{
		$this->saveQuestionDataToDb($original_id);
		parent::saveToDb($original_id);
	}

	/**
	 * Save to DB all the specific data from a STACK Question.
	 * Is called from saveToDb().
	 */
	public function saveAdditionalQuestionDataToDb($edit_question = "", $adding_to_test = FALSE, $importing_questions = FALSE)
	{
		//OPTIONS
		if (is_a($this->options, 'assStackQuestionOptions'))
		{
			if (!$this->options->getOptionsId() OR $adding_to_test OR $importing_questions)
			{
				$this->options->setOptionsId(-1);
				$this->options->setQuestionId($this->getId());
			}
			$this->options->checkOptions(TRUE);

			//Check if it has random variable, in this case this is mandatory. Solve bug 0016426
			if (assStackQuestionUtils::_questionHasRandomVariables($this->options->getQuestionVariables()))
			{
				global $DIC;

				$lng = $DIC->language();
				if ($this->options->getQuestionNote() == "" OR $this->options->getQuestionNote() == " ")
				{
					$this->setErrors($lng->txt("qpl_qst_xqcas_error_no_question_note"));
				}
			}
			$this->options->save();
		} else
		{
			$options_obj = new assStackQuestionOptions(-1, $this->getId());
			$this->setOptions($options_obj);
			$this->getOptions()->save();
		}

		//INPUTS
		if (sizeof($this->inputs))
		{
			foreach ($this->inputs as $input)
			{
				if (is_a($input, 'assStackQuestionInput'))
				{
					if (!$input->getInputId() OR $adding_to_test OR $importing_questions)
					{
						$input->setInputId(-1);
						$input->setQuestionId($this->getId());
					}
					$input->checkInput(TRUE);
					$input->save();
				}
			}
		}

		//POTENTIAL RESPONSE TREES
		if (sizeof($this->potential_responses_trees))
		{
			foreach ($this->potential_responses_trees as $prt)
			{
				if (!$prt->getPRTId() OR $adding_to_test OR $importing_questions)
				{
					$prt->setPRTId(-1);
					$prt->setQuestionId($this->getId());
				}
				$prt->save();
				//POTENTIAL RESPONSE TREES NODES
				foreach ($prt->getPRTNodes() as $node)
				{
					if (!$node->getNodeId() OR $adding_to_test OR $importing_questions)
					{
						$node->setNodeId(-1);
						$node->setQuestionId($this->getId());
					}

					if (is_string($this->getStackQuestion()->getQuestionVariables()->get_errors()))
					{
						include_once "./Services/Utilities/classes/class.ilUtil.php";
						$this->setErrors($this->getStackQuestion()->getQuestionVariables()->get_errors());
					}
					$node->save();
				}
			}
		}

		//EXTRA info
		if (is_a($this->extra_info, 'assStackQuestionExtraInfo'))
		{
			if (!$this->extra_info->getSpecificId() OR $adding_to_test OR $importing_questions)
			{
				$this->extra_info->setSpecificId(-1);
				$this->extra_info->setQuestionId($this->getId());

			}
			$this->extra_info->save();
		} else
		{
			$this->extra_info = new assStackQuestionExtraInfo(-1, $this->getId());
			$this->extra_info->save();
		}
		if ($edit_question AND $adding_to_test == FALSE AND $importing_questions == FALSE)
		{
			return;
		}

		//TESTS
		foreach ($this->tests as $test)
		{
			$test->setTestId(-1);
			$test->setQuestionId($this->getId());
			$test->save();
			//INPUTS FOR TESTS
			foreach ($test->getTestInputs() as $input)
			{
				$input->setTestInputId(-1);
				$input->setQuestionId($this->getId());
				$input->save();
			}
			//EXPECTED FOR TESTS
			foreach ($test->getTestExpected() as $expected)
			{
				$expected->setTestExpectedId(-1);
				$expected->setQuestionId($this->getId());
				$expected->save();
			}
		}

		//DEPLOYED SEEDS
		if (is_array($this->deployed_seeds))
		{
			foreach ($this->deployed_seeds as $seed)
			{
				$seed->setSeedId(-1);
				$seed->setQuestionId($this->getId());
				$seed->save();
			}
		}

	}

	function beforeSyncWithOriginal($origQuestionId, $dupQuestionId, $origParentObjId, $dupParentObjId)
	{
		//Options
		if (is_a($this->options, 'assStackQuestionOptions'))
		{
			$this->options->setQuestionId($origQuestionId);
			$options = assStackQuestionOptions::_read($origQuestionId);
			$this->options->setOptionsId($options->getOptionsId());
		}

		//Inputs
		if (sizeof($this->inputs))
		{
			$inputs = assStackQuestionInput::_read($origQuestionId);

			//#18371 Delete Input if deleted in test
			foreach ($inputs as $original_ikey => $original_input)
			{
				//If key is in original but not in current test version, delete original
				if (!isset($this->inputs[$original_ikey]))
				{
					//Delete input
					$original_input->delete();
				}
			}

			foreach ($this->inputs as $key => $input)
			{
				if (is_a($input, 'assStackQuestionInput'))
				{
					$input->setQuestionId($origQuestionId);
					if (isset($inputs[$key]))
					{
						$orig_input = $inputs[$key];
						$input->setInputId($orig_input->getInputId());
					} else
					{
						$input->setInputId(-1);
					}
				}
			}
		}

		//PRT
		if (sizeof($this->potential_responses_trees))
		{
			$prts = assStackQuestionPRT::_read($origQuestionId);

			//#18371 Delete PRT if deleted in test
			foreach ($prts as $original_key => $original_prt)
			{
				//If key is in original but not in current test version, delete original
				if (!isset($this->potential_responses_trees[$original_key]))
				{
					//Delete PRT
					$original_prt->delete();

					//Delete nodes
					foreach ($original_prt->getPRTNodes() as $node_name => $node)
					{
						$node->delete();
					}
				}
			}

			foreach ($this->potential_responses_trees as $prt_key => $prt)
			{
				if (is_a($prt, 'assStackQuestionPRT'))
				{
					$prt->setQuestionId($origQuestionId);
					if (isset($prts[$prt_key]))
					{
						$orig_prt = $prts[$prt_key];
						$prt->setPRTId($orig_prt->getPRTId());
					} else
					{
						$prt->setPRTId(-1);
					}

					$nodes = $orig_prt->getPRTNodes();

					//POTENTIAL RESPONSE TREES NODES
					$new_prt_nodes = array();
					foreach ($prt->getPRTNodes() as $node_key => $node)
					{
						if (is_a($node, 'assStackQuestionPRTNode'))
						{
							$node->setQuestionId($origQuestionId);

							if (isset($nodes[$node_key]))
							{
								$orig_node = $nodes[$node_key];
								$node->setNodeId($orig_node->getNodeId());
							} else
							{
								$node->setNodeId(-1);
							}
							$new_prt_nodes[$node_key] = $node;
						}
					}

					$prt->setPRTNodes($new_prt_nodes);
				}
			}
		}

		//EXTRA info
		if (is_a($this->extra_info, 'assStackQuestionExtraInfo'))
		{
			$this->extra_info->setQuestionId($origQuestionId);
			$extra_info = assStackQuestionExtraInfo::_read($origQuestionId);
			$this->extra_info->setSpecificId($extra_info->getSpecificId());
		}

		//DEPLOYED SEEDS
		$seeds = assStackQuestionDeployedSeed::_read($origQuestionId);

		if (is_array($this->deployed_seeds))
		{
			foreach ($this->deployed_seeds as $seed_key => $seed)
			{
				if (isset($seeds[$seed_key]))
				{
					$orig_seed = $seeds[$seed_key];
					$seed->setSeedId($orig_seed->getSeedId());
				} else
				{
					$seed->setSeedId(-1);
				}
				$seed->setQuestionId($origQuestionId);
			}
		}
	}

	function beforeCopy($origQuestionId)
	{
		//Options
		if (is_a($this->options, 'assStackQuestionOptions'))
		{
			$this->options->setQuestionId($origQuestionId);
			$this->options->setOptionsId(-1);
		}

		//Inputs
		if (sizeof($this->inputs))
		{
			foreach ($this->inputs as $key => $input)
			{
				if (is_a($input, 'assStackQuestionInput'))
				{
					$input->setQuestionId($origQuestionId);
					$input->setInputId(-1);
				}
			}
		}

		//PRT
		if (sizeof($this->potential_responses_trees))
		{

			foreach ($this->potential_responses_trees as $prt_key => $prt)
			{
				if (is_a($prt, 'assStackQuestionPRT'))
				{
					$prt->setQuestionId($origQuestionId);
					$prt->setPRTId(-1);

					$nodes = $prt->getPRTNodes();

					//POTENTIAL RESPONSE TREES NODES

					$new_prt_nodes = array();
					foreach ($prt->getPRTNodes() as $node_key => $node)
					{
						if (is_a($node, 'assStackQuestionPRTNode'))
						{
							$node->setQuestionId($origQuestionId);
							$node->setNodeId(-1);
							$new_prt_nodes[$node_key] = $node;
						}
					}

					$prt->setPRTNodes($new_prt_nodes);
				}
			}
		}

		//EXTRA info
		if (is_a($this->extra_info, 'assStackQuestionExtraInfo'))
		{
			$this->extra_info->setQuestionId($origQuestionId);
			$this->extra_info->setSpecificId(-1);
		}

		//DEPLOYED SEEDS

		if (is_array($this->deployed_seeds))
		{
			foreach ($this->deployed_seeds as $seed_key => $seed)
			{
				$seed->setSeedId(-1);
				$seed->setQuestionId($origQuestionId);
			}
		}
	}

	/*
	 * LOAD FROM DB
	 */

	/**
	 * Gets all the data of an assStackQuestion from the DB
	 *
	 * @param integer $question_id A unique key which defines the question in the database
	 */
	public function loadFromDb($question_id)
	{
		if ($this->getId() != $question_id)
		{
			global $DIC;
			$db = $DIC->database();
			//load the basic question data
			$result = $db->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = " . $db->quote($question_id, 'integer'));

			$data = $db->fetchAssoc($result);
			$this->setId($question_id);
			$this->setTitle($data["title"]);
			$this->setComment($data["description"]);
			$this->setSuggestedSolution($data["solution_hint"]);
			$this->setOriginalId($data["original_id"]);
			$this->setObjId($data["obj_fi"]);
			$this->setAuthor($data["author"]);
			$this->setOwner($data["owner"]);
			$this->setPoints($data["points"]);

			require_once("./Services/RTE/classes/class.ilRTE.php");
			$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
			$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));

			//Load the specific assStackQuestion data from DB
			if ($question_id)
			{

				//load options
				$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionOptions.php');
				$this->setOptions(assStackQuestionOptions::_read($question_id));
				if (!is_a($this->getOptions(), 'assStackQuestionOptions'))
				{
					//Create options
					$options = new assStackQuestionOptions(-1, $question_id);
					$options->getDefaultOptions();
					$options->checkOptions(TRUE);
					$options->save();
					$this->setOptions($options);
				}

				//load inputs
				$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionInput.php');
				$this->setInputs(assStackQuestionInput::_read($question_id));
				if (sizeof($this->getInputs()) == 0)
				{
					//Create options
					$input = new assStackQuestionInput(-1, $question_id, "ans1", "algebraic", "");
					$input->getDefaultInput();
					$input->checkInput(TRUE);
					$input->save();
					$this->setInputs(array("ans1" => $input));
				}

				//load PRTs and PRT nodes
				$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionPRT.php');
				$this->setPotentialResponsesTrees(assStackQuestionPRT::_read($question_id));

				//load tests
				$this->getPlugin()->includeClass('model/ilias_object/test/class.assStackQuestionTest.php');
				$this->setTests(assStackQuestionTest::_read($question_id));

				//load seeds
				$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionDeployedSeed.php');
				$this->setDeployedSeeds(assStackQuestionDeployedSeed::_read($question_id));

				//load extra info
				$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionExtraInfo.php');
				$extra_info = assStackQuestionExtraInfo::_read($question_id);
				$this->setInstantValidation(assStackQuestionUtils::_useInstantValidation());

				// ERROR MESSAGE FOR QUESTION CREATED IN AN OLD VERSION.
				if (is_array($extra_info))
				{
					$extra_info_obj = new assStackQuestionExtraInfo(-1, $this->getId());
					$extra_info_obj->setHowToSolve(' ');
					$extra_info_obj->save();
				} else
				{
					$this->setExtraInfo($extra_info);
				}
			}

			// loads additional stuff like suggested solutions
			parent::loadFromDb($question_id);
		}
	}


	/*
     * GETTERS AND SETTERS
     */

	/**
	 * @return ilAssStackCasQuestionPlugin The plugin object
	 */
	public function getPlugin()
	{
		if ($this->plugin == null)
		{
			require_once "./Services/Component/classes/class.ilPlugin.php";
			$this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assStackQuestion");
		}

		return $this->plugin;
	}

	/**
	 * @return assStackQuestionOptions
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @return array
	 */
	public function getInputs($selector = '')
	{
		if ($selector)
		{
			return $this->inputs[$selector];
		} else
		{
			return $this->inputs;
		}
	}

	/**
	 * @return array
	 */
	public function getPotentialResponsesTrees()
	{
		return $this->potential_responses_trees;
	}

	/**
	 * @return array
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
	 * @return array
	 */
	public function getDeployedSeeds()
	{
		return $this->deployed_seeds;
	}

	/**
	 * @return int
	 */
	public function getSeed()
	{
		return $this->seed;
	}

	/**
	 * @return assStackQuestionStackQuestion
	 */
	public function getStackQuestion()
	{
		return $this->stack_question;
	}

	/**
	 * @return assStackQuestionExtraInfo
	 */
	public function getExtraInfo()
	{
		return $this->extra_info;
	}

	/**
	 * @param $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * @param $inputs
	 */
	public function setInputs($inputs, $input_name = "")
	{
		if ($input_name)
		{
			$this->inputs[$input_name] = $inputs;
		} else
		{
			$this->inputs = $inputs;
		}
	}

	/**
	 * @param $prts
	 */
	public function setPotentialResponsesTrees($prts, $prt_name = "")
	{
		if ($prt_name)
		{
			$this->potential_responses_trees[$prt_name] = $prts;
		} else
		{
			$this->potential_responses_trees = $prts;
		}
	}

	/**
	 * @param $tests
	 */
	public function setTests($tests)
	{
		$this->tests = $tests;
	}

	/**
	 * @param $deployed_seeds
	 */
	public function setDeployedSeeds($deployed_seeds)
	{
		$this->deployed_seeds = $deployed_seeds;
	}

	/**
	 * @param $seed
	 */
	public function setSeed($seed)
	{
		$this->seed = $seed;
	}

	/**
	 * @param $stack_question
	 */
	public function setStackQuestion($stack_question)
	{
		$this->stack_question = $stack_question;
	}

	/**
	 * @param $extra_info
	 */
	public function setExtraInfo($extra_info)
	{
		$this->extra_info = $extra_info;
	}


	/**
	 * @param boolean $instant_validation
	 */
	public function setInstantValidation($instant_validation)
	{
		$this->instant_validation = $instant_validation;
	}

	/**
	 * @return boolean
	 */
	public function getInstantValidation()
	{
		return $this->instant_validation;
	}


	/**
	 * Returns all the database tables related to this question type.
	 * @return array
	 */
	public function getAdditionalTableName()
	{
		$CAS_tables = array();
		$CAS_tables[] = 'xqcas_options';
		$CAS_tables[] = 'xqcas_inputs';
		$CAS_tables[] = 'xqcas_prts';
		$CAS_tables[] = 'xqcas_prt_nodes';
		$CAS_tables[] = 'xqcas_qtests';
		$CAS_tables[] = 'xqcas_qtest_inputs';
		$CAS_tables[] = 'xqcas_qtest_expected';
		$CAS_tables[] = 'xqcas_deployed_seeds';
		$CAS_tables[] = 'xqcas_extra_info';

		return $CAS_tables;
	}

	/**
	 * Returns the question type name.
	 * @return string
	 */
	public function getQuestionType()
	{
		return "assStackQuestion";
	}


	/**
	 * Collects all text in the question which could contain media objects
	 * These were created with the Rich Text Editor
	 * The collection is needed to delete unused media objects
	 */
	protected function getRTETextWithMediaObjects()
	{
		// question text, suggested solutions etc
		$collected = parent::getRTETextWithMediaObjects();

		if (isset($this->options))
		{
			$collected .= $this->options->getSpecificFeedback();
			$collected .= $this->options->getPRTCorrect();
			$collected .= $this->options->getPRTIncorrect();
			$collected .= $this->options->getPRTPartiallyCorrect();
		}

		if (isset($this->extra_info))
		{
			$collected .= $this->extra_info->getHowToSolve();
		}

		foreach ($this->potential_responses_trees as $prt)
		{
			foreach ($prt->getPRTNodes() as $node)
			{
				$collected .= $node->getTrueFeedback();
				$collected .= $node->getFalseFeedback();
			}
		}

		return $collected;
	}

	/*
	 * REQUIRED QUESTION METHODS
	 */

	/**
	 * @return bool
	 */
	function isComplete()
	{
		$isComplete = TRUE;

		//Check all inputs have a model answer
		if (is_array($this->getInputs()))
		{
			foreach ($this->getInputs() as $input_name => $input)
			{
				if (is_a($input, "assStackQuestionInput"))
				{
					if ($input->getTeacherAnswer() == "" OR $input->getTeacherAnswer() == " ")
					{

						$isComplete = FALSE;
					}
				}
			}
		} else
		{
			return FALSE;
		}

		//Check student answer is always filled in
		if (is_array($this->getPotentialResponsesTrees()))
		{
			if (is_a($input, "assStackQuestionPRT"))
			{
				foreach ($this->getPotentialResponsesTrees() as $prt_name => $prt)
				{
					foreach ($prt->getPRTNodes() as $node_name => $node)
					{
						if ($node->getStudentAnswer() == "" OR $node->getStudentAnswer() == " ")
						{
							$isComplete = FALSE;
						}
					}
				}
			}

			//Check teacher answer is always filled in
			foreach ($this->getPotentialResponsesTrees() as $prt_name => $prt)
			{
				foreach ($prt->getPRTNodes() as $node_name => $node)
				{
					if ($node->getTeacherAnswer() == "" OR $node->getTeacherAnswer() == " ")
					{
						$isComplete = FALSE;
					}
				}
			}
		} else
		{
			return FALSE;
		}


		return $isComplete;
	}

	/**
	 * @param int $active_id
	 * @param int $pass
	 * @param bool $obligationsAnswered
	 * @param bool $authorized
	 */
	protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
	{
		// TODO: Implement reworkWorkingData() method.
	}


	public function getAllQuestionsFromPool()
	{
		global $DIC;
		$db = $DIC->database();

		$q_type_id = $this->getQuestionTypeID();
		$question_id = $this->getId();

		$questions_array = array();

		if ($question_id > 0 AND $q_type_id)
		{
			$result = $db->queryF("SELECT question_id FROM qpl_questions AS qpl
									WHERE qpl.obj_fi = (SELECT obj_fi FROM qpl_questions WHERE question_id = %s)
									AND qpl.question_type_fi = %s", array('integer', 'integer'), array($question_id, $q_type_id));

			while ($row = $db->fetchAssoc($result))
			{
				$new_question_id = $row['question_id'];

				$ilias_question = new assStackQuestion();
				$ilias_question->loadFromDb($new_question_id);

				$questions_array[$new_question_id] = $ilias_question;
			}
		}

		return $questions_array;
	}

	public function getAllQuestionsFromTest()
	{
		global $DIC;
		$db = $DIC->database();

		$q_type_id = $this->getQuestionTypeID();
		$question_id = $this->getId();

		$questions_array = array();

		if ($question_id > 0 AND $q_type_id)
		{
			$result = $db->queryF("SELECT question_fi FROM tst_test_question AS tst INNER JOIN qpl_questions AS qpl
								WHERE tst.question_fi = qpl.question_id
								AND tst.test_fi = (SELECT test_fi FROM tst_test_question WHERE question_fi = %s)
								AND qpl.question_type_fi = %s", array('integer', 'integer'), array($question_id, $q_type_id));

			while ($row = $db->fetchAssoc($result))
			{
				$new_question_id = $row['question_fi'];

				$ilias_question = new assStackQuestion();
				$ilias_question->loadFromDb($new_question_id);

				$questions_array[$new_question_id] = $ilias_question;
			}
		}

		return $questions_array;
	}

	public function getSolutionSubmit()
	{
		//RETURN DATA FROM POST
		$user_response_from_post = $_POST;
		unset($user_response_from_post["formtimestamp"]);
		unset($user_response_from_post["cmd"]);

		return assStackQuestionUtils::_adaptUserResponseTo($user_response_from_post, $this->getId(), "only_input_names");
	}

	public function calculateReachedPointsForSolution($found_values)
	{
		$points = 0.0;
		foreach ($this->getStackQuestion()->getPRTResults() as $prt_name => $results)
		{
			$points = $points + $results['points'];
		}

		return $points;
	}


	/******************************************************************
	 *  Interface methods of iQuestionCondition (for use in FormATest)
	 *****************************************************************/

	/**
	 * Get all available operations for a specific question
	 *
	 * @param $expression
	 *
	 * @internal param string $expression_type
	 * @return array
	 */
	public function getOperators($expression)
	{
		require_once "./Modules/TestQuestionPool/classes/class.ilOperatorsExpressionMapping.php";

		return ilOperatorsExpressionMapping::getOperatorsByExpression($expression);
	}

	/**
	 * Get all available expression types for a specific question
	 *
	 * @return array
	 */
	public function getExpressionTypes()
	{
		return array(iQuestionCondition::PercentageResultExpression);
	}

	/**
	 * Get the user solution for a question by active_id and the test pass
	 *
	 * @param int $active_id
	 * @param int $pass
	 *
	 * @return ilUserQuestionResult
	 */
	public function getUserQuestionResult($active_id, $pass)
	{
		require_once './Modules/TestQuestionPool/classes/class.ilUserQuestionResult.php';

		$result = new ilUserQuestionResult($this, $active_id, $pass);

		$points = (float)$this->calculateReachedPoints($active_id, $pass);
		$max_points = (float)$this->getMaximumPoints();
		$result->setReachedPercentage(($points / $max_points) * 100);

		return $result;
	}

	/**
	 * If index is null, the function returns an array with all anwser options
	 * Else it returns the specific answer option
	 *
	 * @param null|int $index
	 *
	 * @return array|ASS_AnswerSimple
	 */
	public function getAvailableAnswerOptions($index = null)
	{
		return array();
	}

	public function getAnswers()
	{
		return array();
	}


	protected function savePreviewData(ilAssQuestionPreviewSession $previewSession)
	{
		$submittedAnswer = $this->getSolutionSubmit();
		if (!empty($submittedAnswer))
		{
			$previewSession->setParticipantsSolution($submittedAnswer);
		}
	}

	public function getQuestionSeedForCurrentTestRun($active_id, $pass)
	{
		global $DIC;
		$db = $DIC->database();

		$question_seed = NULL;


		if (is_null($pass))
		{
			require_once './Modules/Test/classes/class.ilObjTest.php';
			$pass = ilObjTest::_getPass($active_id);
		}

		//Determine if seed already exists and return it;
		if (sizeof($this->getPotentialResponsesTrees()))
		{
			foreach ($this->getPotentialResponsesTrees() as $prt)
			{
				//Solve https://www.ilias.de/mantis/view.php?id=21536 bug
				$query = $db->query("SELECT tst_solutions.value2 FROM tst_solutions WHERE active_fi = " . $db->quote($active_id, 'integer') . " AND pass = " . $db->quote($pass, 'integer') . " AND value1 = 'xqcas_prt_" . $prt->getPRTName() . "_seed'" . " AND question_fi = " . $this->getId());
				$data = $db->fetchAssoc($query);
				if ($data["value2"])
				{
					$question_seed = $data["value2"];

					return $question_seed;
				}
			}
		}

		//Create seed for test run in case it doesn't exist
		if ($question_seed == NULL)
		{
			//create stack question
			$this->plugin->includeClass("model/class.assStackQuestionStackQuestion.php");
			$this->setStackQuestion(new assStackQuestionStackQuestion($active_id, $pass));
			$this->getStackQuestion()->init($this);

			//get seed and save it to DB
			$question_seed = $this->getStackQuestion()->getSeed();
			if (sizeof($this->getPotentialResponsesTrees()))
			{
				foreach ($this->getPotentialResponsesTrees() as $prt)
				{
					//If ILIAS 5.1  or 5.0 using intermediate
					if (method_exists($this, "getUserSolutionPreferingIntermediate"))
					{
						//5.1
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt->getPRTName() . '_seed', $this->getStackQuestion()->getSeed(), TRUE);
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt->getPRTName() . '_seed', $this->getStackQuestion()->getSeed(), FALSE);
					} else
					{
						//5.0
						$this->saveCurrentSolution($active_id, $pass, 'xqcas_prt_' . $prt->getPRTName() . '_seed', $this->getStackQuestion()->getSeed());
					}
				}
			}

			return $question_seed;
		}

	}


	/**
	 * Lookup if an authorized or intermediate solution exists (specific for STACK question: don't lookup seeds)
	 * @param    int $activeId
	 * @param    int $pass
	 * @return    array        ['authorized' => bool, 'intermediate' => bool]
	 */
	public function lookupForExistingSolutions($activeId, $pass)
	{
		global $ilDB;

		$return = array('authorized' => false, 'intermediate' => false);

		$query = "
			SELECT authorized, COUNT(*) cnt
			FROM tst_solutions
			WHERE active_fi = " . $ilDB->quote($activeId, 'integer') . "
			AND question_fi = " . $ilDB->quote($this->getId(), 'integer') . "
			AND pass = " . $ilDB->quote($pass, 'integer') . "
			AND value1 not like '%_seed'
			AND value2 is not null
			AND value2 <> ''
			GROUP BY authorized
		";
		$result = $ilDB->query($query);

		while ($row = $ilDB->fetchAssoc($result))
		{
			if ($row['authorized'])
			{
				$return['authorized'] = $row['cnt'] > 0;
			} else
			{
				$return['intermediate'] = $row['cnt'] > 0;
			}
		}

		return $return;
	}

	/**
	 * Remove an existing solution without removing the variables (specific for STACK question: don't delete seeds)
	 * @param    int $activeId
	 * @param    int $pass
	 * @return int
	 */
	public function removeExistingSolutions($activeId, $pass)
	{
		global $DIC;
		$ilDB = $DIC->database();

		$query = "
			DELETE FROM tst_solutions
			WHERE active_fi = " . $ilDB->quote($activeId, 'integer') . "
			AND question_fi = " . $ilDB->quote($this->getId(), 'integer') . "
			AND pass = " . $ilDB->quote($pass, 'integer') . "
			AND value1 not like '%_seed'
		";

		return $ilDB->manipulate($query);
	}

	/**
	 * This method solves the problems of the previous versions where all seed entries on the DB were not deleted.
	 * @param $activeId
	 * @param $pass
	 * @return int
	 */
	public function removeOldSeeds($activeId, $pass)
	{
		global $DIC;
		$ilDB = $DIC->database();

		$query = "
			DELETE FROM tst_solutions
			WHERE active_fi = " . $ilDB->quote($activeId, 'integer') . "
			AND question_fi = " . $ilDB->quote($this->getId(), 'integer') . "
			AND pass = " . $ilDB->quote($pass, 'integer') . "
			AND value1 like '%_seed'
		";

		return $ilDB->manipulate($query);
	}


	/**
	 * @return mixed
	 */
	public function getErrors()
	{
		return $_SESSION["stack_authoring_errors"][$this->getId()];
	}

	/**
	 * @param mixed $errors
	 */
	public function setErrors($error)
	{
		$_SESSION["stack_authoring_errors"][$this->getId()][] = $error;
	}


}