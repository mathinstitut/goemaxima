<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question FEEDBACK of question GUI class
 * This class provides a view for the feedback of a specific STACK Question
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 1.6.1$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionFeedbackGUI
{
	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var ilTemplate for showing the preview
	 */
	private $template;

	/**
	 * @var array with the data from assStackQuestionFeedback
	 */
	private $feedback;


	/**
	 * Set all the data needed for call the getFeedbackGUI() method.
	 * @param ilassStackQuestionPlugin $plugin
	 * @param array $feedback_data
	 */
	function __construct(ilassStackQuestionPlugin $plugin, $feedback_data, $specific_feedback = "")
	{

		//Set plugin object
		$this->setPlugin($plugin);

		//Set template for preview
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_question_show_feedback.html'));

		//Set feedback data
		$this->setFeedback($feedback_data);

		if (sizeof($this->getFeedback('prt')) > 1) {
			$this->show_user_response = TRUE;
		} else {
			$this->show_user_response = FALSE;
		}

		//Add MathJax (Ensure MathJax is loaded)
		include_once "./Services/Administration/classes/class.ilSetting.php";
		$mathJaxSetting = new ilSetting("MathJax");
		$this->getTemplate()->addJavaScript($mathJaxSetting->get("path_to_mathjax"));

		if (is_string($specific_feedback)) {
			$this->specific_feedback = $specific_feedback;
		}
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestionGUI and assStackQuestionPreview to get the question feedback HTML.
	 * @return ilTemplate the STACK Question feedback HTML
	 */
	public function getFeedbackGUI($graphical_output = TRUE, $show_points = TRUE, $show_feedback = TRUE, $show_question_text = TRUE, $show_correct_solution = TRUE)
	{
		//Step #1: Set new template
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_question_show_feedback.html'));

		//#2 Fill the template with data from evaluation
		$this->fillTemplate($graphical_output, $show_points, $show_feedback, $show_question_text, $show_correct_solution);

		//#3 Returns the template with filled data
		return $this->getTemplate();
	}

	/**
	 * This method is called from assStackQuestionGUI to get the best solution HTML.
	 * @return ilTemplate the STACK Question best solution HTML
	 */
	public function getBestSolutionGUI($mode = "correct", $graphicalOutput = TRUE, $show_points = TRUE, $show_feedback = TRUE, $show_question_text = TRUE, $show_correct_solution = TRUE)
	{
		//Step #1: Set new template
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_question_best_solution.html'));

		//Step #2 Fill the template with data from evaluation
		$this->fillBestSolutionTemplate($mode);

		//Step #3 Returns the template with filled data
		return $this->getTemplate();
	}

	/*
	 * FILLING TEMPLATE FUNCTIONS
	 */

	/**
	 * This function fills the template for question feedback display
	 */
	private function fillTemplate($graphical_output, $show_points, $show_feedback, $show_question_text, $show_correct_solution)
	{
		//Set block
		$this->getTemplate()->setCurrentBlock('question');
		//General feedback info
		$this->fillGeneralInfo($show_question_text, $show_points, $show_feedback, $show_correct_solution);
		//PRT Specific part
		if (is_array($this->getFeedback('prt'))) {
			foreach ($this->getFeedback('prt') as $prt_name => $prt) {
				$this->fillSpecificPrtFeedback($show_question_text, $prt_name, $prt, $graphical_output, $show_points, $show_feedback, $show_correct_solution);
			}
		}
		//Close block
		$this->getTemplate()->ParseCurrentBlock();
	}

	/**
	 * This function fills the template for best solution display
	 */
	private function fillBestSolutionTemplate($mode = "correct")
	{
		//Fill question text
		$this->getTemplate()->setVariable('QUESTION_TEXT', assStackQuestionUtils::_getLatex($this->getQuestionTextFilledIn($mode)));

		//Fill how to solve
		if ($mode == "correct") {
			$this->getTemplate()->setVariable('HOW_TO_SOLVE', assStackQuestionUtils::_getLatex($this->getQuestionHowToSolve($this->getFeedback('general_feedback'))));
		}


		/*
		//Get model answers array
		$model_answers = array();
		if (is_array($this->getFeedback('prt'))) {
			foreach ($this->getFeedback('prt') as $prt_name => $prt) {
				if (isset($prt['response'])) {
					foreach ($prt['response'] as $input_name => $value) {
						if (!array_key_exists($input_name, $model_answers)) {
							$model_answers[$input_name]['model_answer'] = $value['model_answer'];
							$model_answers[$input_name]['user_response'] = $value['display'];
						}
					}
				}
			}
		}

		//Fill header
		$this->getTemplate()->setVariable('INPUT_NAME_HEADER', $this->getPlugin()->txt('bs_your_solution_was'));
		$this->getTemplate()->setVariable('MODEL_ANSWER_HEADER', $this->getPlugin()->txt('bs_best_solution'));

		//Fill model answer part
		foreach ($model_answers as $input_name => $model_answer) {
			$this->getTemplate()->setCurrentBlock('model_answer');
			$this->getTemplate()->setVariable('INPUT_NAME', ilUtil::insertLatexImages(assStackQuestionUtils::_solveKeyBracketsBug($model_answer['user_response'])));
			$this->getTemplate()->setVariable('MODEL_ANSWER', ilUtil::insertLatexImages('\[ ' . assStackQuestionUtils::_solveKeyBracketsBug($model_answer['model_answer'])) . ' \]');
			$this->getTemplate()->ParseCurrentBlock();
		}*/

	}

	/**
	 * Fill general info for the whole question feedback
	 * Called from fillTemplate()
	 * @param $test_finished
	 */
	private function fillGeneralInfo($show_question_text, $show_points, $show_feedback, $show_correct_solution)
	{
		// question_text
		if ($this->getFeedback('question_text') != '' AND $_GET['activecommand'] != 'directfeedback') {

			//If test is finished use LaTeX
			if ($_GET['cmd'] != 'preview') {
				$this->getTemplate()->setVariable('QUESTION_TEXT_MESSAGE', $this->getPlugin()->txt('message_question_text'));
				$this->getTemplate()->setVariable('QUESTION_TEXT', assStackQuestionUtils::_replacePlaceholders(assStackQuestionUtils::_getLatex($this->getFeedback('question_text'))));
			} else {
				$this->getTemplate()->setVariable('QUESTION_TEXT_MESSAGE', $this->getPlugin()->txt('message_question_text'));
				$this->getTemplate()->setVariable('QUESTION_TEXT', assStackQuestionUtils::_replacePlaceholders(assStackQuestionUtils::_getLatex($this->getFeedback('general_feedback'))));
			}

		} elseif ($this->getFeedback('general_feedback') != '' AND $_GET['activecommand'] == 'directfeedback') {

			//If test is finished use LaTeX
			$this->getTemplate()->setVariable('QUESTION_TEXT_MESSAGE', $this->getPlugin()->txt('message_general_feedback'));
			$this->getTemplate()->setVariable('QUESTION_TEXT', assStackQuestionUtils::_replacePlaceholders(assStackQuestionUtils::_getLatex($this->getFeedback('general_feedback'))));

		}
		//If there are general feedback to be shown
		if ($show_feedback AND $this->getFeedback('general_feedback') != '') {
			//If test is finished use LaTeX
			//$this->getTemplate()->setVariable('GENERAL_FEEDBACK_MESSAGE', $this->getPlugin()->txt('message_general_feedback'));
			//$this->getTemplate()->setVariable('GENERAL_FEEDBACK', assStackQuestionUtils::_getLatex($this->getFeedback('general_feedback')));
		} else {
			//Show message for no general feedback.
			//v1.6.1 Not use general_feedback
			//$this->getTemplate()->setVariable('GENERAL_FEEDBACK', $this->getPlugin()->txt('message_no_how_to_solve_in_this_question'));
		}

		/*
		 * v1.6.1 Not use question_note
		if ($this->getShowCorrectSolution() AND $this->getFeedback('question_note')) {
			$this->getTemplate()->setVariable('BEST_SOLUTION_MESSAGE', $this->getPlugin()->txt('message_best_solution'));
			$this->getTemplate()->setVariable('BEST_SOLUTION', assStackQuestionUtils::_getLatex($this->getFeedback('question_note')));
		}
		*/

		//Fill total points information#
		/*
		if ($show_points AND $_GET['activecommand'] != 'directfeedback') {
			$this->getTemplate()->setVariable('TOTAL_POINTS_MESSAGE', $this->getPlugin()->txt('message_total_points'));
			$this->getTemplate()->setVariable('TOTAL_POINTS', $this->getFeedback('points'));

		}*/
	}

	/**
	 * Fill Specific information per each prt of the question
	 * Called from fillTemplate()
	 * @param string $prt_name
	 * @param array $prt
	 */
	private function fillSpecificPrtFeedback($show_question_text, $prt_name, $prt, $graphical_output, $show_points, $show_feedback, $show_correct_solution)
	{
		//Set block
		$this->getTemplate()->setCurrentBlock('question_part');
		$this->getTemplate()->setVariable('PRT_NAME', $prt_name);
		//Fill the user response part
		if (($this->show_user_response AND $_GET['activecommand'] == 'directfeedback') OR $_GET['activecommand'] != 'directfeedback') {
			$this->fillUserResponse($prt['response'], $show_correct_solution);
		}

		//Set block again to continue filling the question part
		$this->getTemplate()->setCurrentBlock('question_part');
		//Points reached in this prt
		if (($this->show_user_response AND $_GET['activecommand'] == 'directfeedback') OR $_GET['activecommand'] != 'directfeedback') {
			if (!is_null($prt['points']) AND $show_points) {
				$this->getTemplate()->setVariable('POINTS_MESSAGE', $this->getPlugin()->txt('message_points'));
				$this->getTemplate()->setVariable('POINTS', $prt['points']);
			}
		}

		//Errors
		if ($prt['errors'] AND $show_feedback) {
			$this->getTemplate()->setVariable('ERROR_MESSAGE', $this->getPlugin()->txt('message_error_part'));
			$this->getTemplate()->setVariable('ERROR', assStackQuestionUtils::_getLatex($prt['errors']));
		}
		//Specific feedback given for this prt
		if ($prt['feedback']) {
			//$this->getTemplate()->setVariable('FEEDBACK_MESSAGE', $this->getPlugin()->txt('message_feedback_solution_part'));
			$this->getTemplate()->setVariable('PART_FEEDBACK', assStackQuestionUtils::_getLatex($prt['feedback']));
		}
		//answer note given for this prt
		/* Hidden in feedback.
		if ($prt['answernote'] AND $rbacsystem->checkAccess('write', $_GET['ref_id'])) {
			$this->getTemplate()->setVariable('ANSWERNOTE_MESSAGE', $this->getPlugin()->txt('message_answernote_part'));
			$this->getTemplate()->setVariable('ANSWERNOTE', $prt['answernote']);
		}*/

		//Fill color for the feedback status of this input.
		if ($graphical_output AND $_GET['cmd'] != 'outUserListOfAnswerPasses') {
			//Status message
			if (is_array($prt['status'])) {

				$this->getTemplate()->setVariable('FEEDBACK_STATUS', $prt['status']['message']);
			}
			$this->getTemplate()->setVariable('FEEDBACK_STATUS_COLOUR', $this->getColor($prt['status']['value']));
		}
		//Close block
		$this->getTemplate()->ParseCurrentBlock();
	}


	/**
	 * Fills the user response data in feedback GUI
	 * Called from fillSpecificPrtFeedback()
	 * @param array $response_data
	 */
	private function fillUserResponse($response_data, $show_correct_solution)
	{
		$this->getTemplate()->setVariable('USER_RESPONSE_CONTAINER_MESSAGE', $this->getPlugin()->txt('message_user_response_container'));
		//For each input evaluated in current PRT
		foreach ($response_data as $input_name => $response) {
			//Set block
			$this->getTemplate()->setCurrentBlock('user_response_part');
			//If there is a model answer to show
			if (isset($response['model_answer'])) {
				//User response
				$this->getTemplate()->setVariable('USER_RESPONSE_MESSAGE', $this->getPlugin()->txt('message_user_solution_part'));
				$this->getTemplate()->setVariable('USER_RESPONSE', $response['display']);
				//Teacher solution
				//TODO this may not work in all configurations detetmine how to call the system delimiters for LaTeX
				if ($show_correct_solution AND $_GET['activecommand'] != 'directfeedback') {
					//$this->getTemplate()->setVariable('TEACHER_ANSWER_MESSAGE', $this->getPlugin()->txt('message_best_solution'));
					//$this->getTemplate()->setVariable('TEACHER_ANSWER', ilUtil::insertLatexImages('\[ ' . assStackQuestionUtils::_solveKeyBracketsBug($response['model_answer'])) . ' \]');
				}
			}
			//Close block
			$this->getTemplate()->ParseCurrentBlock();
		}
	}

	/**
	 * Takes the right color for the feedback status field in question Feedback.
	 * @param int $status
	 * @return string
	 */
	private function getColor($status)
	{
		switch ($status) {
			case 1:
				return "#b5eeac";
			case 0:
				return "#FFF6CE";
			case -1:
				return "#FFCCCC";
			default:
				return "";
		}
	}

	public function getQuestionTextFilledIn($mode = "correct")
	{
		$question_text = $this->getFeedback('question_text');
		$specific_feedback = $this->specific_feedback;
		//$question_text = preg_replace('/\[\[validation:(.*?)\]\]/', "", $question_text);
		if (is_array($this->getFeedback('prt'))) {
			foreach ($this->getFeedback('prt') as $prt_name => $prt) {
				if(is_array($prt['response'])){
					foreach ($prt['response'] as $input_name => $input) {
						if ($input['model_answer'] != "" AND $mode == "correct") {
							$question_text = str_replace("[[input:" . $input_name . "]]", $input['model_answer'], $question_text);
							$question_text = str_replace("[[validation:" . $input_name . "]]", $input['model_answer_display'], $question_text);
						} elseif ($input['model_answer'] != "" AND $mode == "user") {
							$question_text = str_replace("[[input:" . $input_name . "]]", $this->getFilledInputUser($input['display']), $question_text);
							$question_text = str_replace("[[feedback:" . $prt_name . "]]", $this->replacementForPRTPlaceholders($prt, $prt_name, $input), $question_text);
							$specific_feedback = str_replace("[[feedback:" . $prt_name . "]]", $this->replacementForPRTPlaceholders($prt, $prt_name, $input), $specific_feedback);
						} elseif ($mode == "user") {
							$question_text = str_replace("[[input:" . $input_name . "]]", $this->getPlugin()->txt("no_model_solution_for_this_input"), $question_text);
							$question_text = str_replace("[[feedback:" . $prt_name . "]]", $this->replacementForPRTPlaceholders($prt, $prt_name, $input), $question_text);
						} elseif ($mode == "correct") {
							$question_text = str_replace("[[input:" . $input_name . "]]", $this->getPlugin()->txt("no_model_solution_for_this_input"), $question_text);
							$question_text = str_replace("[[feedback:" . $prt_name . "]]", "", $question_text);
						}
					}
				}
			}
		}

		if ($mode == "correct") {
			$string = "";
			//feedback
			$string .= '<div class="alert alert-warning" role="alert">';
			//Generic feedback
			$string .= $prt['status']['message'];
			//$string .= '<br>';
			//Specific feedback
			$string .= $prt['feedback'];
			$string .= $prt['errors'];
			$string .= '</div>';
			$deco_question_text = $string;
		} elseif ($mode == "user") {
			$deco_question_text = $question_text;
		}

		return $deco_question_text;
	}

	/**
	 * Replace Feedback placeholders by feedback in case it is needed
	 * @param $prt
	 * @param $prt_name
	 * @param $in_test
	 */
	private function replacementForPRTPlaceholders($prt, $prt_name, $input)
	{
		$string = "";
		//feedback
		$string .= '<div class="alert alert-warning" role="alert">';
		//Generic feedback
		$string .= $prt['status']['message'];
		//$string .= '<br>';
		//Specific feedback
		$string .= $prt['feedback'];
		$string .= $prt['errors'];
		$string .= '</div>';
		return $string;
	}

	private function getFilledInputUser($value)
	{
		return $value;
	}


	private function getQuestionHowToSolve($text)
	{
		$deco_how_to_solve = "";

		if ($text) {
			$deco_how_to_solve = '<div class="alert alert-warning" role="alert">' . $text;
			$deco_how_to_solve .= '</div>';
		}

		return $deco_how_to_solve;
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
	private function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param ilTemplate $template
	 */
	private function setTemplate(ilTemplate $template)
	{
		$this->template = $template;
	}

	/**
	 * @return ilTemplate
	 */
	private function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param array $feedback_data
	 */
	private function setFeedback($feedback_data)
	{
		$this->feedback = $feedback_data;
	}

	/**
	 * This method can return the whole feedback info, a general parameters or an specific parameter of an PRT.
	 * @return array OR string
	 */
	public function getFeedback($selector = '', $prt_name = '')
	{
		if ($selector AND $prt_name) {
			//For selection of specific data within an prt
			return $this->feedback['prt'][$prt_name][$selector];
		} elseif ($selector) {
			//For selection of specific data non related to an input.
			if (isset($this->feedback[$selector])) {
				return $this->feedback[$selector];
			} else {
				return "";
			}
		} else {
			return $this->feedback;
		}
	}

	public function getUserAnswersFromFeedback()
	{
		$user_answers = array();
		if (is_array($this->getFeedback('prt'))) {
			foreach ($this->getFeedback('prt') as $prt_name => $prt) {
				if (isset($prt['response'])) {
					foreach ($prt['response'] as $input_name => $value) {
						if (!array_key_exists($input_name, $user_answers)) {
							$user_answers[$input_name] = $value['value'];
						}
					}
				}
			}
		}

		return $user_answers;
	}
}