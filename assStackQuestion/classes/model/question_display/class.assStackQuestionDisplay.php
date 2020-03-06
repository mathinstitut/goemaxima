<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question DISPLAY OF QUESTIONS
 * This class provides a view for a specifiSTACK Questionon
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionDisplay
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
	 *
	 * @var ilTemplate
	 */
	private $template;

	/**
	 *
	 * @var array
	 */
	private $user_response;

	/**
	 *
	 * @var string
	 */
	private $question_text;

	/**
	 * @var array
	 * This array has the following structure:
	 * ['prts'] = array of PRT feedback with $prt_name as key, contains 2 fields.
	 * -  ['general'] that can have the following values: 1 for correct, 0 for partially correct and -1 for incorrect.
	 * -  ['specific'] that have an string with the specific feedback for that PRT instantiated.
	 * ['show'] = Boolean, true when feedback must be shown and false if not.
	 */
	private $inline_feedback;


	/**
	 * Sets all information needed for question display,
	 * Be aware of $question, here is not an assStackQuestion but an assStackQuestionStackQuestion object
	 * @param ilassStackQuestionPlugin $plugin
	 * @param assStackQuestionStackQuestion $question
	 * @param array OR boolean $user_response
	 */
	function __construct(ilassStackQuestionPlugin $plugin, assStackQuestionStackQuestion $question, $user_response = NULL, $inline_feedback = TRUE)
	{
		//Set plugin object
		$this->setPlugin($plugin);
		//Set question object to be displayed
		$this->setQuestion($question);
		//Set user solutions
		//In assStackQuestionDisplay the User response should be stored with the "value" format for assStackQuestionUtils::_getUserResponse.
		$this->setUserResponse($user_response);
		//Set specific data and variables for the display
		//Set question text
		$this->setQuestionText($question->getQuestionTextInstantiated());
		//Set template for question display
		$this->setTemplate($plugin->getTemplate("tpl.il_as_qpl_xqcas_question_display.html"));

		//2.3 Set the inline feedback data
		$this->setInlineFeedback($inline_feedback);
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestionGUI or assStackQuestionPreview to get the question Display.
	 * @return array STACK Questiontion display data
	 */
	public function getQuestionDisplayData($in_test = FALSE)
	{
		$display_data = array();

		//Set Question text instantiated
		if ($in_test)
		{
			$display_data['question_text'] = $this->getQuestionText();
		} else
		{
			$display_data['question_text'] = $this->getQuestion()->getQuestionTextInstantiated();
		}

		//Specific feedback
		$display_data['question_specific_feedback'] = $this->getQuestion()->getSpecificFeedbackInstantiated();

		//Set question_id
		$display_data['question_id'] = (string)$this->getQuestion()->getQuestionId();

		//Step 1: Get the replacement per each placeholder.
		foreach ($this->getQuestion()->getInputs() as $input_name => $input)
		{
			//Step 1.1: Replacement for input placeholders
			$display_data['inputs'][$input_name]['display'] = $this->replacementForInputPlaceholders($input, $input_name, $in_test, FALSE);
			$display_data['inputs'][$input_name]['display_rendered'] = $this->replacementForInputPlaceholders($input, $input_name, $in_test, TRUE);
			$display_data['inputs'][$input_name]['validation'] = $this->replacementForValidationInput($input, $input_name, $in_test, TRUE);
			if (is_a($input, "stack_equiv_input") OR is_a($input, "stack_textarea_input"))
			{
				$display_data['inputs'][$input_name]['text_area'] = TRUE;
			} else
			{
				$display_data['inputs'][$input_name]['text_area'] = FALSE;
			}
			if (is_a($input, "stack_radio_input") OR is_a($input, "stack_dropdown_input") OR is_a($input, "stack_checkbox_input") OR is_a($input, "stack_notes_input"))
			{
				$display_data['inputs'][$input_name]['show_validation'] = 0;
			} else
			{
				$display_data['inputs'][$input_name]['show_validation'] = $input->get_parameter("showValidation");
			}
			//Step 1.2: Replacement for validation placeholders
			if ((int)$this->getQuestion()->getInputs($input_name)->get_parameter("showValidation"))
			{
				$display_data['validation'][$input_name] = $this->replacementForValidationPlaceholders($input, $input_name);
			} else
			{
				$display_data['validation'][$input_name] = "";
			}
			//Step 1.3 set matrix info
			if (is_a($input, "stack_matrix_input"))
			{
				$display_data['inputs'][$input_name]['matrix_w'] = $input->width;
				$display_data['inputs'][$input_name]['matrix_h'] = $input->height;
			}
		}
		//Step 2: Get the replacement per each Feedback placeholder
		foreach ($this->getQuestion()->getPRTs() as $prt_name => $prt)
		{
			//Step 1.1: Replacement for input placeholders
			$display_data['prts'][$prt_name]['display'] = $this->replacementForPRTPlaceholders($prt, $prt_name, $in_test);
		}

		return $display_data;
	}

	/**
	 * Replace input placeholders by correspondant HTML code for the input
	 * @param stack_input $input
	 * @param string $input_name
	 */
	private function replacementForInputPlaceholders($input, $input_name, $in_test, $render_display = FALSE)
	{
		//Get student answer for this inputF
		//In assStackQuestionDisplay the User response should be store with the "value" format for assStackQuestionUtils::_getUserResponse.
		$student_answer = $this->getUserResponse($input_name, $in_test);
		//Bug https://www.ilias.de/mantis/view.php?id=22129 about matrix syntax hint
		if (!sizeof($student_answer) AND ($input->get_parameter('syntaxHint') != '') AND is_a($input, 'stack_matrix_input'))
		{
			$student_answer = assStackQuestionUtils::_changeUserResponseStyle(array($input_name => $input->get_parameter('syntaxHint')), $this->getQuestion()->getQuestionId(), array($input_name => $input), 'reduced_to_value');
			$student_answer = $student_answer["xqcas_input_" . $input_name . "_value"];
		}

		//Create input state
		if ($in_test)
		{
			$state = $this->getQuestion()->getInputState($input_name, $student_answer[$input_name], $input->get_parameter('forbidWords', ''));

		} else
		{
			$state = $this->getQuestion()->getInputState($input_name, $student_answer, $input->get_parameter('forbidWords', ''));
		}
		if ($render_display)
		{
			//Solve problem with string input type
			if (is_array($student_answer))
			{
				if ($student_answer[$input_name] == NULL)
				{
					return "";
				}
			}

			return $state->contentsdisplayed;
		}
		//Get teacher answer value for equivalence reasoning input firstline problem #22847
		$ta_value = $this->getQuestion()->getSession()->get_value_key($input->get_teacher_answer());

		//Return renderised input
		if (get_class($input) == 'stack_algebraic_input')
		{
			$input_info = new stdClass();
			$input_info->input = $input;
			$input_info->name = $input_name;
			$input_info->state = $state;
			$input_info->teacher_answer = $ta_value;

			return $input_info;
		} else
		{
			if (is_subclass_of($input, 'stack_dropdown_input'))
			{
				return $input->render($state, 'xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name, FALSE, $ta_value);
			} else
			{
				return $input->render($state, 'xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name, FALSE, $ta_value);
			}
		}
	}

	/**
	 * Replace validation placeholders by validation button.
	 * This is different thatn STACK because at the moment instant validtaion is not
	 * supported by STACK Questionestion plugin.
	 * @param stack_input $input
	 * @param string $input_name
	 */
	private function replacementForValidationPlaceholders($input, $input_name)
	{
		if (!is_a($input, 'stack_boolean_input') AND !is_subclass_of($input, 'stack_dropdown_input') AND !is_a($input, 'stack_dropdown_input'))
		{
			if ($input->get_parameter("showValidation"))
			{
				if ($this->getQuestion()->getInstantValidation())
				{
					return 'instant';
				} else
				{
					return 'button';
				}
			} else
			{
				return 'hidden';
			}
		} else
		{
			return FALSE;
		}
	}

	/**
	 * Replace Feedback placeholders by feedback in case it is needed
	 * @param $prt
	 * @param $prt_name
	 * @param $in_test
	 */
	private function replacementForPRTPlaceholders($prt, $prt_name, $in_test)
	{
		$string = "";
		if (sizeof($this->getInlineFeedback()))
		{
			//feedback
			$string .= '<div class="alert alert-warning" role="alert">';
			//Generic feedback
			$string .= $this->inline_feedback['prt'][$prt_name]['status']['message'];
			//$string .= '<br>';
			//Specific feedback
			$string .= $this->inline_feedback['prt'][$prt_name]['feedback'];
			$string .= $this->inline_feedback['prt'][$prt_name]['errors'];
			$string .= '</div>';
		}
		return $string;
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @return ilassStackQuestionPlugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @return assStackQuestionStackQuestion
	 */
	public function getQuestion()
	{
		return $this->question;
	}

	/**
	 * @param string $selector
	 * @return array
	 */
	public function getUserResponse($selector = '', $in_test = FALSE)
	{
		$user_answer = array();

		//In assStackQuestionDisplay the User response should be stored with the "value" format for assStackQuestionUtils::_getUserResponse.
		if ($selector)
		{
			if (is_array($this->user_response))
			{
				if ($in_test)
				{
					if (array_key_exists($selector, $this->user_response))
					{
						foreach ($this->getQuestion()->getInputs() as $input_name => $input)
						{
							if ($input_name == $selector)
							{
								$user_answer[$selector] = array($selector => $this->user_response[$selector]);
							}
						}
					} else
					{
						return array($selector => '');
					}

					return $user_answer[$selector];
				} else
				{
					//preview mode
					foreach ($this->getQuestion()->getInputs() as $input_name => $input)
					{
						if ($input_name == $selector)
						{
							if (is_a($input, 'stack_matrix_input'))
							{
								$matrix_input = array();
								foreach ($this->user_response as $sub_key => $response)
								{
									if (strpos($sub_key, $input_name . "_") !== FALSE)
									{
										$matrix_input[$sub_key] = $response;
									}
								}
								$user_answer[$selector] = $matrix_input;
							} elseif (is_subclass_of($input, "stack_dropdown_input"))
							{
								$user_answer[$selector] = $this->user_response;
							} else
							{
								$user_answer[$selector] = array($selector => $this->user_response[$selector]);
							}
						}
					}

					return $user_answer[$selector];
				}
			} else
			{
				return array();
			}
		} else
		{
			return $this->user_response;
		}
	}

	/**
	 * @return string
	 */
	public function getQuestionText()
	{
		return $this->question_text;
	}

	/**
	 * @return ilTemplate
	 */
	public function getTemplate()
	{
		return $this->template;
	}


	/**
	 * @return array
	 */
	public function getInlineFeedback($selector1 = '', $selector2 = '', $selector3 = '')
	{
		if ($selector1 AND !$selector2)
		{
			return $this->inline_feedback[$selector1];
		} elseif ($selector1 AND $selector2)
		{
			return $this->inline_feedback[$selector1][$selector2];
		} elseif ($selector1 AND $selector2 AND $selector3)
		{
			return $this->inline_feedback[$selector1][$selector2][$selector3];
		} else
		{
			return $this->inline_feedback;
		}
	}

	/**
	 * @param ilassStackQuestionPlugin $plugin
	 */
	public function setPlugin(ilassStackQuestionPlugin $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @param assStackQuestionStackQuestion $question
	 */
	public function setQuestion(assStackQuestionStackQuestion $question)
	{
		$this->question = $question;
	}

	/**
	 * @param $user_response
	 * @param string $selector
	 */
	public function setUserResponse($user_response, $selector = '')
	{
		//In assStackQuestionDisplay the User response should be stored with the "value" format for assStackQuestionUtils::_getUserResponse.
		if ($selector)
		{
			$this->user_response[$selector] = $user_response;
		} else
		{
			$this->user_response = $user_response;
		}
	}

	/**
	 * @param $question_text
	 */
	public function setQuestionText($question_text)
	{
		$this->question_text = $question_text;
	}

	/**
	 * @param ilTemplate $template
	 */
	public function setTemplate(ilTemplate $template)
	{
		$this->template = $template;
	}

	/**
	 * @param array $inline_feedback
	 */
	public function setInlineFeedback($inline_feedback, $selector = '')
	{
		if ($selector)
		{
			$this->inline_feedback[$selector] = $inline_feedback;
		} else
		{
			$this->inline_feedback = $inline_feedback;
		}
	}

	public function replacementForValidationInput($input, $input_name, $in_test, $render_display = FALSE)
	{
		//Get student answer for this inputF
		//In assStackQuestionDisplay the User response should be store with the "value" format for assStackQuestionUtils::_getUserResponse.
		$student_answer = $this->getUserResponse($input_name, $in_test);
		//Bug https://www.ilias.de/mantis/view.php?id=22129 about matrix syntax hint
		if (!sizeof($student_answer) AND ($input->get_parameter('syntaxHint') != '') AND is_a($input, 'stack_matrix_input'))
		{
			$student_answer = assStackQuestionUtils::_changeUserResponseStyle(array($input_name => $input->get_parameter('syntaxHint')), $this->getQuestion()->getQuestionId(), array($input_name => $input), 'reduced_to_value');
			$student_answer = $student_answer["xqcas_input_" . $input_name . "_value"];
		}

		$input_state = $this->getQuestion()->getInputStates($input_name);
		$input_size = (string)$input->get_parameter("boxWidth");

		if (is_a($input, "stack_algebraic_input") OR is_a($input, "stack_numerical_input") OR is_a($input, "stack_singlechar_input") OR is_a($input, "stack_boolean_input") OR is_a($input, "stack_units_input"))
		{
			if (is_array($student_answer[$input_name]))
			{
				$student_answer_value = $student_answer[$input_name][$input_name . "_val"];
			} elseif (is_string($student_answer[$input_name]))
			{
				$student_answer_value = $student_answer[$input_name];
			}

			$input_html = '<code>' . $student_answer_value . '</code>';

			$validation_message = stack_string('studentValidation_yourLastAnswer', $input_state->contentsdisplayed);

			return "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . $input_html . $validation_message . "</td></tr></table>";
		}
		if (is_a($input, "stack_matrix_input"))
		{
			$matrix_input_rows = (int)$input->height;
			$matrix_input_columns = (int)$input->width;

			$user_matrix = "<table class='xqcas_matrix_validation' style='display:inline'>";
			for ($i = 0; $i < $matrix_input_rows; $i++)
			{
				$user_matrix .= "<tr>";
				for ($j = 0; $j < $matrix_input_columns; $j++)
				{
					$user_matrix .= "<td class='xqcas_matrix_validation'>";
					$user_filled_input = '<code>' . $student_answer[$input_name][$input_name . "_sub_" . $i . "_" . $j] . '</code>';
					$user_matrix .= $user_filled_input;
					$user_matrix .= "</td>";
				}
				$user_matrix .= "</tr>";
			}
			$user_matrix .= "</table>";

			$validation_message = stack_string('studentValidation_yourLastAnswer', $input_state->contentsdisplayed);

			return "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . $user_matrix . $validation_message . "</td></tr></table>";
		}
		if (is_a($input, "stack_checkbox_input"))
		{
			return "";
		}
		if (is_a($input, "stack_radio_input"))
		{
			return "";
		}
		if (is_a($input, "stack_dropdown_input"))
		{
			return "";
		}
		if (is_a($input, "stack_equiv_input") or is_a($input, "stack_textarea_input"))
		{
			$feedback = "";
			$textarea_html = "";
			foreach ($input_state->contents as $key => $val)
			{
				$textarea_html .= '<code>' . $val . '</code></br>';
			}

			$feedback .= html_writer::tag('p', "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . $textarea_html . "</td><td class='xqcas_validation'>" . stack_string('studentValidation_yourLastAnswer', $input_state->contentsdisplayed) . "</td></tr>");

			if ($input::INVALID == $input_state->status)
			{
				$feedback .= html_writer::tag('p', "<tr><td class='xqcas_validation_status'>" . stack_string('studentValidation_invalidAnswer') . "</td></tr>");
			}

			if (!($input_state->lvars === '' or $input_state->lvars === '[]'))
			{
				$feedback .= "<tr><td class='xqcas_validation_variables'>" . $input->tag_listofvariables($input_state->lvars) . "</td></tr>";
			}
			$feedback .= "</table>";

			return "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . $feedback . "</td></tr></table>";

		}
		if (is_a($input, "stack_notes_input"))
		{
			$string = "";
			$string .= '<div class="alert alert-warning" role="alert">';
			$string .= $this->getPlugin()->txt("notes_best_solution_message");
			$string .= '</div>';
			$result["value"] = $string;
			$result["display"] = "";

			return $result;
		}
	}


}