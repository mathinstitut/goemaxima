<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * This class provides different stack objects in order to be used by the question plugin
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: $
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionStackFactory
{
	//FACTORY METHOD
	public function get($select, $parameters = "")
	{
		switch ($select)
		{
			case "cas_casstring_from_input":
				return $this->getStackCasCasstringFromInput($parameters);
			case "cas_casstring_from_array":
				return $this->getStackCasCasstringFromArray($parameters);
			case "cas_casstring_from_parameters":
				return $this->getStackCasCasstringFromParameters($parameters);
			case "cas_keyval":
				//returns: stack_cas_keyval
				return $this->getStackCasKeyval($parameters);
			case "cas_text":
				return $this->getStackCasText($parameters);
			case "default_options":
				return $this->getStackDefaultOptions();
			case "input_object":
				//returns: stack_*type*_input; this function calls to stack input factory.
				return $this->getStackInput($parameters);
			case "input_state":
				//returns: stack_input_state.
				return $this->getStackInputState($parameters);
			case "options":
				return $this->getStackOptions($parameters);
			case "potentialresponse_node":
				return $this->getStackPotentialResponseNode($parameters);
			case "potentialresponse_tree":
				return $this->getStackPotentialResponseTree($parameters);
			case "potentialresponse_tree_state":
				return $this->getStackPotentialResponseTreeState($parameters);
			case "potentialresponse_tree_state_blank":
				return $this->getStackPotentialResponseTreeStateBlank($parameters);
			case "unit_test":
				return $this->getStackUnitTest($parameters);
			default:
				return false;
		}
	}

	//stack_cas_session
	public function getStackCasSessionDefault()
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/cassession.class.php';

		$session = array();
		//$options is an stack_options object. Default options given INCOMPLETE?
		$options = $this->get("default_options");
		//$seed will be set as time() INCOMPLETE
		$seed = null;

		return new stack_cas_session($session, $options, $seed);
	}

	public function getStackCasCasstringFromInput(assStackQuestionInput $input)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/casstring.class.php';
		$cas_casstring = new stack_cas_casstring($input->getTeacherAnswer());
		$cas_casstring->set_key($input->getInputName());
		if ($cas_casstring->get_valid())
		{
			return $cas_casstring;
		} else
		{
			return $cas_casstring;
		}
	}

	/**
	 *
	 * @param array $parameters
	 * Composition:
	 * ['string'] = String with raw text
	 * ['key'] = string Key for casstring.
	 * ['security'] = 's' for students 't' for teachers.
	 * ['syntax'] = boolean, to apply strict syntax.
	 * ['stars'] = int, To insert stars if needed.
	 */
	public function getStackCasCasstringFromParameters($parameters)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/casstring.class.php';
		$cas_casstring = new stack_cas_casstring($parameters["string"]);
		$cas_casstring->set_key($parameters["key"]);
		if ($cas_casstring->get_valid($parameters["security"], $parameters["syntax"], (int)$parameters["stars"]))
		{
			return $cas_casstring;
		} else
		{
			return $cas_casstring;
		}
	}

	public function getStackCasCasstringFromArray($parameters)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/casstring.class.php';
		$cas_casstring = new stack_cas_casstring($parameters["string"]);
		#Unknown function problem solution
		//$cas_casstring->set_key($parameters["name"]);
		//$cas_casstring->validate('t');

		if ($cas_casstring->get_valid('t'))
		{
			return $cas_casstring;
		} else
		{
			return $cas_casstring;
		}
	}

	/**
	 *
	 * @param array $parameters
	 * Composition:
	 * ['raw'] = String with f.e. question_variables.
	 * ['options'] = stack_options Object.
	 * ['seed'] = Seed of the question, Null if there is no seed.
	 * ['security'] = 's' for students 't' for teachers.
	 * ['syntax'] = boolean, to apply strict syntax.
	 * ['stars'] = int, To insert stars if needed.
	 */
	public function getStackCasKeyVal(array $parameters)
	{
		global $DIC;

		$lng = $DIC->language();
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/cassession.class.php';
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/keyval.class.php';
		if (!isset($parameters['raw']) OR strlen($parameters['raw']) <= 0)
		{
			$parameters['raw'] = " ";
		}
		if (!isset($parameters['options']) OR !is_a($parameters['options'], 'stack_options'))
		{
			$parameters['options'] = $this->getStackDefaultOptions();
		}
		if (!isset($parameters['seed']))
		{
			$parameters['seed'] = NULL;
		}
		if (!isset($parameters['security']))
		{
			//Set student security by default
			$parameters['security'] = 's';
		}
		if (!isset($parameters['syntax']))
		{
			//Use strict syntax by default
			$parameters['syntax'] = TRUE;
		}
		if (!isset($parameters['stars']))
		{
			//Do not insert stars by default
			//Changed to integer for STACK 3.3
			$parameters['stars'] = 0;
		}

		return new stack_cas_keyval($parameters['raw'], $parameters['options'], $parameters['seed'], $parameters['security'], $parameters['syntax'], (int)$parameters['stars']);
	}

	/**
	 *
	 * @param array $parameters
	 * Composition:
	 * ['raw'] = String with f.e. question_variables.
	 * ['session'] = stack_options Object.
	 * ['seed'] = Seed of the question, Null if there is no seed.
	 * ['security'] = 's' for students 't' for teachers.
	 * ['syntax'] = boolean, to apply strict syntax.
	 * ['stars'] = int, To insert stars if needed.
	 */
	public function getStackCasText($parameters)
	{
		$cas_text = array();
		global $DIC;

		$lng = $DIC->language();
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/cassession.class.php';
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/cas/castext.class.php';
		if (!isset($parameters['raw']) OR strlen($parameters['raw']) <= 0)
		{
			$parameters['raw'] = " ";
		}
		if (!isset($parameters['session']) OR !is_a($parameters['session'], 'stack_cas_session'))
		{
			$parameters['session'] = $this->getStackCasSessionDefault();
		}
		if (!isset($parameters['seed']))
		{
			$parameters['seed'] = NULL;
		}
		if (!isset($parameters['security']))
		{
			//Set student security by default
			$parameters['security'] = 's';
		}
		if (!isset($parameters['syntax']))
		{
			//Use strict syntax by default
			$parameters['syntax'] = TRUE;
		}
		if (!isset($parameters['stars']))
		{
			//Do not insert stars by default
			//Changed to integer for STACK 3.3
			$parameters['stars'] = 0;
		}
		$castext = new stack_cas_text((string)$parameters['raw'], $parameters['session'], $parameters['seed'], $parameters['security'], $parameters['syntax'], (int)$parameters['stars']);
		$cas_text["valid"] = $castext->get_valid();
		$cas_text["text"] = $castext->get_display_castext();
		$cas_text["errors"] = $castext->get_errors();
		$cas_text["debug"] = $castext->get_debuginfo();

		return $cas_text;
	}


	public function getStackDefaultOptions()
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/options.class.php';
		//DEFAULT OPTIONS ARRAY GIVEN BY STACK
		//stack options creation
		$settings = array();

		return new stack_options($settings);
	}

	/**
	 *
	 * @param array $parameters || assStackQuestionInput $parameters
	 * Composition:
	 * ['type'] = string input type.
	 * ['name'] = string identifier: xcas_questionid_inputname .
	 * ['teacheranswer'] = string Teacher answer, Null if not needed.
	 * ['parameters'] = array Extra parameters, is an array.
	 */
	public function getStackInput($parameters)
	{
		global $DIC;

		$lng = $DIC->language();
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/factory.class.php';
		//If $parameters is an assStackQuestionInput object
		if (is_a($parameters, 'assStackQuestionInput'))
		{
			return stack_input_factory::make($parameters->getInputType(), $parameters->getInputName(), $parameters->getTeacherAnswer(), $parameters);
		}

		$all_parameters_used = stack_input_factory::get_parameters_used();

		//string is algebraic
		if ($parameters['type'] == "string")
		{
			$parameters_used_by_input_type = $all_parameters_used["algebraic"];
		} else
		{
			$parameters_used_by_input_type = $all_parameters_used[$parameters['type']];
		}

		//Create a new array with all parameters used by input type
		$used_parameters = array();
		if (is_array($parameters_used_by_input_type))
		{
			foreach ($parameters_used_by_input_type as $key => $value)
			{
				if (key_exists($value, $parameters['parameters']))
				{
					$used_parameters[$value] = $parameters['parameters'][$value];
				}
			}
		}

		//If $parameters is an Array
		if (!isset($parameters['type']) OR !isset($parameters['name']))
		{
			throw new assStackQuestionException($lng->txt('ex_input_creation_is_not_possible'));
		} else
		{
			if (!isset($parameters['teacheranswer']))
			{
				$parameters['teacheranswer'] = NULL;
			}
			if (!isset($parameters['parameters']))
			{
				$parameters['parameters'] = NULL;
			}
		}

		//We need to choose which parameters should we send to the render, because if a unused parameter is sent, we get an Exception.
		return stack_input_factory::make($parameters['type'], $parameters['name'], $parameters['teacheranswer'], $parameters['options'], $used_parameters);
	}

	/**
	 *
	 * @param array $parameters
	 * Composition:
	 * ['status'] = string actual status.
	 * ['contents'] = array, contents .
	 * ['contentsmodified']
	 * ['contentsdisplayed']
	 * ['errors']
	 * ['note']
	 * @return \stack_input_state
	 * @throws Exception
	 */
	public function getStackInputState(array $parameters)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/inputbase.class.php';
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/inputstate.class.php';
		global $DIC;

		$lng = $DIC->language();
		//State value translation to stack constans.
		if (!isset($parameters['status']))
		{
			throw new assStackQuestionException($lng->txt('ex_unknown_input_state'));
		} else
		{
			switch ($parameters['status'])
			{
				case 'blank':
					$status = stack_input::BLANK;
					break;
				case 'valid':
					$status = stack_input::VALID;
					break;
				case 'invalid':
					$status = stack_input::INVALID;
					break;
				case 'score':
					$status = stack_input::SCORE;
					break;
				default:
					$status = stack_input::BLANK;
					break;
			}
		}
		//Check other data
		if (!isset($parameters['contents']) OR !is_array($parameters['contents']))
		{
			$parameters['contents'] = array();
		}
		if (!isset($parameters['contentsmodified']))
		{
			$parameters['contentsmodified'] = '';
		}
		if (!isset($parameters['contentsdisplayed']))
		{
			$parameters['contentsdisplayed'] = '';
		}
		if (!isset($parameters['errors']))
		{
			$parameters['errors'] = '';
		}
		if (!isset($parameters['note']))
		{
			$parameters['note'] = '';
		}

		//Returns object.
		return new stack_input_state($status, $parameters['contents'], $parameters['contentsmodified'], $parameters['contentsdisplayed'], $parameters['errors'], $parameters['note']);
	}

	public function getStackOptions($parameters)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/options.class.php';

		//stack options creation
		return new stack_options($parameters);
	}


	public function getStackPotentialResponseNode(assStackQuestionPRTNode $ilias_node)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/potentialresponsenode.class.php';
		//Prepare data to create node
		$student_answer_parameters = array("name" => $ilias_node->getNodeName(), "string" => $ilias_node->getStudentAnswer());
		$stack_student_answer = $this->get("cas_casstring_from_array", $student_answer_parameters);
		$teacher_answer_parameters = array("name" => $ilias_node->getNodeName(), "string" => $ilias_node->getTeacherAnswer());
		$stack_teacher_answer = $this->get("cas_casstring_from_array", $teacher_answer_parameters);
		$node = new stack_potentialresponse_node($stack_student_answer, $stack_teacher_answer, $ilias_node->getAnswerTest(), $ilias_node->getTestOptions(), (boolean)$ilias_node->getQuiet(), "", $ilias_node->getNodeId());

		$node->add_branch(0, $ilias_node->getFalseScoreMode(), $ilias_node->getFalseScore(), $ilias_node->getFalsePenalty(), $ilias_node->getFalseNextNode(), $ilias_node->getFalseFeedback(), $ilias_node->getFalseFeedbackFormat(), $ilias_node->getFalseAnswerNote());
		$node->add_branch(1, $ilias_node->getTrueScoreMode(), $ilias_node->getTrueScore(), $ilias_node->getTruePenalty(), $ilias_node->getTrueNextNode(), $ilias_node->getTrueFeedback(), $ilias_node->getTrueFeedbackFormat(), $ilias_node->getTrueAnswerNote());

		return $node;
	}

	public function getStackPotentialResponseTree(assStackQuestionPRT $ilias_PRT)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/potentialresponsetree.class.php';
		//Feedback variables conversion
		$feedback_variables_parameters = array('raw' => $ilias_PRT->getPRTFeedbackVariables(), 'options' => $this->get('default_options'), 'seed' => 1, 'security' => 't');
		$stack_feedback_variables = $this->get("cas_keyval", $feedback_variables_parameters);
		$stack_feedback_variables->instantiate();

		//Nodes conversion
		$stack_nodes = array();
		foreach ($ilias_PRT->getPRTNodes() as $ilias_node)
		{
			if (is_a($ilias_node, "assStackQuestionPRTNode"))
			{
				$stack_nodes[$ilias_node->getNodeName()] = $this->get("potentialresponse_node", $ilias_node);
			}
		}

		try
		{
			return new stack_potentialresponse_tree($ilias_PRT->getPRTName(), "", (boolean)$ilias_PRT->getAutoSimplify(), $ilias_PRT->getPRTValue(), $stack_feedback_variables->get_session(), $stack_nodes, $ilias_PRT->getFirstNodeName());
		} catch (stack_exception $e)
		{
			ilUtil::sendFailure($e->getMessage(), TRUE);
		}
	}

	public function getStackPotentialResponseTreeState(array $parameters)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/potentialresponsetreestate.class.php';

		return new stack_potentialresponse_tree_state($parameters['weight'], $parameters['valid'], $parameters['score'], $parameters['penalty']);
	}

	public function getStackPotentialResponseTreeStateBlank($prts_data)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/potentialresponsetreestate.class.php';

		return new stack_potentialresponse_tree_state($prts_data['weight'], $prts_data['valid'], $prts_data['score'], $prts_data['penalty'], $prts_data['errors'], $prts_data['answernote'], $prts_data['feedback']);
	}

	private function getStackUnitTest($ilias_tests)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/questiontest.php';

		foreach ($ilias_tests as $ilias_test)
		{
			//Create test and inputs
			$stack_question_test = new stack_question_test($ilias_test->getInputsForSTACKtest());
			//Add expected results
			foreach ($ilias_test->getTestExpected() as $expected)
			{
				$stack_question_test->add_expected_result($expected->getTestPRTName(), new stack_potentialresponse_tree_state(1, true, $expected->getExpectedScore(), $expected->getExpectedPenalty(), '', array($expected->getExpectedAnswerNote())));
			}
		}

		return $stack_question_test;
	}

}
