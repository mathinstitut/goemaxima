<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionStackFactory.php';
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionInitialization.php';

/**
 * This class simulates the STACK question class.
 * ALL VARIABLES OF THIS CLASS CONTAINS INFORMATION IN THE STACK MODE
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionStackQuestion
{

	/**
	 * Factory for STACK object creation
	 * @var assStackQuestionStackFactory
	 */
	private $stack_factory;

	/**
	 *
	 * @var integer
	 */
	private $question_id;

	/**
	 * @var string STACK specific: variables, as authored by the teacher.
	 */
	private $question_variables;

	/**
	 * @var string STACK specific: question note, as authored by the teacher.
	 */
	private $question_note;

	/**
	 * @var array STACK specific: string name as it appears in the question text => stack_input
	 */
	private $inputs = array();

	/**
	 * @var array stack_potentialresponse_tree STACK specific: respones tree number => ...
	 */
	private $prts = array();

	/**
	 * @var stack_options STACK specific: question-level options.
	 */
	private $options;

	/**
	 * @var int STACK specific: seeds Maxima's random number generator.
	 */
	private $seed = NULL;

	//Instantiated variables

	/**
	 * @var array stack_cas_session STACK specific: session of variables.
	 */
	private $session;

	/**
	 * How many entries have session
	 * @var integer
	 */
	private $session_length;

	/**
	 * @var array stack_cas_session STACK specific: session of variables.
	 */
	private $question_note_instantiated;

	/**
	 * @var string instantiated version of questiontext.
	 * Initialised in start_attempt / apply_attempt_state.
	 */
	private $question_text_instantiated;

	/**
	 * @var string instantiated version of specificfeedback.
	 * Initialised in start_attempt / apply_attempt_state.
	 */
	private $specific_feedback_instantiated;

	/**
	 * @var string instantiated version of prtcorrect.
	 * Initialised in start_attempt / apply_attempt_state.
	 */
	private $prt_correct_instantiated;

	/**
	 * @var string instantiated version of prtpartiallycorrect.
	 * Initialised in start_attempt / apply_attempt_state.
	 */
	private $prt_partially_correct_instantiated;

	/**
	 * @var string instantiated version of prtincorrect.
	 * Initialised in start_attempt / apply_attempt_state.
	 */
	private $prt_incorrect_instantiated;

	/**
	 * @var string instatieted version of general feedback
	 */
	private $general_feedback;

	/**
	 * The next four fields cache the results of some expensive computations.
	 * The chache is only vaid for a particular response, so we store the current
	 * response, so that we can clearn the cached information in the result changes.
	 * See {@link validate_cache()}.
	 * @var array
	 */
	private $last_response = NULL;

	/**
	 * @var bool like $lastresponse, but for the $acceptvalid argument to {@link validate_cache()}.
	 */
	private $last_accept_valid = NULL;

	/**
	 * @var array input name => stack_input_state.
	 * This caches the results of validate_student_response for $lastresponse.
	 */
	private $input_states = array();

	/**
	 * @var array prt name => result of evaluate_response, if known.
	 */
	private $prt_results = array();

	/**
	 * @var int active id
	 */
	private $active_id;

	/**
	 * @var int pass
	 */
	private $pass;

	/**
	 * @var float points
	 */
	private $points;

	/**
	 * @var float penalty
	 */
	private $penalty;

	/**
	 * @var bool
	 */
	private $instant_validation;


	/**
	 * If active_id and pass are given, Seed is created (if needed) with them.
	 * @param int $active_id
	 * @param int $pass
	 */
	public function __construct($active_id = NULL, $pass = NULL)
	{
		if ($active_id == NULL OR $pass == NULL)
		{
			$this->setActiveId(-1);
			$this->setPass(-1);
		} else
		{
			$this->setActiveId($active_id);
			$this->setPass($pass);
		}
		$this->setStackFactory(new assStackQuestionStackFactory());
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method translate data from ILIAS STACK Question into Stack format.
	 * @param assStackQuestion $ilias_question
	 * @param bool $evaluation_mode
	 * @param int $maintain_seed (v1.6+ if positive integer, use it as seed)
	 */
	public function init(assStackQuestion $ilias_question, $step_to_stop = '', $maintain_seed = -1, $authorized = TRUE, $instanciate = FALSE)
	{
		//Step 0: set question id and points and set if instant validation is shown
		$this->setQuestionId($ilias_question->getId());
		$this->setPoints($ilias_question->getPoints());
		if (is_object($ilias_question->getExtraInfo()))
		{
			$this->setPenalty($ilias_question->getExtraInfo()->getPenalty());
		} else
		{
			$this->setPenalty(0);
		}

		$this->setInstantValidation($ilias_question->getInstantValidation());

		//Step 1: Create options and question id
		$this->createOptions($ilias_question->getOptions());
		//Step 2: Create seed
		$this->createSeed($ilias_question, $maintain_seed, $authorized);
		//Step 3: Create Question variables
		$this->createQuestionVariables($ilias_question->getOptions()->getQuestionVariables());
		//Step 4: Create Session
		$this->createSession();
		//Step 5: Create Inputs
		$error = $this->createInputs($ilias_question);

		//Step 6: Add correct answer as sesion value for all inputs
		$this->addTeacherAnswersToSession($ilias_question);
		//Step 7: Calculate Session lenght;
		$this->calculateSessionLenght();
		//Step 8: Allow inputs to update themselves based on the model answers.
		$this->adaptInputs();

		//Step 9: Prepare all CAS Text and Instantiate the session
		//Conversion can be stopped here when only display mode
		if ($step_to_stop == 8)
		{
			$this->prepareAllCasTexts($ilias_question, TRUE, $instanciate);

			return TRUE;
		} else
		{
			$this->prepareAllCasTexts($ilias_question, "", $instanciate);
		}

		if ($step_to_stop == 9)
		{
			return TRUE;
		}

		//Step 10: Create PRT for evaluation purposes
		$this->createPotentialResponseTrees($ilias_question);
	}

	/**
	 * Creates stack_options from an assStackQuestionOptions object.
	 * @param assStackQuestionOptions $ilias_options
	 */
	public function createOptions(assStackQuestionOptions $ilias_options)
	{
		$parameters = array( // Array of public class settings for this class.
			'display' => array('type' => 'list', 'value' => 'LaTeX', 'strict' => true, 'values' => array('LaTeX', 'MathML', 'String'), 'caskey' => 'OPT_OUTPUT', 'castype' => 'string',), 'multiplicationsign' => array('type' => 'list', 'value' => $ilias_options->getMultiplicationSign(), 'strict' => true, 'values' => array('dot', 'cross', 'none'), 'caskey' => 'make_multsgn', 'castype' => 'fun',), 'complexno' => array('type' => 'list', 'value' => $ilias_options->getComplexNumbers(), 'strict' => true, 'values' => array('i', 'j', 'symi', 'symj'), 'caskey' => 'make_complexJ', 'castype' => 'fun',), 'inversetrig' => array('type' => 'list', 'value' => $ilias_options->getInverseTrig(), 'strict' => true, 'values' => array('cos-1', 'acos', 'arccos'), 'caskey' => 'make_arccos', 'castype' => 'fun',), 'floats' => array('type' => 'boolean', 'value' => 1, 'strict' => true, 'values' => array(), 'caskey' => 'OPT_NoFloats', 'castype' => 'ex',), 'sqrtsign' => array('type' => 'boolean', 'value' => $ilias_options->getSqrtSign(), 'strict' => true, 'values' => array(), 'caskey' => 'sqrtdispflag', 'castype' => 'ex',), 'simplify' => array('type' => 'boolean', 'value' => $ilias_options->getQuestionSimplify(), 'strict' => true, 'values' => array(), 'caskey' => 'simp', 'castype' => 'ex',), 'assumepos' => array('type' => 'boolean', 'value' => $ilias_options->getAssumePositive(), 'strict' => true, 'values' => array(), 'caskey' => 'assume_pos', 'castype' => 'ex',), 'matrixparens' => array('type' => 'list', 'value' => $ilias_options->getMatrixParens(), 'strict' => true, 'values' => array('[', '(', '', '{', '|'), 'caskey' => 'lmxchar', 'castype' => 'exs',));

		$this->setOptions($this->getStackFactory()->get("options", $parameters));
	}

	/**
	 * Sets the seed used in randomisation for the current question, depending if there are deployed seeds or not.
	 * And also depending if questions is shown in a test or in a preview.
	 * @param assStackQuestion $ilias_question (v1.6+ If negative: normal procedure, if positive set seed as value)
	 * @param $seed (v1.6+ If negative: normal procedure, if positive set seed as value)
	 */
	public function createSeed($ilias_question, $seed = -1, $authorized = TRUE)
	{
		if (is_a($ilias_question, "assStackQuestion"))
		{
			global $DIC;

			$lng = $DIC->language();
			switch (sizeof($ilias_question->getDeployedSeeds()))
			{
				//No deployed seeds for this question.
				case 0:
					//Question has randomisation
					if (assStackQuestionUtils::_questionHasRandomVariables($ilias_question->getOptions()->getQuestionVariables()))
					{
						//If we are in a test, Get seed from active_id, pass and question_id
						if ($this->getActiveId() >= 0 AND $this->getPass() >= 0)
						{
							if ($seed == -1)
							{
								$first_prt = array_shift(array_values($ilias_question->getPotentialResponsesTrees()));
								$seed = assStackQuestionUtils::_getSeedFromTest($this->getQuestionId(), $this->getActiveId(), $this->getPass(), $first_prt->getPRTName());
								if ($seed == NULL)
								{
									$seed = rand(1, 100);
								}
							}
						} else
						{
							//We are in a preview, use seed from session and store it.
							//v1.6+ Seed management in preview change.
							if ($_GET['cmd'] != 'deployedSeedsManagement' AND !isset($_POST['deployed_seed']))
							{
								if ($seed > 0)
								{
									$_SESSION['q_seed_for_preview_' . $this->getQuestionId() . ''] = $seed;
								} else
								{
									//#22714 $chosen_seed = $seeds[array_rand($seeds, 1)]  = $chosen_seed->getSeed();
									$seed = $_SESSION['q_seed_for_preview_' . $this->getQuestionId() . ''];
								}
								//Send information message about random questions in previews.
								if ($_GET['cmdClass'] != 'iltestoutputgui')
								{
									ilUtil::sendInfo($lng->txt('qpl_qst_xqcas_randomisation_info'));
								}
							}
						}
						$this->setSeed((int)$seed);
					} else
					{
						//Question has not randomisation
						$this->setSeed(0);
					}
					break;
				//Only one deployed seed, use it always.
				case 1:
					$seeds = $ilias_question->getDeployedSeeds();
					$this->setSeed($seeds[0]->getSeed());
					break;
				//More than one deployed seed.
				default:
					$seeds = $ilias_question->getDeployedSeeds();
					//Question has randomisation
					if (assStackQuestionUtils::_questionHasRandomVariables($ilias_question->getOptions()->getQuestionVariables()))
					{
						//If we are in a test, Get seed from active_id, pass and question_id
						if ($this->getActiveId() >= 0 AND $this->getPass() >= 0)
						{
							//get Seed from DB, if not, create new one
							if ($seed == -1)
							{
								$first_prt = array_shift(array_values($ilias_question->getPotentialResponsesTrees()));
								$seed = assStackQuestionUtils::_getSeedFromTest($this->getQuestionId(), $this->getActiveId(), $this->getPass(), $first_prt->getPRTName());
								if ($seed == NULL)
								{
									$seed = $seeds[array_rand($seeds, 1)]->getSeed();
								}
							}
						} else
						{
							//We are in a preview, use seed from session and store it.
							//v1.6+ Seed management in preview change.
							if (($_GET['cmd'] != 'deployedSeedsManagement' AND !isset($_POST['deployed_seed'])))
							{
								if ($seed > 0)
								{
									$_SESSION['q_seed_for_preview_' . $this->getQuestionId() . ''] = $seed;
								} else
								{
								//choose seed
									//#22714 $chosen_seed = $seeds[array_rand($seeds, 1)]  = $chosen_seed->getSeed();
									$seed = $_SESSION['q_seed_for_preview_' . $this->getQuestionId() . ''];
								}
								//Send information message about random questions in previews.
								if ($_GET['cmdClass'] != 'iltestoutputgui')
								{
									ilUtil::sendInfo($lng->txt('qpl_qst_xqcas_randomisation_info_2'));
								}
							}
						}
						$this->setSeed((int)$seed);
					} else
					{
						//Question has not randomisation
						$this->setSeed(0);
					}
					break;
			}
		}
	}

	/**
	 * @param $question_variables_raw string with question variables
	 */
	public function createQuestionVariables($question_variables_raw)
	{
		$question_variables_parameters = array('raw' => $question_variables_raw, 'options' => $this->getOptions(), 'seed' => $this->getSeed(), 'security' => 't');

		$question_variables = $this->getStackFactory()->get("cas_keyval", $question_variables_parameters);
		$question_variables->instantiate();

		$this->setQuestionVariables($question_variables);
	}

	/**
	 * Creates the session for this question
	 */
	public function createSession()
	{
		$this->setSession($this->getQuestionVariables()->get_session());
	}

	/**
	 * Instantiates the session for this question
	 */
	public function instantiateSession()
	{
		$this->getSession()->instantiate();
	}

	/**
	 * Create stack_input objects from assStackQuestionInput
	 * @param assStackQuestion $question
	 * @throws assStackQuestionException
	 */
	public function createInputs(assStackQuestion $question)
	{
		$stack_inputs = array();
		foreach ($question->getInputs() as $input_name => $input)
		{
			//Boolean inputs doesn't accept most of the specific parameters so send blank array as specific parameters
			if (is_a($input, "assStackQuestionInput"))
			{
				if ($input->getInputType() != 'boolean' AND $input->getInputType() != 'singlechar')
				{
					$specific_parameters = array('mustVerify' => $input->getMustVerify(), //As seen in STACK hideFeedback var is the negation of ShowValidation.
						//'hideFeedback' => !$input->getShowValidation()
					 'boxWidth' => $input->getBoxSize(), 'strictSyntax' => $input->getStrictSyntax(), 'insertStars' => $input->getInsertStars(), 'syntaxHint' => $input->getSyntaxHint(), 'forbidWords' => $input->getForbidWords(), 'allowWords' => $input->getAllowWords(), 'forbidFloats' => $input->getForbidFloat(), 'lowestTerms' => $input->getRequireLowestTerms(), 'sameType' => $input->getCheckAnswerType(), 'options' => $input->getOptions(), 'showValidation' => $input->getShowValidation());
				} else
				{
					$specific_parameters = array();
				}
				if ($input->getTeacherAnswer() == " " OR $input->getTeacherAnswer() == "")
				{
					return $input_name;
				}
				//26640 is teacher answer is a variable should be converted before setting it
				$all_session_keys = $this->getSession()->get_all_keys();
				$teacher_answer = $input->getTeacherAnswer();
				foreach($all_session_keys as $key){
					if($input->getTeacherAnswer() == $key){
						$teacher_answer = $this->getSession()->get_value_key($key);
					}
				}
				$input_parameters = array('type' => $input->getInputType(), 'name' => $input_name, 'teacheranswer' => $teacher_answer, 'options' => $this->getOptions(), 'parameters' => $specific_parameters);
				$stack_inputs[$input_name] = $this->getStackFactory()->get("input_object", $input_parameters);

			}
			if (sizeof($stack_inputs))
			{
				$this->setInputs($stack_inputs);
			}
		}

		return TRUE;
	}

	/**
	 * Add the teacher answer to the session
	 * @param assStackQuestion $question
	 */
	public function addTeacherAnswersToSession(assStackQuestion $question)
	{
		$response = array();
		foreach ($question->getInputs() as $name => $input)
		{
			if (is_a($input, "assStackQuestionInput"))
			{
				$teacher_answer_parameters = array('string' => (string)$input->getTeacherAnswer(), 'key' => $name, 'security' => 't', 'syntax' => $input->getStrictSyntax(), 'stars' => $input->getInsertStars());
				$teacher_answer_casstring = $this->getStackFactory()->get("cas_casstring_from_parameters", $teacher_answer_parameters);
				$teacher_answer_casstring->validate('t');
				$teacher_answer_casstring->set_key($name);
				$response[$name] = $teacher_answer_casstring;
			}
		}
		$session = $this->getSession();
		$session->add_vars($response);
		$session->instantiate();
		$this->setSession($session);
	}

	/**
	 * Calculates session length for pruning purposes.
	 */
	public function calculateSessionLenght()
	{
		$this->setSessionLength(count($this->getSession()->get_session()));
	}

	/**
	 * This function prepares all CAS Text needed for the question evaluation and feedback display.
	 * @param $ilias_question
	 * @throws stack_exception
	 */
	public function prepareAllCasTexts($ilias_question, $stop_here = FALSE, $instanciate = FALSE)
	{
		global $DIC;

		$lng = $DIC->language();

		if ($instanciate)
		{
			$this->setSession($this->getSession()->instantiate());
		}

		//1. Prepare question text.
		if ($ilias_question->getQuestion())
		{
			$question_text_parameters = array('raw' => $ilias_question->getQuestion(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$question_text = $this->getStackFactory()->get('cas_text', $question_text_parameters);

			$this->setQuestionTextInstantiated($question_text["text"]);
			if ($question_text["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_question_text") . ": " . $question_text["errors"]);
			}

			//Stop here when only display mode
			if ($stop_here)
			{
				return;
			}
		}

		//2. Prepare Specific feedback.
		if ($ilias_question->getOptions()->getSpecificFeedback())
		{
			$specific_feedback_parameters = array('raw' => $ilias_question->getOptions()->getSpecificFeedback(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$specific_feedback = $this->getStackFactory()->get('cas_text', $specific_feedback_parameters);
			$this->setSpecificFeedbackInstantiated($specific_feedback["text"]);
			if ($specific_feedback["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_specific_feedback") . ": " . $specific_feedback["errors"]);
			}
		}

		//3. Prepare Question Note.
		if ($ilias_question->getOptions()->getQuestionNote())
		{
			$question_note_parameters = array('raw' => $ilias_question->getOptions()->getQuestionNote(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$question_note = $this->getStackFactory()->get('cas_text', $question_note_parameters);
			$this->setQuestionNoteInstantiated($question_note["text"]);
			if ($question_note["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_question_note") . ": " . $question_note["errors"]);
			}
		}

		//4. Prepare PRT correct feedback.
		if ($ilias_question->getOptions()->getPRTCorrect())
		{
			$PRT_correct_parameters = array('raw' => $ilias_question->getOptions()->getPRTCorrect(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$PRT_correct = $this->getStackFactory()->get('cas_text', $PRT_correct_parameters);
			$this->setPRTCorrectInstantiated($PRT_correct["text"]);

			if ($PRT_correct["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_prt_correct") . ": " . $PRT_correct["errors"]);
			}
		}

		//5. Prepare PRT partially correct feedback.
		if ($ilias_question->getOptions()->getPRTPartiallyCorrect())
		{
			$PRT_partially_correct_parameters = array('raw' => $ilias_question->getOptions()->getPRTPartiallyCorrect(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$PRT_partially_correct = $this->getStackFactory()->get('cas_text', $PRT_partially_correct_parameters);
			$this->setPRTPartiallyCorrectInstantiated($PRT_partially_correct["text"]);

			if ($PRT_partially_correct["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_prt_partially_correct") . ": " . $PRT_partially_correct["errors"]);
			}
		}

		//6. Prepare PRT incorrect feedback.
		if ($ilias_question->getOptions()->getPRTIncorrect())
		{
			$PRT_incorrect_parameters = array('raw' => $ilias_question->getOptions()->getPRTIncorrect(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$PRT_incorrect = $this->getStackFactory()->get('cas_text', $PRT_incorrect_parameters);
			$this->setPRTIncorrectInstantiated($PRT_incorrect["text"]);

			if ($PRT_incorrect["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_prt_incorrect") . ": " . $PRT_incorrect["errors"]);
			}
		}

		//7. Prepare How to solve.
		if (is_object($ilias_question->getExtraInfo()) and $ilias_question->getExtraInfo()->getHowToSolve())
		{
			$general_feedback_parameters = array('raw' => $ilias_question->getExtraInfo()->getHowToSolve(), 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => 1);
			$general_feedback = $this->getStackFactory()->get('cas_text', $general_feedback_parameters);
			$this->setGeneralFeedback($general_feedback["text"]);

			if ($general_feedback["errors"])
			{
				$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_how_to_solve") . ": " . $general_feedback["errors"]);
			}
		}

		//8 Add error message for question variables:
		if ($this->getQuestionVariables()->get_errors())
		{
			$ilias_question->setErrors($lng->txt("qpl_qst_xqcas_error_in_question_variables") . ": " . $this->getQuestionVariables()->get_errors());
		}

	}

	/**
	 * Give all the input elements a chance to configure themselves given the
	 * teacher's model answers.
	 */
	public function adaptInputs()
	{
		foreach ($this->getInputs() as $name => $input)
		{
			$teacheranswer = $this->getSession()->get_value_key($name);
			$input->adapt_to_model_answer($teacheranswer);
		}
	}

	/**
	 * Get the results of validating one of the input elements.
	 * @param string $name the name of one of the input elements.
	 * @param array $response the response.
	 * @return stack_input_state the result of calling validate_student_response() on the input.
	 */
	public function getInputState($name, $response, $forbiddenkeys = '')
	{
		if (!is_a($this->getSession(), "stack_cas_session"))
		{
			$this->createSession();
		}

		$this->validateCache($response, NULL);

		if (array_key_exists($name, $this->getInputStates()))
		{
			return $this->getInputStates($name);
		}

		// The student's answer may not contain any of the variable names with which
		// the teacher has defined question variables.   Otherwise when it is evaluated
		// in a PRT, the student's answer will take these values.   If the teacher defines
		// 'ta' to be the answer, the student could type in 'ta'!  We forbid this.

		if ($forbiddenkeys)
		{
			$forbiddenwords = explode(',', $forbiddenkeys);
			$forbiddenkeys = array_merge($this->getSession()->get_all_keys(), $forbiddenwords);
		}
		$teacheranswer = $this->getSession()->get_value_key($name);
		if (array_key_exists($name, $this->getInputs()))
		{
			$final_response = array();
			switch (get_class($this->getInputs($name)))
			{
				case "stack_matrix_input":
					$final_response = $response;
					break;
				case "stack_algebraic_input":
					$final_response = $response;
					break;
				case "stack_units_input":
					$final_response = $response;
					break;
				default:
					$final_response = $response;
					break;
			}

			$this->setInputStates($this->getInputs($name)->validate_student_response($final_response, $this->getOptions(), $teacheranswer, $forbiddenkeys), $name);

			return $this->getInputStates($name);
		}

		return '';
	}

	/**
	 * Make sure the cache is valid for the current response. If not, clear it.
	 */
	public function validateCache($response, $acceptvalid = NULL)
	{

		if (is_null($this->getLastResponse()))
		{
			// Nothing cached yet. No worries.
			$this->setLastResponse($response);
			$this->setLastAcceptValid($acceptvalid);

			return;
		}

		if ($this->getLastResponse() == $response && ($this->getLastAcceptValid() === NULL || $acceptvalid === NULL || $this->getLastAcceptValid() === $acceptvalid))
		{
			if ($this->getLastAcceptValid() === NULL)
			{
				$this->setLastAcceptValid($acceptvalid);
			}

			return; // Cache is good.
		}

		// Clear the cache.
		$this->setLastResponse($response);
		$this->setLastAcceptValid($acceptvalid);
		$this->setInputStates(array());
		$this->setPRTResults(array());
	}

	/**
	 * @param assStackQuestion $ilias_question
	 */
	public function createPotentialResponseTrees(assStackQuestion $ilias_question)
	{
		//Transform ILIAS PRT into STACK PRT and set as $this->prts.
		$stack_PRTs = array();
		foreach ($ilias_question->getPotentialResponsesTrees() as $ilias_PRT)
		{
			if (is_a($ilias_PRT, "assStackQuestionPRT") AND $ilias_PRT->checkPRT(TRUE, TRUE))
			{
				$stack_PRTs[$ilias_PRT->getPRTName()] = $this->getStackFactory()->get("potentialresponse_tree", $ilias_PRT);
			}
		}
		$this->setPRTs($stack_PRTs);
	}

	/**
	 * Creates a blank PRT state with weight $weight and the errors
	 * @param float $weight
	 * @param string $errors
	 * @return stack_potentialresponse_tree_state
	 */
	public function createBlankPRTState($weight, $errors, $answer_note = '')
	{
		//Prepare data for prt
		$prts_data = array();
		$prts_data['weight'] = $weight;
		$prts_data['valid'] = FALSE;
		$prts_data['score'] = NULL;
		$prts_data['penalty'] = NULL;
		$prts_data['errors'] = $errors;
		$prts_data['answernote'] = $answer_note;
		$prts_data['feedback'] = '';

		return $this->getStackFactory()->get('potentialresponse_tree_state_blank', $prts_data);
	}

	/**
	 * This function traverse the PRT result in order to determine the points obtained
	 * Sets the points th each PRT result and also the global calification
	 */
	public function calculatePoints($test_mode = FALSE, $active_id = NULL, $pass = NULL, $question = NULL)
	{
		$max_weight = 0.0;
		$reached_points = 0.0;

		foreach ($this->getPRTResults() as $prt_evaluation_data)
		{
			$max_weight += $prt_evaluation_data['state']->__get('weight');
		}

		$time = time();

		foreach ($this->getPRTResults() as $prt_name => $prt_evaluation_data)
		{

			$prt_points = ((($prt_evaluation_data['state']->__get('score') /* - ($prt_evaluation_data['state']->__get('penalty')*/) * $prt_evaluation_data['state']->__get('weight')) * $this->getPoints()) / $max_weight;
			$reached_points += $prt_points;
			$this->setPRTResults($prt_points, $prt_name, 'points');
			if ($test_mode == TRUE AND $active_id != NULL)
			{
				//$question->saveWorkingDataValue($active_id, $pass, 'xqcas_prt_' . $prt_name . '_name', $prt_name, $prt_points, $time, NULL, 0);
			}
		}

		$this->reached_points = $reached_points;
	}

	/**
	 * Returns the question note for the current seed
	 * @param int $seed
	 * @param string $question_variables_raw
	 * @param string $question_note_raw
	 * @return bool|string
	 */
	public function getQuestionNoteForSeed($seed, $question_variables_raw, $question_note_raw, $question_id)
	{
		$ilias_question = new assStackQuestion();
		$ilias_question->loadFromDb($question_id);
		//Step #0: comprobation of options
		if (!is_a($this->getOptions(), 'stack_options'))
		{
			return FALSE;
		}
		//Step #1: Create seed
		$this->createSeed($ilias_question, $seed);
		//Step #2: Create Question variables
		$this->createQuestionVariables($question_variables_raw);
		//Step #3: Create Session
		$this->createSession();
		//Step #4 Prepare Question Note.
		if ($question_note_raw)
		{
			$question_note_parameters = array('raw' => $question_note_raw, 'session' => $this->getSession(), 'seed' => $this->getSeed(), 'security' => 't', 'syntax' => FALSE, 'stars' => TRUE);
			$question_note = $this->getStackFactory()->get('cas_text', $question_note_parameters);

			return $question_note["text"];
		} else
		{
			return FALSE;
		}
	}

	/**
	 * Add all the question variables to a give CAS session. This can be used to
	 * initialise that session, so expressions can be evaluated in the context of
	 * the question variables.
	 * @param stack_cas_session $session the CAS session to add the question variables to.
	 */
	public function addQuestionVarsToSession(stack_cas_session $session)
	{
		$session->merge_session($this->session);
	}

	public function getInputValue($input_name, $inputs)
	{
		$forbiddenkeys = $inputs[$input_name]->get_parameter('forbidWords', '');
		$teacheranswer = $this->getSession()->get_value_key($input_name);
		if (array_key_exists($input_name, $this->getInputs()))
		{
			return $this->getInputs($input_name)->validate_student_response($inputs, $this->getOptions(), $teacheranswer, $forbiddenkeys)->__get('contentsmodified');
		} else
		{
			return "";
		}
	}

	static function cmp($a, $b)
	{
		if (strlen($a) == strlen($b))
		{
			return 0;
		}

		return (strlen($a) < strlen($b)) ? -1 : 1;
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @return assStackQuestionStackFactory
	 */
	public function getStackFactory()
	{
		return $this->stack_factory;
	}

	/**
	 * @return int
	 */
	public function getQuestionId()
	{
		return $this->question_id;
	}

	/**
	 * @return string
	 */
	public function getQuestionVariables()
	{
		return $this->question_variables;
	}

	/**
	 * @return string
	 */
	public function getQuestionNote()
	{
		return $this->question_note;
	}

	/**
	 * @param string $name
	 * @return array
	 */
	public function getInputs($name = '')
	{
		if ($name)
		{
			return $this->inputs[$name];
		} else
		{
			return $this->inputs;
		}
	}

	/**
	 * @param string $selector
	 * @return array||stack_potentialresponse_tree
	 */
	public function getPRTs($selector = '')
	{
		if ($selector)
		{
			return $this->prts[$selector];
		} else
		{
			return $this->prts;
		}
	}

	/**
	 * @return stack_options
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @return int
	 */
	public function getSeed()
	{
		return $this->seed;
	}

	/**
	 * @return array
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * @return int
	 */
	public function getSessionLength()
	{
		return $this->session_length;
	}

	/**
	 * @return array
	 */
	public function getQuestionNoteInstantiated()
	{
		return $this->question_note_instantiated;
	}

	/**
	 * @return string
	 */
	public function getQuestionTextInstantiated()
	{
		return $this->question_text_instantiated;
	}

	/**
	 * @return string
	 */
	public function getSpecificFeedbackInstantiated()
	{
		return $this->specific_feedback_instantiated;
	}

	/**
	 * @return string
	 */
	public function getPRTCorrectInstantiated()
	{
		return $this->prt_correct_instantiated;
	}

	/**
	 * @return string
	 */
	public function getPRTPartiallyCorrectInstantiated()
	{
		return $this->prt_partially_correct_instantiated;
	}

	/**
	 * @return string
	 */
	public function getPRTIncorrectInstantiated()
	{
		return $this->prt_incorrect_instantiated;
	}

	/**
	 * @return mixed
	 */
	public function getGeneralFeedback()
	{
		return $this->general_feedback;
	}


	/**
	 * @return array
	 */
	public function getLastResponse()
	{
		return $this->last_response;
	}

	/**
	 * @return bool
	 */
	public function getLastAcceptValid()
	{
		return $this->last_accept_valid;
	}

	/**
	 * @param string $name
	 * @return stack_input_state
	 */
	public function getInputStates($name = '')
	{
		if ($name)
		{
			return $this->input_states[$name];
		} else
		{
			return $this->input_states;
		}
	}

	/**
	 * @param string $selector
	 * @return array
	 */
	public function getPRTResults($selector = '')
	{
		if ($selector)
		{
			return $this->prt_results[$selector];
		} else
		{
			return $this->prt_results;
		}
	}

	/**
	 * @return int
	 */
	public function getActiveId()
	{
		return $this->active_id;
	}

	/**
	 * @return int
	 */
	public function getPass()
	{
		return $this->pass;
	}

	/**
	 * @return float
	 */
	public function getPoints()
	{
		return $this->points;
	}

	/**
	 * @param assStackQuestionStackFactory $stack_factory
	 */
	public function setStackFactory(assStackQuestionStackFactory $stack_factory)
	{
		$this->stack_factory = $stack_factory;
	}

	/**
	 * @param $question_id
	 */
	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	/**
	 * @param $question_variables
	 */
	public function setQuestionVariables($question_variables)
	{
		$this->question_variables = $question_variables;
	}

	/**
	 * @param $question_note
	 */
	public function setQuestionNote($question_note)
	{
		$this->question_note = $question_note;
	}

	/**
	 * @param $inputs
	 */
	public function setInputs($inputs)
	{
		$this->inputs = $inputs;
	}

	/**
	 * @param $prts
	 */
	public function setPRTs($prts)
	{
		$this->prts = $prts;
	}

	/**
	 * @param stack_options $options
	 */
	public function setOptions(stack_options $options)
	{
		$this->options = $options;
	}

	/**
	 * @param $seed
	 */
	public function setSeed($seed)
	{
		$this->seed = $seed;
	}

	/**
	 * @param $session
	 */
	public function setSession($session)
	{
		$this->session = $session;
	}

	/**
	 * @param $session_length
	 */
	public function setSessionLength($session_length)
	{
		$this->session_length = $session_length;
	}

	/**
	 * @param $question_note_instantiated
	 */
	public function setQuestionNoteInstantiated($question_note_instantiated)
	{
		$this->question_note_instantiated = $question_note_instantiated;
	}

	/**
	 * @param $question_text_instantiated
	 */
	public function setQuestionTextInstantiated($question_text_instantiated)
	{
		$this->question_text_instantiated = $question_text_instantiated;
	}

	/**
	 * @param $specific_feedback_instantiated
	 */
	public function setSpecificFeedbackInstantiated($specific_feedback_instantiated)
	{
		$this->specific_feedback_instantiated = $specific_feedback_instantiated;
	}

	/**
	 * @param $prt_correct_instantiated
	 */
	public function setPRTCorrectInstantiated($prt_correct_instantiated)
	{
		$this->prt_correct_instantiated = $prt_correct_instantiated;
	}

	/**
	 * @param $prt_partially_correct_instantiated
	 */
	public function setPRTPartiallyCorrectInstantiated($prt_partially_correct_instantiated)
	{
		$this->prt_partially_correct_instantiated = $prt_partially_correct_instantiated;
	}

	/**
	 * @param $prt_incorrect_instantiated
	 */
	public function setPRTIncorrectInstantiated($prt_incorrect_instantiated)
	{
		$this->prt_incorrect_instantiated = $prt_incorrect_instantiated;
	}

	/**
	 * @param mixed $general_feedback
	 */
	public function setGeneralFeedback($general_feedback)
	{
		$this->general_feedback = $general_feedback;
	}


	/**
	 * @param $last_response
	 */
	public function setLastResponse($last_response)
	{
		$this->last_response = $last_response;
	}

	/**
	 * @param $last_accept_valid
	 */
	public function setLastAcceptValid($last_accept_valid)
	{
		$this->last_accept_valid = $last_accept_valid;
	}

	/**
	 * @param $input_states
	 * @param string $name
	 */
	public function setInputStates($input_states, $name = '')
	{
		if ($name)
		{
			$this->input_states[$name] = $input_states;
		} else
		{
			$this->input_states = $input_states;
		}
	}

	/**
	 * @param $prt_results
	 * @param string $selector
	 */
	public function setPRTResults($prt_results, $selector = '', $selector2 = '')
	{
		if ($selector)
		{
			if ($selector2)
			{
				$this->prt_results[$selector][$selector2] = $prt_results;
			} else
			{
				$this->prt_results[$selector] = $prt_results;
			}
		} else
		{
			$this->prt_results = $prt_results;
		}
	}

	/**
	 * @param int $active_id
	 */
	public function setActiveId($active_id)
	{
		$this->active_id = $active_id;
	}

	/**
	 * @param int $pass
	 */
	public function setPass($pass)
	{
		$this->pass = $pass;
	}

	/**
	 * @param int $points
	 */
	public function setPoints($points)
	{
		$this->points = (float)$points;
	}

	/**
	 * @param float $penalty
	 */
	public function setPenalty($penalty)
	{
		$this->penalty = $penalty;
	}

	/**
	 * @return float
	 */
	public function getPenalty()
	{
		return $this->penalty;
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
	 * Give all the input elements a chance to configure themselves given the
	 * teacher's model answers.
	 */
	protected function adapt_inputs() {
		foreach ($this->inputs as $name => $input) {
			$teacheranswer = $this->session->get_value_key($name);
			$input->adapt_to_model_answer($teacheranswer);
		}
	}

	/**
	 * Helper method used by initialise_question_from_seed.
	 * @param string $text a textual part of the question that is CAS text.
	 * @param stack_cas_session $session the question's CAS session.
	 * @return stack_cas_text the CAS text version of $text.
	 */
	protected function prepare_cas_text($text, $session) {
		$castext = new stack_cas_text($text, $session, $this->seed, 't', false, 1);
		if ($castext->get_errors()) {
			throw new stack_exception('qtype_stack_question : Error part of the question: ' .
				$castext->get_errors());
		}
		return $castext;
	}

}
