<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question FEEDBACK management
 * This class manages the feedback after a STACK Question evaluation
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 1.6.1$$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionFeedback
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * The question already evaluated
	 * @var assStackQuestionStackQuestion
	 */
	private $question;

	/**
	 * @param ilassStackQuestionPlugin $plugin
	 * @param assStackQuestionStackQuestion $question
	 */
	function __construct(ilassStackQuestionPlugin $plugin, assStackQuestionStackQuestion $question)
	{
		//Set plugin object
		$this->setPlugin($plugin);
		//Set question object already evaluated
		$this->setQuestion($question);
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestion and assStackQuestionPreview
	 * This method creates the feedback information array and returns it
	 * @return array
	 */
	public function getFeedback()
	{
		//Feedback structure creation
		$question_feedback = array();

		//Fill global question vars
		$question_feedback['question_text'] = $this->getQuestion()->getQuestionTextInstantiated();
		$question_feedback['general_feedback'] = $this->getQuestion()->getGeneralFeedback();
		$question_feedback['question_note'] = $this->getQuestion()->getQuestionNoteInstantiated();
		$question_feedback['points'] = $this->getQuestion()->reached_points;

		//Fill specific PRT vars
		foreach ($this->getQuestion()->getPRTResults() as $prt_name => $prt_data)
		{
			$question_feedback['prt'][$prt_name] = $this->createPRTFeedback($prt_name, $prt_data);
		}

		return $question_feedback;
	}

	/**
	 * Creates specific feedback for each PRT evaluated
	 * Called from $this->getFeedback()
	 * @param string $prt_name
	 * @param array $prt_data
	 * @return array
	 */
	private function createPRTFeedback($prt_name, $prt_data)
	{
		//PRT Feedback structure creation
		$prt_feedback = array();
		//fill user response data
		$prt_feedback['response'] = $this->fillUserResponses($prt_data['inputs_evaluated']);
		//fill points data
		$prt_feedback['points'] = $prt_data['points'];
		//fill errors data
		$prt_feedback['errors'] = $prt_data['state']->__get('errors');
		//fill feedback message data
		$prt_feedback['feedback'] = $this->fillFeedback($prt_data['state']);
		//fill status and status message
		$prt_feedback['status'] = $this->fillStatus($prt_data['state']);
		//fill answernote
		$prt_feedback['answernote'] = $this->fillAnswerNote($prt_data['state']);

		return $prt_feedback;
	}

	/**
	 * Fills the user response structure for feedback
	 * Called from $this->createPRTFeedback()
	 * @param array $inputs_evaluated
	 * @return array
	 */
	private function fillUserResponses($inputs_evaluated)
	{
		//Prepare user response structure array
		$user_responses = array();

		$count = 0;
		//Fill user_response per each input evaluated by current PRT
		foreach ($inputs_evaluated as $input_name => $user_response_value)
		{

			//Input is Ok, use input states
			if (is_a($this->getQuestion()->getInputStates($input_name), 'stack_input_state'))
			{
				//PROBLEM WITH NOTES; AS THEY DONT HAVE PRT WE GOT STRANGER THINGS WHEN EVALUATION IS DONE :)
				//Fill value
				$user_responses[$input_name]['value'] = $this->getQuestion()->getInputStates($input_name)->__get('contentsmodified');
				//Fill LaTeX display
				$user_responses[$input_name]['display'] = assStackQuestionUtils::_solveKeyBracketsBug($this->getQuestion()->getInputStates($input_name)->__get('contentsdisplayed'));
				//Fill model answer
				$correct_response = $this->getCorrectResponsePlaceholders($input_name);
				$user_responses[$input_name]['model_answer'] = $correct_response["value"];
				//Fill model answer display
				$user_responses[$input_name]['model_answer_display'] = $correct_response["display"];

			} else
			{
				//Input was not Ok, use getLatexText
				//Fill value
				$user_responses[$input_name]['value'] = $user_response_value;
				//Fill LaTeX display
				$user_responses[$input_name]['display'] = assStackQuestionUtils::_solveKeyBracketsBug($user_response_value);
				//Fill model answer
				$correct_response = $this->getCorrectResponsePlaceholders($input_name);
				$user_responses[$input_name]['model_answer'] = $correct_response["value"];
				//Fill model answer display
				$user_responses[$input_name]['model_answer_display'] = $correct_response["display"];
			}
		}

		return $user_responses;
	}


	/**
	 * Gets the model answer for the current input
	 * @param string $input_name
	 * @return string
	 */
	private function getModelAnswerDisplay($input_name)
	{
		$input = $this->getQuestion()->getInputs($input_name);

		$state = $this->getQuestion()->getInputState($input_name, $this->getTeacherAnswer());

		return $input->render($state, $input_name, true, $this->getQuestion()->getSession()->get_display_key($input_name));
	}

	/**
	 * Create feedback message
	 * @param $prt_state
	 * @return string
	 */
	private function fillFeedback($prt_state)
	{
		//Prepare feedback message
		$feedback = '';
		//For each feedback obj add a line the the message with the feedback.
		if ($prt_state->__get('feedback'))
		{
			foreach ($prt_state->__get('feedback') as $feedback_obj)
			{
				$feedback .= $prt_state->substitue_variables_in_feedback($feedback_obj->feedback);
				$feedback .= '</br>';
			}
		}

		return $feedback;
	}

	/**
	 * Determines status for the current PRT and sets the message
	 * @param $prt_state
	 * @return array
	 */
	private function fillStatus($prt_state)
	{
		//Prepare status structure
		$status = array();
		if ((float)$prt_state->__get('score') * (float)$prt_state->__get('weight') == (float)$prt_state->__get('weight'))
		{
			//CORRECT
			$status['value'] = 1;
			$status['message'] = $this->getQuestion()->getPRTCorrectInstantiated();
		} elseif ((float)$prt_state->__get('score') > 0.0 AND (float)$prt_state->__get('score') < (float)$prt_state->__get('weight'))
		{
			//PARTIALLY CORRECT
			$status['value'] = 0;
			$status['message'] = $this->getQuestion()->getPRTPartiallyCorrectInstantiated();
		} else
		{
			//INCORRECT
			$status['value'] = -1;
			$status['message'] = $this->getQuestion()->getPRTIncorrectInstantiated();
		}

		return $status;
	}

	/**
	 * Determines answernote for the current PRT and sets the message
	 * @param $prt_state
	 * @return array
	 */
	private function fillAnswerNote($prt_state)
	{
		if (is_array($prt_state->__get('answernotes')))
		{
			return implode('_', $prt_state->__get('answernotes'));
		}
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

	private function getTeacherAnswer($selected_input = "")
	{
		$teacher_answer = array();
		foreach ($this->getQuestion()->getInputs() as $input_name => $input)
		{
			$teacher_answer = array_merge($teacher_answer, $input->get_correct_response($this->getQuestion()->getSession()->get_value_key($input_name, true)));
		}

		if ($selected_input)
		{
			if (isset($teacher_answer[$selected_input]))
			{
				return $teacher_answer[$selected_input];
			}
		} else
		{
			return $teacher_answer;
		}
	}

	/**
	 * We need to make sure the inputs are displayed in the order in which they
	 * occur in the question text. This is not necessarily the order in which they
	 * are listed in the array $this->inputs.
	 */
	public function format_correct_response($input_name = "")
	{
		$feedback = '';

		if ($input_name)
		{
			$feedback = stack_string('studentValidation_yourLastAnswer', '\( ' . $this->getQuestion()->getSession()->get_display_key($input_name) . ' \)');
		} else
		{
			$inputs = stack_utils::extract_placeholders($this->getQuestion()->getQuestionTextInstantiated(), 'input');
			foreach ($inputs as $name)
			{
				$feedback .= stack_string('studentValidation_yourLastAnswer', '\( ' . $this->getQuestion()->getSession()->get_display_key($name) . ' \)');
			}
		}

		return assStackQuestionUtils::stack_output_castext($feedback);

	}

	public function getCorrectResponsePlaceholders($input_name = "")
	{
		if ($input_name)
		{
			$input = $this->getQuestion()->getInputs($input_name);
			$input_state = $this->getQuestion()->getInputStates($input_name);
			$correct_answer = $input->get_correct_response($this->getQuestion()->getSession()->get_value_key($input_name, true));

			if (is_a($input, "stack_string_input"))
			{
				$correct_answer_array = $input->get_correct_response($this->getQuestion()->getSession()->get_value_key($input_name, true));
				$correct_answer = $correct_answer_array[$input_name];
				if (!$correct_answer)
				{
					$correct_answer = $input->get_teacher_answer();
				}

				$input_size = strlen($correct_answer) * 1.1;
				//Notice this is different to other due to quotes wrapping value
				$input_html_display = '<input type="text" size="' . $input_size . '" id="xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name . '_postvalidation" value="' . $correct_answer . '" disabled="disabled">';
				$result = array();
				$result["value"] = $input_html_display;
				$result["display"] = "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . '<code>' . $correct_answer . '</code>' . $this->format_correct_response($input_name) . "</td></tr></table>";

				return $result;
			}
			if (is_a($input, "stack_algebraic_input"))
			{
				$correct_answer_array = $input->get_correct_response($this->getQuestion()->getSession()->get_value_key($input_name, true));
				$correct_answer = $correct_answer_array[$input_name];
				if (!strlen($correct_answer))
				{
					$correct_answer = $this->getQuestion()->getSession()->get_value_key($input->get_teacher_answer());
				}
				$input_size = strlen($correct_answer) * 1.1;
				$input_html_display = '<input type="text" size="' . $input_size . '" id="xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name . '_postvalidation" value="' . $correct_answer . '" disabled="disabled">';
				$result = array();
				$result["value"] = $input_html_display;
				$result["display"] = "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . '<code>' . $correct_answer . '</code>' . $this->format_correct_response($input_name) . "</td></tr></table>";

				return $result;
			}
			if (is_a($input, "stack_numerical_input") OR is_a($input, "stack_singlechar_input") OR is_a($input, "stack_boolean_input") OR is_a($input, "stack_units_input"))
			{
				$input_size = strlen($correct_answer[$input_name]);
				$input_html_display = '<input type="text" style="width:' . $input_size . 'em" id="xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name . '_postvalidation" value="' . $correct_answer[$input_name] . '" disabled="disabled">';

				$result = array();
				$result["value"] = $input_html_display;
				$result["display"] = "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . '<code>' . $correct_answer[$input_name] . '</code>' . $this->format_correct_response($input_name) . "</td></tr></table>";

				return $result;
			}
			if (is_a($input, "stack_matrix_input"))
			{
				//display
				$matrix_input_correct_answer = $input->get_correct_response($this->getQuestion()->getSession()->get_value_key($input_name, true));
				$matrix_input_rows = (int)$input->height;
				$matrix_input_columns = (int)$input->width;

				$correct_matrix = "<table class='xqcas_matrix_validation' style='display:inline'>";
				//Solve https://mantis.ilias.de/view.php?id=23837
				$correct_matrix_display = "<table class='xqcas_matrix_validation' style='display:inline'>";
				for ($i = 0; $i < $matrix_input_rows; $i++)
				{
					$correct_matrix .= "<tr>";
					$correct_matrix_display .= "<tr>";
					for ($j = 0; $j < $matrix_input_columns; $j++)
					{
						$correct_matrix .= "<td class='xqcas_matrix_validation'>";
						$correct_matrix .= '<code>' . $matrix_input_correct_answer[$input_name . "_sub_" . $i . "_" . $j] . '</code>';
						$correct_matrix .= "</td>";
						$correct_matrix_display .= "<td>";
						$correct_matrix_display .= '<input type="text" style="width:' . $input_size = $input->get_parameter("boxWidth") . 'em" id="xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name . '_postvalidation" value="' . $matrix_input_correct_answer[$input_name . "_sub_" . $i . "_" . $j] . '" disabled="disabled">';
						$correct_matrix_display .= "<input";
						$correct_matrix_display .= "</td>";
					}
					$correct_matrix .= "</tr>";
					$correct_matrix_display .= "</td>";
				}
				$correct_matrix .= "</table>";
				$correct_matrix_display .= "</table>";

				$result = array();
				$result["value"] = $correct_matrix_display;
				$result["display"] = "<table class='xqcas_validation'><tr><td class='xqcas_validation'>" . $correct_matrix . $this->format_correct_response($input_name) . "</td></tr></table>";

				return $result;
			}
			if (is_a($input, "stack_checkbox_input"))
			{
				$options = $input->get_choices();
				//Clean in case of not choosing any active
				if ($options[""])
				{
					unset($options[""]);
				}
				$number_of_options = sizeof($options);
				$html = "";
				if ($number_of_options)
				{
					for ($i = 0; $i < $number_of_options; $i++)
					{
						if (array_key_exists($input_name . "_" . ($i + 1), $correct_answer))
						{
							$html .= '<input type="checkbox" name="" value="" disabled="disabled" checked="checked">' . " " . assStackQuestionUtils::_getLatex($options[($i + 1)]) . '<br>';
						} else
						{
							$html .= '<input type="checkbox" name="" value="" disabled="disabled">' . " " . assStackQuestionUtils::_getLatex($options[($i + 1)]) . '<br>';
						}
					}
				}

				$result = array();
				$result["value"] = $html;
				$result["display"] = "";

				return $result;
			}
			if (is_a($input, "stack_radio_input"))
			{
				$options = $input->get_choices();
				//Clean in case of not choosing any active
				if ($options[""])
				{
					unset($options[""]);
				}
				$number_of_options = sizeof($options);
				$html = "";
				if ($number_of_options)
				{
					for ($i = 0; $i < $number_of_options; $i++)
					{
						if ($i + 1 == $correct_answer[$input_name])
						{
							$html .= '<input type="radio" name="" value="" disabled="disabled" checked="checked">' . " " . assStackQuestionUtils::_getLatex($options[($i + 1)]) . '<br>';
						} else
						{
							$html .= '<input type="radio" name="" value="" disabled="disabled">' . " " . assStackQuestionUtils::_getLatex($options[($i + 1)]) . '<br>';
						}
					}
				}

				$result = array();
				$result["value"] = $html;
				$result["display"] = "";

				return $result;
			}
			if (is_a($input, "stack_dropdown_input"))
			{
				$html = "<select>";
				$html .= '<option value="' . $correct_answer[$input_name . "_val"] . '">' . $correct_answer[$input_name . "_val"] . '</option>';
				$html .= "</select>";

				$result["value"] = $html;
				$result["display"] = "";

				return $result;
			}

			if (is_a($input, "stack_equiv_input") OR (is_a($input, "stack_textarea_input")))
			{
				//Display
				$feedback = "<table class='xqcas_validation'>";
				$textarea_html = "";
				$textarea_html .= '<pre>' . $correct_answer[$input_name] . '</pre></br>';

				$feedback .= html_writer::tag('p', "<tr><td class='xqcas_validation'>" . $textarea_html . "</td><td class='xqcas_validation'>" . $this->format_correct_response($input_name)) . "</td></tr>";

				$feedback .= "</table>";

				//Value
				$rows = sizeof(explode(",", $correct_answer[$input_name . "_val"]));
				if (is_array($correct_answer[$input_name]))
				{
					$student_answer_value = $correct_answer[$input_name][$input_name . "_val"];
				} elseif (is_string($correct_answer[$input_name]))
				{
					$student_answer_value = $correct_answer[$input_name];
				}

				$textarea_html = '<textarea rows="' . $rows . '" id="xqcas_' . $this->getQuestion()->getQuestionId() . '_' . $input_name . '_postvalidation" disabled="disabled">' . $student_answer_value . '</textarea>';

				$result = array();
				$result["value"] = $textarea_html;
				$result["display"] = $feedback;

				return $result;
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

}