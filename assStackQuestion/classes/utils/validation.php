<?php

// fim: [debug] optionally set error before initialisation
error_reporting(E_ALL);
ini_set("display_errors", "on");
// fim.

chdir("../../../../../../../../../");

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)

require_once "./include/inc.header.php";
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
//Initialization (load of stack wrapper classes)
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionInitialization.php';

header('Content-type: application/json; charset=utf-8');
echo json_encode(checkUserResponse($_REQUEST['question_id'], $_REQUEST['input_name'], $_REQUEST['input_value']));
exit;

/**
 * Gets the students answer and send it to maxima in order to get the validation.
 * @param string $student_answer
 * @return string the Validation message.
 */
function checkUserResponse($question_id, $input_name, $user_response)
{
	require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/class.assStackQuestion.php';
	require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/class.assStackQuestionStackQuestion.php';

	$ilias_question = new assStackQuestion();
	$ilias_question->loadFromDb($question_id);
	//v1.6+ Randomisation improvements
	$active_id = $_GET['active_id'];
	require_once "./Modules/Test/classes/class.ilObjTest.php";
	$pass = ilObjTest::_getPass($active_id);
	if (is_int($active_id) AND is_int($pass))
	{
		$stack_question = new assStackQuestionStackQuestion($active_id, $pass);
		$stack_question->init($ilias_question, 8);
	} else
	{
		$stack_question = new assStackQuestionStackQuestion();
		$seed = $_SESSION['q_seed_for_preview_' . $_GET['q_id'] . ''];
		$stack_question->init($ilias_question, 8, $seed);
	}

	$stack_input = $stack_question->getInputs($input_name);
	$stack_options = $stack_question->getOptions();
	$teacher_answer = $stack_input->get_teacher_answer();

	if (is_a($stack_input, "stack_equiv_input") OR is_a($stack_input, "stack_textarea_input"))
	{
		$stack_response = $stack_input->maxima_to_response_array("[" . $user_response . "]");
	} elseif (is_a($stack_input, "stack_matrix_input"))
	{

		$input = $stack_question->getInputs($input_name);
		$forbiddenwords = $input->get_parameter('forbidWords', '');
		$array = $input->maxima_to_response_array($user_response);

		$state = $stack_question->getInputState($input_name, $array, $forbiddenwords);

		$result = array('input' => $user_response, 'status' => $state->status, 'message' => $input->render_validation($state, $input_name),);

		return $result['message'];
	} else
	{
		$stack_response = $stack_input->maxima_to_response_array($user_response);
	}

	$status = $stack_input->validate_student_response($stack_response, $stack_options, $teacher_answer, null);

	$result = array('input' => $user_response, 'status' => $status->status, 'message' => $stack_input->render_validation($status, $input_name));

	return $result['message'];
}