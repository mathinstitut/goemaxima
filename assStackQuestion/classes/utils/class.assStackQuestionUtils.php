<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * Class with STATIC METHODS used in the whole STACK Question
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionUtils
{

	/**
	 * Prevent comparison operators being interpreted as HTML tags
	 * This would cause errors if CASText is processed with strip_tags.
	 *
	 * Not used anymore because RTE fields convert < and >to &lt; and &gt;
	 * The question variables field is now read without strip_tags
	 *
	 * @param $text
	 * @return mixed
	 */
	public static function _debugText($text)
	{
		$text1 = str_replace("<", "< ", $text);
		$text2 = str_replace(">", " >", $text1);

		return $text2;
	}

	/**
	 * Replace key brackets by their ascii code, to avoid
	 * Bug: http://www.ilias.de/mantis/view.php?id=12878
	 * @param string $text the original text.
	 * @return string the text with corrections done.
	 */
	public static function _solveKeyBracketsBug($text)
	{
		$text1 = str_replace("{", "&#123;", $text);

		return str_replace("}", "&#125;", $text1);
	}

	public static function _removeLaTeX($text)
	{
		$text1 = str_replace('\[', '', $text);

		return str_replace('\]', '', $text1);
	}

	public static function _addLatex($text)
	{
		$text1 = '\[' . $text;

		return $text1 . '\]';
	}

	public static function _replacePlaceholders($text, $replacement = '')
	{
		return preg_replace('/\[\[feedback:(.*?)\]\]/', $replacement, $text);
	}

	/**
	 * Transforms an answer from STACK evaluation to ILIAS Display format.
	 * @param array $student_answer
	 * @return array the student_answer with correct display format.
	 */
	public static function _fromEvaluationToDisplayFormat($student_answer)
	{
		$display_format = array();
		foreach ($student_answer as $input_name => $value)
		{
			$display_format['xqcas_input_' . $input_name . '_value'] = $value;
		}

		return $display_format;
	}

	/**
	 * Redo changes done by self::_debugText for a few tags
	 * (Deprecated, not used anymore)
	 *
	 * @param $text
	 * @return mixed
	 * @deprecated
	 */
	public static function _solveHTMLProblems($text)
	{
		$text1 = str_replace('< p >', '<p>', $text);
		$text2 = str_replace('< /p >', '</p>', $text1);
		$text3 = str_replace('< br >', '<br>', $text2);
		$text4 = str_replace('< /br >', '</br>', $text3);
		$text5 = str_replace('< br / >', '<br/>', $text4);

		return $text5;
	}


	/**
	 * @param $array_of_seeds /array of deployed seeds
	 * @param $seed /string created for this pass and active id
	 * @return int chosen seed
	 */
	public static function _chooseSeedForTestPass($array_of_seeds, $seed)
	{
		//Prepare variables
		$keys = array_keys($array_of_seeds);
		$most_appearances_key = 0;
		$most_appearances_value = 0;

		//Look for most appearances of a key in the seed given
		foreach ($keys as $value => $key)
		{
			$count = substr_count($seed, $value);
			if ($count > $most_appearances_value)
			{
				$most_appearances_key = $key;
				$most_appearances_value = $count;
			}
		}

		//Returns seed which appears more times in the seed, otherwise return last seed.
		if ($most_appearances_key > 0)
		{
			return $array_of_seeds[$most_appearances_key]->getSeed();
		} else
		{
			return end($array_of_seeds)->getSeed();
		}
	}

	/**
	 * @param array $user_response
	 * @param $question_id
	 * @param array $inputs
	 * @param $format
	 */
	public static function _getUserResponse($question_id, array $inputs, array $previous_response = array())
	{
		$current_response = array();
		$user_response_from_db = array();

		if(sizeof($previous_response)){
			foreach ($previous_response["prt"] as $prt_name => $prt_info)
			{
				if(sizeof($prt_info["response"])){
					foreach ($prt_info["response"] as $input_name => $input_info)
					{
						$user_response_from_db[$input_name] = $input_info["value"];
					}
				}
			}
		}

		$user_response = array();
		foreach ($inputs as $input_name => $input)
		{
			//Check if its an ILIAS object, or a STACK object
			if (is_a($input, "assStackQuestionInput"))
			{
				//We have an ILIAS object input

			} elseif (is_subclass_of($input, "stack_input"))
			{
				$user_response[$input_name] = $input->maxima_to_response_array($user_response_from_db[$input_name]);
			} else
			{
				//We have something wrong
				ilUtil::sendFailure("Error in manageUserResponse, inputs provided are neither ILIAS or STACK inputs", TRUE);
			}
		}

		return $user_response;
	}

	 /**
	 * @param $user_response
	 * @param $question_id
	 * @param $inputs
	 * @param $change
	 * @return array|bool
	 * @throws stack_exception
	 */
	public static function _changeUserResponseStyle($user_response, $question_id, $inputs, $change, $mode = '')
	{
		//Initialisation of parameters
		$new_user_response_array = array();
		switch ($change)
		{
			case 'full_to_reduced':
				//From full to reduced
				foreach ($inputs as $input_name => $input)
				{
					//If input is not matrix

					if ($mode == 'p')
					{
						if (is_a($input, 'stack_checkbox_input'))
						{
						} elseif (!is_a($input, 'stack_matrix_input'))
						{
							if (isset($user_response['xqcas_' . $question_id . '_' . $input_name]))
							{
								$new_user_response_array[$input_name] = $user_response['xqcas_' . $question_id . '_' . $input_name];
							}
						} else
						{
							if (is_array($user_response))
							{
								foreach ($user_response as $index => $user_response)
								{
									$new_index = str_replace('xqcas_' . $question_id . '_', '', $index);
									$new_user_response_for_matrix[$new_index] = $user_response;
								}
							}
							if (is_array($new_user_response_for_matrix))
							{
								$new_user_response_array = $new_user_response_for_matrix;
							}
						}
					} elseif ($mode == 't')
					{

						if (is_a($input, 'stack_checkbox_input'))
						{
						} elseif (!is_a($input, 'stack_matrix_input'))
						{
							if ($user_response['xqcas_' . $question_id . '_' . $input_name])
							{
								$new_user_response_array[$input_name] = $user_response['xqcas_' . $question_id . '_' . $input_name];
							}
						} else
						{
							foreach ($user_response as $index => $user_response)
							{
								$new_index = str_replace('xqcas_' . $question_id . '_', '', $index);
								$new_user_response_for_matrix[$new_index] = $user_response;
							}
							if (is_array($new_user_response_for_matrix))
							{
								$new_user_response_array = $new_user_response_for_matrix;
							}
						}
					}
				}
				break;
			case 'full_to_value':
				//from full to value
				foreach ($inputs as $input_name => $input)
				{
					//If input is not matrix
					if (is_a($input, 'stack_checkbox_input'))
					{

					} elseif (!is_a($input, 'stack_matrix_input'))
					{
						if (isset($user_response['xqcas_' . $question_id . '_' . $input_name]))
						{
							$new_user_response_array['xqcas_input_' . $input_name . '_value'] = $user_response['xqcas_' . $question_id . '_' . $input_name];
						}
					} else
					{
						//Don't change
						$new_user_response_array = $user_response;
					}
				}
				break;
			case 'value_to_reduced':
				//from value to reduced
				foreach ($inputs as $input_name => $input)
				{
					//If input is not matrix
					if (is_a($input, 'stack_checkbox_input'))
					{

					} elseif (!is_a($input, 'stack_matrix_input'))
					{
						if (isset($user_response['xqcas_input_' . $input_name . '_value']))
						{
							$new_user_response_array[$input_name] = $user_response['xqcas_input_' . $input_name . '_value'];
						}
					} else
					{
						if (isset($user_response['xqcas_input_' . $input_name . '_value']))
						{
							$new_user_response_array = $input->get_expected_data($user_response['xqcas_input_' . $input_name . '_value']);
						}
						unset($new_user_response_array[$input_name . '_val']);
					}
				}
				break;
			case 'reduced_to_value':
				//from reduced to value
				foreach ($inputs as $input_name => $input)
				{
					//If input is not matrix
					if (is_subclass_of($input,"stack_dropdown_input"))
					{
						$new_user_response_array['xqcas_input_' . $input_name . '_value'] = $input->maxima_to_response_array($user_response[$input_name]);
					} elseif (!is_a($input, 'stack_matrix_input'))
					{
						if (isset($user_response[$input_name]))
						{
							$new_user_response_array['xqcas_input_' . $input_name . '_value'] = $user_response[$input_name];
						}
					} else
					{
						$new_user_response_array['xqcas_input_' . $input_name . '_value'] = $input->maxima_to_response_array($user_response[$input_name]);
						unset($new_user_response_array['xqcas_input_' . $input_name . '_value'][$input_name . '_val']);
					}
				}
				break;
			default:
				throw new stack_exception('exception_unknown_change_of_style');
				break;
		}

		return $new_user_response_array;
	}


	/**
	 * Creates stack_options from an assStackQuestionOptions object.
	 * @param assStackQuestionOptions $ilias_options
	 */
	public static function _createOptions(assStackQuestionOptions $ilias_options)
	{
		$parameters = array( // Array of public class settings for this class.
			'display' => array('type' => 'list', 'value' => 'LaTeX', 'strict' => true, 'values' => array('LaTeX', 'MathML', 'String'), 'caskey' => 'OPT_OUTPUT', 'castype' => 'string',), 'multiplicationsign' => array('type' => 'list', 'value' => $ilias_options->getMultiplicationSign(), 'strict' => true, 'values' => array('dot', 'cross', 'none'), 'caskey' => 'make_multsgn', 'castype' => 'fun',), 'complexno' => array('type' => 'list', 'value' => $ilias_options->getComplexNumbers(), 'strict' => true, 'values' => array('i', 'j', 'symi', 'symj'), 'caskey' => 'make_complexJ', 'castype' => 'fun',), 'inversetrig' => array('type' => 'list', 'value' => $ilias_options->getInverseTrig(), 'strict' => true, 'values' => array('cos-1', 'acos', 'arccos'), 'caskey' => 'make_arccos', 'castype' => 'fun',), 'floats' => array('type' => 'boolean', 'value' => 1, 'strict' => true, 'values' => array(), 'caskey' => 'OPT_NoFloats', 'castype' => 'ex',), 'sqrtsign' => array('type' => 'boolean', 'value' => $ilias_options->getSqrtSign(), 'strict' => true, 'values' => array(), 'caskey' => 'sqrtdispflag', 'castype' => 'ex',), 'simplify' => array('type' => 'boolean', 'value' => $ilias_options->getQuestionSimplify(), 'strict' => true, 'values' => array(), 'caskey' => 'simp', 'castype' => 'ex',), 'assumepos' => array('type' => 'boolean', 'value' => $ilias_options->getAssumePositive(), 'strict' => true, 'values' => array(), 'caskey' => 'assume_pos', 'castype' => 'ex',),);

		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionStackFactory.php';
		$stack_factory = new assStackQuestionStackFactory();

		return $stack_factory->get("options", $parameters);
	}

	/**
	 * @param $array .
	 * @return bool
	 */
	public static function _isArrayEmpty($array)
	{
		//If array is not empty returns it, otherwise return FALSE;
		foreach ($array as $value)
		{
			if ($value != '' AND $value != '[]')
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Checks wheter a question uses randomisation or not
	 * @param $question_variables_text string the question variables
	 * @return boolean
	 */
	public static function _questionHasRandomVariables($question_variables_text)
	{
		return (boolean)preg_match('~\brand~', $question_variables_text);
	}

	/**
	 * Checks wheter a question uses randomisation or not
	 * @param $question_variables_text string the question variables
	 * @return boolean
	 */
	public static function _getInputsAndPRTStructure($question_id)
	{
		$structure = array();
		$structure['input'] = assStackQuestionInput::_read($question_id);
		$structure['prt'] = assStackQuestionPRT::_read($question_id);

		return $structure;
	}

	public static function _useInstantValidation()
	{
		global $DIC;
		$db = $DIC->database();
		$query = 'SELECT value FROM xqcas_configuration WHERE parameter_name = "instant_validation"';

		$result = $db->query($query);
		while ($row = $db->fetchAssoc($result))
		{
			if ((int)$row['value'])
			{
				return TRUE;
			} else
			{
				return FALSE;
			}
		}

	}

	public static function _getSeedFromTest($question_id, $active_id, $pass, $prt_name)
	{
		global $DIC;
		$db = $DIC->database();
		$query = 'SELECT value2 FROM tst_solutions WHERE question_fi = ' . $question_id;
		$query .= ' AND active_fi = ' . $active_id;
		$query .= ' AND pass = ' . $pass;
		$query .= ' AND value1 = "xqcas_prt_' . $prt_name . '_seed"';

		$result = $db->query($query);
		while ($row = $db->fetchAssoc($result))
		{
			if ((int)$row['value2'])
			{
				return (int)$row['value2'];
			} else
			{
				return FALSE;
			}
		}

	}

	public static function _isInputEvaluated($prt, $input_name)
	{
		foreach ($prt->getPRTNodes() as $node_name => $node)
		{
			if (strpos($node->getStudentAnswer(), $input_name) !== false OR strpos($node->getTeacherAnswer(), $input_name))
			{
				return TRUE;
			}
		}

		return FALSE;

	}

	/**#
	 * Used for show Info labels in inputs or PRT
	 * @param $haystack
	 * @param $needle
	 * @return bool
	 */
	public static function _endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0)
		{
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	/**
	 * This function returns the LaTeX rendered version of $text
	 * @param $text The raw text
	 * @return string
	 */
	public static function _getLatex($text)
	{
		/*
		 * Step 1 check current platform's LaTeX delimiters
		 */
		//Replace dollars but using mathjax settings in each platform.
		$mathJaxSetting = new ilSetting("MathJax");
		//By default [tex]
		$start = '[tex]';
		$end = '[/tex]';

		switch ((int)$mathJaxSetting->setting['limiter'])
		{
			case 0:
				/*\(...\)*/
				$start = '\(';
				$end = '\)';
				break;
			case 1:
				/*[tex]...[/tex]*/
				$start = '[tex]';
				$end = '[/tex]';
				break;
			case 2:
				/*&lt;span class="math"&gt;...&lt;/span&gt;*/
				$start = '&lt;span class="math"&gt;';
				$end = '&lt;/span&gt;';
				break;
			default:

		}
		/*
		 * Step 2 Replace $$ from STACK and all other LaTeX delimiter to the current platform's delimiter.
		 */
		//Get all $$ to replace it
		$text = preg_replace('~(?<!\\\\)\$\$(.*?)(?<!\\\\)\$\$~', $start . '$1' . $end, $text);
		$text = preg_replace('~(?<!\\\\)\$(.*?)(?<!\\\\)\$~', $start . '$1' . $end, $text);

		//Comment this in order to have different ebhaviour between display and inline mode of LaTeX,
		//Solving bug 20783
		//Search for all /(/) and change it to the current limiter in Mathjaxsettings
		//$text = str_replace('\(', $start, $text);
		//$text = str_replace('\)', $end, $text);

		//Search for all \[\] and change it to the current limiter in Mathjaxsettings
		//$text = str_replace('\[', $start, $text);
		//$text = str_replace('\]', $end, $text);

		//Search for all [tex] and change it to the current limiter in Mathjaxsettings
		$text = str_replace('[tex]', $start, $text);
		$text = str_replace('[/tex]', $end, $text);
		//Search for all &lt;span class="math"&gt;...&lt;/span&gt; and change it to the current limiter in Mathjaxsettings
		$text = preg_replace('/<span class="math">(.*?)<\/span>/', $start . '$1' . $end, $text);

		//Search for all &lt;span class="latex"&gt;...&lt;/span&gt; and change it to the current limiter in Mathjaxsettings
		$text = preg_replace('/<span class="latex">(.*?)<\/span>/', $start . '$1' . $end, $text);

		// replace special characters to prevent problems with the ILIAS template system
		// eg. if someone uses {1} as an answer, nothing will be shown without the replacement
		$text = str_replace("{", "&#123;", $text);
		$text = str_replace("}", "&#125;", $text);
		$text = str_replace("\\", "&#92;", $text);

		/*
		 * Step 3 User ilMathJax::getInstance()->insertLatexImages to deliver the LaTeX code.
		 */
		include_once './Services/MathJax/classes/class.ilMathJax.php';
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/mathsoutput/mathsoutput.class.php';
		//ilMathJax::getInstance()->insertLatexImages cannot render \( delimiters so we change it to [tex]
		if ($start == '\(')
		{
			return stack_maths::process_display_castext(ilMathJax::getInstance()->insertLatexImages($text));
		} else
		{
			return stack_maths::process_display_castext(ilMathJax::getInstance()->insertLatexImages($text, $start, $end));
		}
	}

	public static function _getNewTestCaseNumber($question_id)
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'SELECT MAX(test_case) FROM xqcas_qtests WHERE question_id = ' . $question_id;

		$result = $db->query($query);
		while ($row = $db->fetchAssoc($result))
		{
			if ((int)$row['MAX(test_case)'])
			{
				return ((int)$row['MAX(test_case)'] + 1);
			} else
			{
				return 1;
			}
		}
	}

	/**
	 * This method convert a text with old delimiters such $$ or @ to the new {@ and platform delimiter
	 * and also to the platform delimiter for LaTeX in case this delimiter is different as the one used in the question.
	 * This come from version 4.0 of STACK in Moodle
	 * @param $old_text string Text to be converted
	 * @param $platform_latex_delimiter string
	 * @return array
	 */
	public static function _updateMathDelimiters($old_text, $platform_latex_delimiter)
	{
		$results = array();

		return $results;
	}

	/**
	 * @return array of available type names.
	 * Refactoring of stack_input_factory::get_availavle_types
	 */
	public static function _getAvailableTypes()
	{

		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/algebraic/algebraic.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/boolean/boolean.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/checkbox/checkbox.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/dropdown/dropdown.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/equiv/equiv.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/matrix/matrix.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/notes/notes.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/radio/radio.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/singlechar/singlechar.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/textarea/textarea.class.php';
		include_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/input/units/units.class.php';

		$types = array('algebraic' => 'stack_algebraic_input', 'boolean' => 'stack_boolean_input', 'checkbox' => 'stack_checkbox_input', 'dropdown' => 'stack_dropdown_input', 'equiv' => 'stack_equiv_input', 'matrix' => 'stack_matrix_input', 'notes' => 'stack_notes_input', 'radio' => 'stack_radio_input', 'singlechar' => 'stack_singlechar_input', 'textarea' => 'stack_textarea_input', 'units' => 'stack_units_input');

		return $types;
	}

	/**
	 * This function will be use in the import routines, in order to check if the questions follow the new syntax for STACK questions.
	 * @param string $a_text
	 * @return string The converted text.
	 */
	public static function _casTextConverter($a_text, $a_question_title = "", $a_show_alert = FALSE)
	{
		global $DIC;
		$lng = $DIC->language();
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/mathsoutput/mathsoutput.class.php';
		require_once('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionInitialization.php');

		//Do replacement
		//#22779 a_strip_html must be false
		$new_text = ilUtil::secureString(stack_maths::replace_dollars($a_text), FALSE);

		//STEP 4 Send back the fixed text
		return $new_text;
	}

	public static function _adaptUserResponseTo($user_response, $question_id, $format)
	{
		$adapted_user_response = array();
		foreach ($user_response as $input_name => $input_value)
		{
			if ($format == "only_input_names")
			{
				$adapted_user_response[str_replace("xqcas_" . $question_id . "_", "", $input_name)] = $input_value;
			}
		}

		return $adapted_user_response;
	}


	public static function stack_output_castext($castext)
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/stack/mathsoutput/mathsoutput.class.php';
		require_once('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionInitialization.php');

		return stack_maths::process_display_castext($castext);
	}

}
