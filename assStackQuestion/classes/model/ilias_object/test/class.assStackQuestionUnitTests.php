<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question Unit tests object class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 1.8$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionUnitTests
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var assStackQuestion current question
	 */
	private $question;

	/**
	 * @var string testcase name if only one test run.
	 */
	private $testcase;

	/**
	 * @var array Input values for the test
	 */
	private $test_inputs;

	/**
	 * @var array Expected results after evaluation
	 */
	private $test_expected_results;


	/**
	 * @param ilassStackQuestionPlugin $plugin
	 * @param assStackQuestion $question
	 */
	function __construct(ilassStackQuestionPlugin $plugin, assStackQuestion $question)
	{
		//Set general info
		$this->setPlugin($plugin);

		//allow all words
		foreach ($question->getInputs() as $input)
		{
			$input->setForbidWords("");
		}
		$this->setQuestion($question);

	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * @return mixed with the unit test results for this question
	 */
	public function runTest($testcase = '')
	{
		//If $testcase given, then executes the specific testcase, if not, execute all test in current question.
		if ($testcase)
		{
			$test_results = array();
			foreach ($this->getQuestion()->getTests() as $unit_test)
			{
				if ($unit_test->getTestCase() == $testcase)
				{
					$test_results[$unit_test->getTestCase()] = $this->evaluateTestcase($testcase);
				} else
				{
					$test_results[$unit_test->getTestCase()] = "";
				}
			}

			return $test_results;
		} else
		{
			$test_results = array();
			foreach ($this->getQuestion()->getTests() as $unit_test)
			{
				$test_results[$unit_test->getTestCase()] = $this->evaluateTestcase($unit_test->getTestCase());
			}

			return $test_results;
		}
	}

	/**
	 * @param string $testcase
	 * @return mixed with the unit test results for an specific $testcase
	 */
	private function evaluateTestcase($testcase)
	{
		//Get desired unit test
		$unit_test = $this->getQuestion()->getTests($testcase);
		$testcase_user_response = $unit_test->getInputsForUnitTest();
		$user_response = $this->computeResponseForUnitTest($testcase_user_response);

		//Prepare question evaluation
		$this->getPlugin()->includeClass('model/question_evaluation/class.assStackQuestionEvaluation.php');
		$evaluation_object = new assStackQuestionEvaluation($this->getPlugin(), $this->getQuestion()->getStackQuestion(), $user_response);
		$evaluation_data = $evaluation_object->evaluateQuestion(array())->getPRTResults();

		//Filter required data
		return $this->getUnitTestData($evaluation_data, $testcase, $user_response);
	}

	/**
	 * This function compares the evaluation data with the expected,
	 * and format it in a properly way.
	 * @param mixed $evaluation_data from assStackQuestionEvaluation
	 * @param string $testcase
	 * @param mixed $user_response for using as inputs
	 * @return mixed the formated data
	 */
	private function getUnitTestData($evaluation_data, $testcase, $user_response)
	{
		//Initialization of attributes
		$unit_test_results = $this->getExpectedResults($testcase);
		$unit_test_results['test_passed'] = TRUE;
		//Add inputs part
		$unit_test_results['inputs'] = $user_response;

		//Get comparison with results
		foreach ($evaluation_data as $prt_name => $data)
		{
			$prt_state = $data['state'];
			//Fill array with received results
			//SCORE TEST
			$unit_test_results['prts'][$prt_name]['received_score'] = $prt_state->__get('score');
			if ((float)$unit_test_results['prts'][$prt_name]['received_score'] == (float)$unit_test_results['prts'][$prt_name]['expected_score'])
			{
				$unit_test_results['prts'][$prt_name]['score_test'] = TRUE;
			} else
			{
				$unit_test_results['test_passed'] = FALSE;
				$unit_test_results['prts'][$prt_name]['score_test'] = FALSE;
			}

			//PENALTY TEST IS NOT USED IN ILIAS
			$unit_test_results['prts'][$prt_name]['received_penalty'] = $prt_state->__get('penalty');
			/*Comment this part of the code to solve bug 0016211*/
			//if ((float)$unit_test_results['prts'][$prt_name]['received_penalty'] == (float)$unit_test_results['prts'][$prt_name]['expected_penalty']) {
			$unit_test_results['prts'][$prt_name]['penalty_test'] = TRUE;
			/*} else {
				if (is_null($unit_test_results['prts'][$prt_name]['received_penalty']) AND is_null($unit_test_results['prts'][$prt_name]['expected_penalty'])) {
					$unit_test_results['prts'][$prt_name]['penalty_test'] = TRUE;
				} else {
					$unit_test_results['test_passed'] = FALSE;
					$unit_test_results['prts'][$prt_name]['penalty_test'] = FALSE;
				}
			}*/

			//ANSWERNOTE TEST
			if (is_array($prt_state->__get('answernotes')))
			{
				$index = sizeof($prt_state->__get('answernotes')) - 1;
				$unit_test_results['prts'][$prt_name]['received_answernote'] = $prt_state->__get('answernotes')[$index];

				if ($unit_test_results['prts'][$prt_name]['received_answernote'] == $unit_test_results['prts'][$prt_name]['expected_answernote'])
				{
					$unit_test_results['prts'][$prt_name]['answernote_test'] = TRUE;
				} else
				{
					$unit_test_results['test_passed'] = FALSE;
					$unit_test_results['prts'][$prt_name]['answernote_test'] = FALSE;
				}
			} elseif ($prt_state->__get('answernotes') == NULL)
			{
				$unit_test_results['prts'][$prt_name]['received_answernote'] = NULL;
				if (strcmp($unit_test_results['prts'][$prt_name]['expected_answernote'], "NULL") == 0)
				{
					$unit_test_results['prts'][$prt_name]['answernote_test'] = TRUE;
				} else
				{
					$unit_test_results['test_passed'] = FALSE;
					$unit_test_results['prts'][$prt_name]['answernote_test'] = FALSE;
				}
			}

			//ADD FEEDBACK AND ERRORS INFO
			$feedback_string = "";
			$unit_test_results['prts'][$prt_name]['cas_errors'] = $prt_state->__get('errors');
			if (is_array($prt_state->__get('feedback')))
			{
				foreach ($prt_state->__get('feedback') as $feedback)
				{
					$feedback_string .= $feedback->feedback;
				}
				$unit_test_results['prts'][$prt_name]['cas_feedback'] = $feedback_string;
			} else
			{
				$unit_test_results['prts'][$prt_name]['cas_feedback'] = '';
			}
		}

		return $unit_test_results;
	}

	/**
	 * Get the expected results for a certain testcase
	 * in order to use as a base array for complete unit test data
	 * @param string $testcase
	 * @return mixed
	 */
	private function getExpectedResults($testcase)
	{
		//Get required unit test
		$unit_test = $this->getQuestion()->getTests($testcase);

		//Fill data
		foreach ($unit_test->getTestExpected() as $test)
		{
			$expected_results['prts'][$test->getTestPRTName()]['expected_answernote'] = $test->getExpectedAnswerNote();
			$expected_results['prts'][$test->getTestPRTName()]['expected_score'] = $test->getExpectedScore();
			$expected_results['prts'][$test->getTestPRTName()]['expected_penalty'] = $test->getExpectedPenalty();
		}

		return $expected_results;
	}

	/**
	 * This question computes the user response in order to let use expression
	 * that using normal procedure will not be accesible
	 * @param mixed $inputs
	 * @return mixed evaluated array
	 */
	private function computeResponseForUnitTest($inputs)
	{
		// If the question has simp:false, then the local options should reflect this.
		// In this case, test constructors (question authors) will need to explicitly simplify their test case constructions.
		$localoptions = clone $this->getQuestion()->getStackQuestion()->getOptions();

		// Start with the question variables (note that order matters here).
		$cascontext = new stack_cas_session(null, $localoptions, $this->getQuestion()->getStackQuestion()->getSeed());
		$this->getQuestion()->getStackQuestion()->addQuestionVarsToSession($cascontext);

		// Turn off simplification - we *always* need test cases to be unsimplified, even if the question option is true.
		$vars = array();
		$cs = new stack_cas_casstring('false');
		$cs->set_key('simp');
		$vars[] = $cs;
		// Now add the expressions we want evaluated.
		foreach ($inputs as $name => $value)
		{
			if ('' !== $value)
			{
				$cs = new stack_cas_casstring($value);
				if ($cs->get_valid('t'))
				{
					$cs->set_key('testresponse_' . $name);
					$vars[] = $cs;
				}
			}
		}

		$cascontext->add_vars($vars);
		$cascontext->instantiate();

		$response = array();
		foreach ($inputs as $name => $notused)
		{
			$computedinput = $cascontext->get_value_key('testresponse_' . $name);
			// In the case we start with an invalid input, and hence don't send it to the CAS
			// We want the response to constitute the raw invalid input.
			// This permits invalid expressions in the inputs, and to compute with valid expressions.
			if ('' == $computedinput)
			{
				$computedinput = $inputs[$name];
			}
			if (array_key_exists($name, $this->getQuestion()->getStackQuestion()->getInputs()))
			{
				$response = array_merge($response, $this->getQuestion()->getStackQuestion()->getInputs($name)->maxima_to_response_array($computedinput));
			}
		}

		foreach ($response as $key => $value)
		{
			$response[$key] = str_replace(' ', '', $value);
		}

		return $response;
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
	 * @param \assStackQuestion $question
	 */
	public function setQuestion($question)
	{
		$this->question = $question;
	}

	/**
	 * @return \assStackQuestion
	 */
	public function getQuestion()
	{
		return $this->question;
	}


}