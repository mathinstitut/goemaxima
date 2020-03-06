<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question PREVIEW of question
 * This class provides a preview for a specifiSTACK Questionon when not in a test
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionPreview
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * assStackQuestion object
	 * @var assStackQuestion
	 */
	private $question;

	/**
	 *
	 * @var array
	 */
	private $user_response;


	/**
	 * Sets all information needed for question preview,
	 * Question preview is the combination of question display and question feedback in case of evaluation is activated.
	 * @param ilassStackQuestionPlugin $plugin
	 * @param assStackQuestion $question
	 * @param array $user_response
	 */
	public function __construct(ilassStackQuestionPlugin $plugin, assStackQuestion $question, $seed = -1, $solutions = array(), $test_mode = FALSE)
	{
		//Set plugin object
		$this->setPlugin($plugin);
		//Set question object to be displayed
		$this->setQuestion($question);
		//Set user solutions

		$this->setUserResponse($solutions);
		//v1.6+ New seed management in preview
		if ($seed < 0) {
			$seed = $this->getSeed();
		}

		//Set grade of conversion to stack via the user response
		if (assStackQuestionUtils::_isArrayEmpty($this->getUserResponse())) {
			//NO USER RESPONSE, MINIMUM CONVERSION
			//Create STACK Question object if doesn't exists
			if (!is_a($question->getStackQuestion(), 'assStackQuestionStackQuestion')) {
				$this->getPlugin()->includeClass("model/class.assStackQuestionStackQuestion.php");
				$this->getQuestion()->setStackQuestion(new assStackQuestionStackQuestion());
				$this->getQuestion()->getStackQuestion()->init($this->getQuestion(), '', $seed);
			}
		} else {
			//THERE WAS USER RESPONSE, EVALUATION REQUIRED#
			//Create STACK Question object if doesn't exists
			if (!is_a($question->getStackQuestion(), 'assStackQuestionStackQuestion')) {
				$this->getPlugin()->includeClass("model/class.assStackQuestionStackQuestion.php");
				$this->getQuestion()->setStackQuestion(new assStackQuestionStackQuestion());
				//(v1.6+) Maintain the seed as the current one.
				$this->getQuestion()->getStackQuestion()->init($this->getQuestion(), '', $seed);
			}
		}

	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestionGUI to get the question Preview.
	 * @return array STACK Questiontion preview data
	 */
	public function getQuestionPreviewData($test_mode = FALSE, $active_id = NULL, $pass = NULL)
	{
		//Step #1: Get evaluation form and evaluate question if needed.
		if (!assStackQuestionUtils::_isArrayEmpty($this->getUserResponse())) {
			$evaluated_question = $this->getEvaluationForPreview();
		} else {
			//Step #2: Prepare Question display data
			$display_object = $this->getDisplayDataForPreview();
			return $this->preparePreviewData($display_object);
		}

		//Step #2: Calculate points
		$evaluated_question->calculatePoints($test_mode, $active_id, $pass, $this->question);

		//Step #3: Create feedback object
		$feedback_data = $this->getFeedbackForPreview($evaluated_question);

		//Step #4: Prepare Question display data
		$display_object = $this->getDisplayDataForPreview($feedback_data);


		//Step 5: Return preview data
		return $this->preparePreviewData($display_object, $feedback_data);
	}

	/**
	 * Calls getQuestionDisplay in assStackQuestionDisplay to get the display of the current question
	 * Set the HTML result as a local variable in order to use it when filling preview template.
	 * @return array the question display data
	 */
	private function getDisplayDataForPreview($feedback_data = NULL)
	{
		//In assStackQuestionDisplay the User response should be stored with the "value" format for assStackQuestionUtils::_getUserResponse.
		//Change style from "full" to "value".
		//$value_format_user_response = assStackQuestionUtils::_changeUserResponseStyle($this->getUserResponse(), $this->getQuestion()->getId(), $this->getQuestion()->getStackQuestion()->getInputs(), 'reduced_to_value');

		$this->getPlugin()->includeClass("model/question_display/class.assStackQuestionDisplay.php");
		$question_display_object = new assStackQuestionDisplay($this->getPlugin(), $this->getQuestion()->getStackQuestion(), $this->getUserResponse(), $feedback_data);
		return $question_display_object->getQuestionDisplayData("");
	}

	/**
	 * Calls getQuestionEvaluation in assStackQuestionEvaluation to get the evaluation of the current question
	 * ONLY WHEN THERE ARE ANY USER RESPONSE TO THE CURRENT QUESTION.
	 * Set the HTML result as a local variable in order to use it when filling preview template.
	 * @return assStackQuestionStackQuestion
	 */
	private function getEvaluationForPreview()
	{
		//In assStackQuestionEvaluation the User response should be stored with the "reduced" format for assStackQuestionUtils::_getUserResponse.
		//Evaluation process
		$this->getPlugin()->includeClass("model/question_evaluation/class.assStackQuestionEvaluation.php");
		$question_evaluation_object = new assStackQuestionEvaluation($this->getPlugin(), $this->getQuestion()->getStackQuestion(), $this->getUserResponse());
		return $question_evaluation_object->evaluateQuestion();
	}

	private function getFeedbackForPreview($evaluated_question, $test_mode = FALSE)
	{
		$this->getPlugin()->includeClass('model/question_evaluation/class.assStackQuestionFeedback.php');
		$feedback_object = new assStackQuestionFeedback($this->getPlugin(), $evaluated_question);
		$feedback_data = $feedback_object->getFeedback();

		return $feedback_data;
	}

	/**
	 * Fill preview template
	 * @return array
	 */
	private function preparePreviewData($display_data, $feedback_data = '')
	{
		$preview_data = array();
		//Fill question displayF
		$preview_data['question_display'] = $display_data;
		if (is_array($this->getUserResponse())) {
			//Fill fee1dback display
			$preview_data['question_feedback'] = $feedback_data;
		} else {
			$preview_data['question_feedback'] = FALSE;
		}

		return $preview_data;
	}

	private function getSeed()
	{
		if (isset($_POST['requires_evaluation'])) {

			return $_SESSION['q_seed_for_preview_' . $this->getQuestion()->getId()];
		} else {

			return -1;
		}
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
	 * @return assStackQuestion
	 */
	public function getQuestion()
	{
		return $this->question;
	}

	/**
	 * @return array
	 */
	public function getUserResponse()
	{
		return $this->user_response;
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
	public function setQuestion(assStackQuestion $question)
	{
		$this->question = $question;
	}

	/**
	 * @param $user_response
	 */
	public function setUserResponse($user_response)
	{
		$this->user_response = $user_response;
	}

}
