<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question EVALUATION of questions
 * This class provides an evaluation for a User response in a specifiSTACK Questionon
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 1.6.1$$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionEvaluation
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * STACK version of the question
	 * @var assStackQuestionStackQuestion
	 */
	private $question;

	/**
	 * The user response to be evaluated
	 * @var array
	 */
	private $user_response;

	/**
	 * Set the parameters needed for evaluation
	 * @param ilassStackQuestionPlugin $plugin
	 * @param assStackQuestionStackQuestion $question
	 * @param array|bool $user_response
	 */
	public function __construct(ilassStackQuestionPlugin $plugin, assStackQuestionStackQuestion $question, $user_response)
	{
		//Set plugin object
		$this->setPlugin($plugin);
		//Set question object to be displayed
		$this->setQuestion($question);
		//Set user response
		//In assStackQuestionEvaluation the User response should be store with the "reduced" format for assStackQuestionUtils::_getUserResponse.
		$this->setUserResponse($user_response);
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestion or assStackQuestionPreview to get the question evaluation.
	 * If any pre check for complete question must be done, should be done here.
	 * @return assStackQuestionStackQuestion the evaluated question.
	 */
	public function evaluateQuestion($forbiddenkeys = '')
	{
		//Step #1: Checks availability of parts needed for evaluating a question
		if ($this->isQuestionEvaluable())
		{
			//Step #2: Evaluates question
			if ($this->doEvaluation($forbiddenkeys))
			{
				$this->log['question_evaluated'] = TRUE;
			}
			if (!is_float($this->getQuestion()->getPoints()))
			{
				$this->getQuestion()->setPoints(0.0);
			}

			//Step 3: Returns evaluation data

			return $this->getQuestion();
		} else
		{
			//Returns the log for showing the error messages.
			return $this->log;
		}
	}


	/**
	 * Analyse each Potential response tree in order to determine if are evaluable.
	 */
	private function isQuestionEvaluable()
	{
		return TRUE;
	}

	/**
	 * This function really evaluates each Potential Response Tree
	 * in case all requiremets are filled in.
	 * @return bool
	 */
	private function doEvaluation($forbiddenkeys = '')
	{
		//This loop checks that each PRT has the whole information needed to be evaluated
		//and evaluates it, filling an entry in the evaluation data array
		foreach (array_keys($this->getQuestion()->getPRTs()) as $potentialresponse_tree_name)
		{
			if ($this->previousCheckingForEvaluation($potentialresponse_tree_name, TRUE, $forbiddenkeys))
			{
				//Evaluate each potential response tree and store it into the evaluation data array.
				try
				{
					$this->evaluatePotentialResponseTree($potentialresponse_tree_name, TRUE);
				} catch (stack_exception $e)
				{
					ilUtil::sendFailure($e, TRUE);
				}
			} else
			{
				//A blank entry in the question's PRT Result has been created for this PRT
				$error = TRUE;
			}
		}
		//Check for penalty
		if ($this->error)
		{
			$this->addPenalty();
		}

		//If everything was OK
		return TRUE;
	}

	/**
	 * @param $potentialresponse_tree_name
	 * @param $accept_valid
	 * @return bool
	 */
	private function previousCheckingForEvaluation($potentialresponse_tree_name, $accept_valid, $forbiddenkeys = '')
	{
		//Step #1: Validate cache, in order to improve the permormance of the evaluation process.
		//In assStackQuestionEvaluation the User response should be store with the "reduced" format for assStackQuestionUtils::_getUserResponse.
		$this->getQuestion()->validateCache($this->getUserResponse(), $accept_valid);

		//Step #2: Check if it's already evaluated, if it does, return the previous evaluation info.
		if (array_key_exists($potentialresponse_tree_name, $this->getQuestion()->getPRTResults()))
		{
			return FALSE;
		}

		//Step #3: Check if potential response tree has enought input to be evaluated.
		$potentialresponse_tree = $this->getQuestion()->getPRTs($potentialresponse_tree_name);
		if (!$this->hasNecessaryPotentialResponseTreeInputs($potentialresponse_tree, $accept_valid, $forbiddenkeys))
		{
			return FALSE;
		}

		//Step #4: Tests have been passed, return TRUE
		return TRUE;
	}


	/**
	 * Do we have all the necessary inputs to execute one of the potential response trees?
	 * @param stack_potentialresponse_tree $potentialresponse_tree the tree in question.
	 * @param bool $acceptvalid if this is true, then we will grade things even if the corresponding inputs are only VALID, and not SCORE.
	 * @return bool can this PRT be evaluated for the current response.
	 */
	private function hasNecessaryPotentialResponseTreeInputs(stack_potentialresponse_tree $potentialresponse_tree, $accept_valid, $forbiddenkeys = '')
	{
		//From all the required variables, are the variables in an appropiate status?
		foreach ($potentialresponse_tree->get_required_variables(array_keys($this->getQuestion()->getInputs())) as $input_name)
		{

			//In assStackQuestionEvaluation the User response should be store with the "reduced" format for assStackQuestionUtils::_getUserResponse.
			//Notice this is the unique place where getInputState is called from this class, in the following occasions, use getInputStates in order to improve the performance

			//The following line solves a bug of allow users to insert forbidden variables to the question and get right.
			$forbiddenkeys = $this->getQuestion()->getInputs($input_name)->get_parameter('forbidWords', '');
			$input_state = $this->getQuestion()->getInputState($input_name, $this->getUserResponse(), $forbiddenkeys);
			if (stack_input::SCORE == $input_state->status || ($accept_valid && stack_input::VALID == $input_state->status))
			{
				//This input is in an OK state.
			} else
			{
				//This input is in a not valid status, so the PRT cannot be evaluated.
				$this->manageInvalidPRT($potentialresponse_tree, $input_name, $input_state, $accept_valid);

				return FALSE;
			}
		}

		//If everything correct, return TRUE to evaluate the PRT.
		return TRUE;
	}

	/**
	 * Creates a blank entry in PRT results for uncomplete PRT
	 * @param stack_potentialresponse_tree $potentialresponse_tree
	 * @param $input_name
	 * @param $accept_valid
	 * @return bool
	 */
	private function manageInvalidPRT(stack_potentialresponse_tree $potentialresponse_tree, $input_name, $input_state, $accept_valid)
	{
		$errors = $this->getQuestion()->getInputStates($input_name)->__get('errors');

		if (!$errors)
		{
			$errors = stack_string('ATSysEquiv_SA_missing_variables');
		}
		$answer_note = $this->getQuestion()->getInputStates($input_name)->__get('note');

		//Preparation of a blank PRT state
		$potentialresponse_tree_state = $this->getQuestion()->createBlankPRTState($potentialresponse_tree->get_value(), $errors, $answer_note);

		//Set evaluation data.
		$evaluation_data = array();
		$evaluation_data['state'] = $potentialresponse_tree_state;
		$evaluation_data['inputs_evaluated'] = $this->getPotentialResponseTreeInputs($potentialresponse_tree->get_name(), $accept_valid, TRUE);

		$this->getQuestion()->setPRTResults($evaluation_data, $potentialresponse_tree->get_name());

		return TRUE;
	}

	/**
	 * Evaluate a PRT for a particular response.
	 * @param string $potentialresponse_tree_name the index of the PRT to evaluate.
	 * @param bool $accept_valid if this is true, then we will grade things even
	 *      if the corresponding inputs are only VALID, and not SCORE.
	 * @return stack_potentialresponse_tree_state the result from $prt->evaluate_response(),
	 *      or a fake state object if the tree cannot be executed.
	 */
	private function evaluatePotentialResponseTree($potentialresponse_tree_name, $accept_valid)
	{
		//Get all the pairs input_name => user_response needed to evaluate the current PRT
		$prt_inputs = $this->getPotentialResponseTreeInputs($potentialresponse_tree_name, $accept_valid);

		//Get Potential response tree object
		$potentialresponse_tree = $this->getQuestion()->getPRTs($potentialresponse_tree_name);

		//Evaluates the current PRT
		$potentialresponse_tree_state = $potentialresponse_tree->evaluate_response($this->getQuestion()->getSession(), $this->getQuestion()->getOptions(), $prt_inputs, $this->getQuestion()->getSeed());

		//Check for penalty
		if ((float)$potentialresponse_tree_state->__get('score') != 1.0)
		{
			if ((float)$potentialresponse_tree_state->__get('penalty') == 0.0)
			{
				$potentialresponse_tree_state->_penalty = $this->getQuestion()->getPenalty();
			}
		}

		//Set evaluation data.
		$evaluation_data = array();
		$evaluation_data['state'] = $potentialresponse_tree_state;
		$evaluation_data['inputs_evaluated'] = $prt_inputs;

		$this->getQuestion()->setPRTResults($evaluation_data, $potentialresponse_tree->get_name());

		return TRUE;
	}

	private function addPenalty()
	{
		foreach ($this->getQuestion()->getPRTResults() as $prt_name => $data)
		{
			if ($data['state']->_valid)
			{
				$data['state']->_penalty = $this->getQuestion()->getPenalty();
				$this->getQuestion()->setPRTResults($data['state'], $prt_name, 'state');
			}
		}
	}


	/**
	 * Determines which input are evaluated by the current PRT
	 * @param string $potentialresponse_tree_name the name of the PRT.
	 * @param bool $accept_valid if this is true, then we will grade things even if the corresponding inputs are only VALID, and not SCORE.
	 * @return array the input(s) required by that PRT as array(input_name => user_response)
	 */
	private function getPotentialResponseTreeInputs($potentialresponse_tree_name, $accept_valid, $show_response = FALSE)
	{
		//PRT to be evaluated
		$potentialresponse_tree = $this->getQuestion()->getPRTs($potentialresponse_tree_name);

		//Array with all imputs evaluated by this PRT
		$prt_inputs = array();

		//Checking of inputs
		foreach ($potentialresponse_tree->get_required_variables(array_keys($this->getQuestion()->getInputs())) as $input_name)
		{
			//In assStackQuestionEvaluation the User response should be store with the "reduced" format for assStackQuestionUtils::_getUserResponse.
			$input_state = $this->getQuestion()->getInputStates($input_name);

			if (stack_input::SCORE == $input_state->status || ($accept_valid AND stack_input::VALID == $input_state->status))
			{
				$prt_inputs[$input_name] = $input_state->contentsmodified;
			}
			if ($show_response)
			{
				$prt_inputs[$input_name] = $this->getUserResponse($input_name);
			}
		}

		//Returns $prt_inputs
		return $prt_inputs;
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param \ilassStackQuestionPlugin $plugin
	 */
	private function setPlugin($plugin)
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
	 * @param \assStackQuestionStackQuestion $question
	 */
	private function setQuestion($question)
	{
		$this->question = $question;
	}

	/**
	 * @return \assStackQuestionStackQuestion
	 */
	public function getQuestion()
	{
		return $this->question;
	}

	/**
	 * @param array $user_response
	 */
	private function setUserResponse($user_response)
	{
		$this->user_response = $user_response;
	}

	/**
	 * @return array
	 */
	public function getUserResponse($input_name = '')
	{
		if ($input_name)
		{
			return $this->user_response[$input_name];
		} else
		{
			return $this->user_response;
		}
	}
}