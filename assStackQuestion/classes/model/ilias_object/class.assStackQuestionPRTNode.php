<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * STACK Question Potential Response Tree Node object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionPRTNode
{

	//Attributes
	private $node_id;
	private $question_id;
	private $prt_name;
	private $node_name;
	private $answer_test;
	private $student_answer;
	private $teacher_answer;
	private $test_options;
	private $quiet;
	private $true_score_mode;
	private $true_score;
	private $true_penalty;
	private $true_next_node;
	private $true_answer_note;
	private $true_feedback;
	private $true_feedback_format;
	private $false_score_mode;
	private $false_score;
	private $false_penalty;
	private $false_next_node;
	private $false_answer_note;
	private $false_feedback;
	private $false_feedback_format;

	function __construct($node_id, $question_id, $prt_name, $node_name, $true_next_node, $false_next_node)
	{
		$this->setNodeId($node_id);
		$this->setQuestionId($question_id);
		$this->setPRTName($prt_name);
		$this->setNodeName($node_name);
		$this->setTrueNextNode($true_next_node);
		$this->setFalseNextNode($false_next_node);
	}

	/*
	 *  GETTERS AND SETTERS
	 */

	public function getNodeId()
	{
		return $this->node_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getPRTName()
	{
		return $this->prt_name;
	}

	public function getNodeName()
	{
		return $this->node_name;
	}

	public function getAnswerTest()
	{
		return $this->answer_test;
	}

	public function getStudentAnswer()
	{
		return $this->student_answer;
	}

	public function getTeacherAnswer()
	{
		return $this->teacher_answer;
	}

	public function getTestOptions()
	{
		return $this->test_options;
	}

	public function getQuiet()
	{
		return $this->quiet;
	}

	public function getTrueScoreMode()
	{
		return $this->true_score_mode;
	}

	public function getTrueScore()
	{
		return $this->true_score;
	}

	public function getTruePenalty()
	{
		return $this->true_penalty;
	}

	public function getTrueNextNode()
	{
		return $this->true_next_node;
	}

	public function getTrueAnswerNote()
	{
		return $this->true_answer_note;
	}

	public function getTrueFeedback()
	{
		return $this->true_feedback;
	}

	public function getTrueFeedbackFormat()
	{
		return $this->true_feedback_format;
	}

	public function getFalseScoreMode()
	{
		return $this->false_score_mode;
	}

	public function getFalseScore()
	{
		return $this->false_score;
	}

	public function getFalsePenalty()
	{
		return $this->false_penalty;
	}

	public function getFalseNextNode()
	{
		return $this->false_next_node;
	}

	public function getFalseAnswerNote()
	{
		return $this->false_answer_note;
	}

	public function getFalseFeedback()
	{
		return $this->false_feedback;
	}

	public function getFalseFeedbackFormat()
	{
		return $this->false_feedback_format;
	}

	public function setNodeId($node_id)
	{
		$this->node_id = $node_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setPRTName($prt_name)
	{
		$this->prt_name = $prt_name;
	}

	public function setNodeName($node_name)
	{
		$this->node_name = $node_name;
	}

	public function setAnswerTest($answer_test)
	{
		$this->answer_test = $answer_test;
	}

	public function setStudentAnswer($student_answer)
	{
		$this->student_answer = $student_answer;
	}

	public function setTeacherAnswer($teacher_answer)
	{
		$this->teacher_answer = $teacher_answer;
	}

	public function setTestOptions($test_options)
	{
		$this->test_options = $test_options;
	}

	public function setQuiet($quiet)
	{
		$this->quiet = $quiet;
	}

	public function setTrueScoreMode($true_score_mode)
	{
		$this->true_score_mode = $true_score_mode;
	}

	public function setTrueScore($true_score)
	{
		$this->true_score = $true_score;
	}

	public function setTruePenalty($true_penalty)
	{
		$this->true_penalty = $true_penalty;
	}

	public function setTrueNextNode($true_next_node)
	{
		$this->true_next_node = $true_next_node;
	}

	public function setTrueAnswerNote($true_answer_note)
	{
		$this->true_answer_note = $true_answer_note;
	}

	public function setTrueFeedback($true_feedback)
	{
		$this->true_feedback = $true_feedback;
	}

	public function setTrueFeedbackFormat($true_feedback_format)
	{
		$this->true_feedback_format = $true_feedback_format;
	}

	public function setFalseScoreMode($false_score_mode)
	{
		$this->false_score_mode = $false_score_mode;
	}

	public function setFalseScore($false_score)
	{
		$this->false_score = $false_score;
	}

	public function setFalsePenalty($false_penalty)
	{
		$this->false_penalty = $false_penalty;
	}

	public function setFalseNextNode($false_next_node)
	{
		$this->false_next_node = $false_next_node;
	}

	public function setFalseAnswerNote($false_answer_note)
	{
		$this->false_answer_note = $false_answer_note;
	}

	public function setFalseFeedback($false_feedback)
	{
		$this->false_feedback = $false_feedback;
	}

	public function setFalseFeedbackFormat($false_feedback_format)
	{
		$this->false_feedback_format = $false_feedback_format;
	}

	/**
	 *
	 * @return boolean
	 */
	public function save()
	{

		$this->checkPRTNode(TRUE);

		if ($this->getNodeId() < 0) {
			return $this->create();
		} else {
			return $this->update();
		}
	}

	public function create()
	{
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

		//Get an ID for this object
		$this->setNodeId((int)$db->nextId('xqcas_prt_nodes'));
		//Insert Object into DB
		$db->insert("xqcas_prt_nodes", array(
			"id" => array("integer", $this->getNodeId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"prt_name" => array("text", $this->getPRTName()),
			"node_name" => array("text", $this->getNodeName()),
			"answer_test" => array("text", $this->getAnswerTest()),
			"sans" => array("text", $this->getStudentAnswer()),
			"tans" => array("text", $this->getTeacherAnswer()),
			"test_options" => array("text", $this->getTestOptions()),
			"quiet" => array("integer", $this->getQuiet()),
			"true_score_mode" => array("text", $this->getTrueScoreMode()),
			"true_score" => array("text", $this->getTrueScore()),
			"true_penalty" => array("text", $this->getTruePenalty()),
			"true_next_node" => array("text", $this->getTrueNextNode()),
			"true_answer_note" => array("text", $this->getTrueAnswerNote()),
			"true_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getTrueFeedback(), 0)),
			"true_feedback_format" => array("integer", $this->getTrueFeedbackFormat()),
			"false_score_mode" => array("text", $this->getFalseScoreMode()),
			"false_score" => array("text", $this->getFalseScore()),
			"false_penalty" => array("text", $this->getFalsePenalty()),
			"false_next_node" => array("text", $this->getFalseNextNode()),
			"false_answer_note" => array("text", $this->getFalseAnswerNote()),
			"false_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getFalseFeedback(), 0)),
			"false_feedback_format" => array("integer", $this->getFalseFeedbackFormat()),
		));
		return true;
	}

	public static function _read($question_id, $prt_name)
	{
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

		//Inputs array
		$PRT_Nodes = array();
		//Select query
		$query = 'SELECT * FROM xqcas_prt_nodes WHERE question_id = '
			. $db->quote($question_id, 'integer') .
			' AND prt_name = ' . $db->quote($prt_name, 'text');
		$res = $db->query($query);

		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//Options object to return in case there are options in DB for this $question_id
			$node = new assStackQuestionPRTNode($row["id"], $question_id, $row["prt_name"], $row["node_name"], $row["true_next_node"], $row["false_next_node"]);
			//Filling object with data from DB
			$node->setAnswerTest($row["answer_test"]);
			$node->setStudentAnswer($row["sans"]);
			$node->setTeacherAnswer($row["tans"]);
			$node->setTestOptions($row["test_options"]);
			$node->setQuiet((int)$row["quiet"]);
			$node->setTrueScore($row["true_score"]);
			$node->setTrueScoreMode($row["true_score_mode"]);
			$node->setTruePenalty($row["true_penalty"]);
			$node->setTrueAnswerNote($row["true_answer_note"]);
			$node->setTrueFeedback(ilRTE::_replaceMediaObjectImageSrc($row["true_feedback"], 1));
			$node->setTrueFeedbackFormat((int)$row["true_feedback_format"]);
			$node->setFalseScore($row["false_score"]);
			$node->setFalseScoreMode($row["false_score_mode"]);
			$node->setFalsePenalty($row["false_penalty"]);
			$node->setFalseAnswerNote($row["false_answer_note"]);
			$node->setFalseFeedback(ilRTE::_replaceMediaObjectImageSrc($row["false_feedback"], 1));
			$node->setFalseFeedbackFormat((int)$row["false_feedback_format"]);

			$PRT_Nodes[$row["node_name"]] = $node;
		}
		return $PRT_Nodes;
	}

	public function update()
	{
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

		$db->replace('xqcas_prt_nodes',
			array(
				"id" => array('integer', $this->getNodeId())),
			array(
				"question_id" => array("integer", $this->getQuestionId()),
				"prt_name" => array("text", $this->getPRTName()),
				"node_name" => array("text", $this->getNodeName()),
				"answer_test" => array("text", $this->getAnswerTest()),
				"sans" => array("text", $this->getStudentAnswer()),
				"tans" => array("text", $this->getTeacherAnswer()),
				"test_options" => array("text", $this->getTestOptions()),
				"quiet" => array("integer", (int)$this->getQuiet()),
				"true_score_mode" => array("text", $this->getTrueScoreMode()),
				"true_score" => array("text", $this->getTrueScore()),
				"true_penalty" => array("text", $this->getTruePenalty()),
				"true_next_node" => array("text", $this->getTrueNextNode()),
				"true_answer_note" => array("text", $this->getTrueAnswerNote()),
				"true_feedback" => array("clob",  ilRTE::_replaceMediaObjectImageSrc($this->getTrueFeedback(), 0)),
				"true_feedback_format" => array("integer", $this->getTrueFeedbackFormat()),
				"false_score_mode" => array("text", $this->getFalseScoreMode()),
				"false_score" => array("text", $this->getFalseScore()),
				"false_penalty" => array("text", $this->getFalsePenalty()),
				"false_next_node" => array("text", $this->getFalseNextNode()),
				"false_answer_note" => array("text", $this->getFalseAnswerNote()),
				"false_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getFalseFeedback(), 0)),
				"false_feedback_format" => array("integer", $this->getFalseFeedbackFormat()),
			)
		);

		return TRUE;
	}

	/**
	 * Write the posted data from the question editing form
	 * @param 	string	$prt_name		current prt name
	 * @param	string	$node_name		current node name
	 * @param	string	$new_prt_name	new prt name
	 * @param	string	$new_node_name	new node name
	 * @param 	string	$a_rte_tags		allowed html tags for RTE fields, e.g. "<em><strong>..."
	 */
	public function writePostData($prt_name, $node_name, $new_prt_name = "", $new_node_name = "", $a_rte_tags = "")
	{
		$prefix = 'prt_' . $prt_name . '_node_' . $node_name;
		
		$this->setAnswerTest($_POST[$prefix . '_answer_test']);
		$this->setStudentAnswer($_POST[$prefix . '_student_answer']);
		$this->setTeacherAnswer($_POST[$prefix . '_teacher_answer']);
		$this->setTestOptions($_POST[$prefix . '_options'] == NULL ? "" : $_POST[$prefix . '_options']);
		$this->setQuiet($_POST[$prefix . '_quiet']);

		$this->setTrueScore(ilUtil::stripSlashes($_POST[$prefix . '_pos_score']) == NULL ? 0 : ilUtil::stripSlashes($_POST[$prefix . '_pos_score']));
		$this->setTrueScoreMode(ilUtil::stripSlashes($_POST[$prefix . '_pos_mod']));
		$this->setTruePenalty(ilUtil::stripSlashes($_POST[$prefix . '_pos_penalty']) == NULL ? 0 : ilUtil::stripSlashes($_POST[$prefix . '_pos_penalty']));
		$this->setTrueNextNode(ilUtil::stripSlashes($_POST[$prefix . '_pos_next']));
		$this->setTrueAnswerNote(ilUtil::stripSlashes($_POST[$prefix . '_pos_answernote'] == NULL ? "" : $_POST[$prefix . '_pos_answernote']));
		$this->setTrueFeedback(ilUtil::stripSlashes($_POST[$prefix . '_pos_specific_feedback'] == NULL ? "" : $_POST[$prefix . '_pos_specific_feedback'], true, $a_rte_tags));

		$this->setFalseScore(ilUtil::stripSlashes($_POST[$prefix . '_neg_score']) == NULL ? 0 : ilUtil::stripSlashes($_POST[$prefix . '_neg_score']));
		$this->setFalseScoreMode(ilUtil::stripSlashes($_POST[$prefix . '_neg_mod']));
		$this->setFalsePenalty(ilUtil::stripSlashes($_POST[$prefix . '_neg_penalty']) == NULL ? 0 : ilUtil::stripSlashes($_POST[$prefix . '_neg_penalty']));
		$this->setFalseNextNode(ilUtil::stripSlashes($_POST[$prefix . '_neg_next']));
		$this->setFalseAnswerNote(ilUtil::stripSlashes($_POST[$prefix . '_neg_answernote'] == NULL ? "" : $_POST[$prefix . '_neg_answernote']));
		$this->setFalseFeedback(ilUtil::stripSlashes($_POST[$prefix . '_neg_specific_feedback'] == NULL ? "" : $_POST[$prefix . '_neg_specific_feedback'], true, $a_rte_tags));


		if ($new_prt_name) {
			//In case of new prt creation
			$this->setPRTName($new_prt_name);
			$this->checkPRTNode(TRUE);
			$this->save();
		} elseif ($new_node_name) {
			$this->setNodeName($new_node_name);
			$this->checkPRTNode(TRUE);
			$this->save();
		} else {
			//In normal case
			$this->setPRTName($prt_name);
		}
	}

	public function delete()
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'DELETE FROM xqcas_prt_nodes WHERE id = ' . $db->quote($this->getNodeId(), 'integer');
		$db->manipulate($query);
	}

	/**
	 * This function checks if each parameter of an assStackQuestionPRTNode object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkPRTNode($solve_problems = TRUE)
	{
		//This method should be called when the assStackQuestionPRTNode obj is created.
		//This attributes cannot be null or void in any case.
		if ($this->getQuestionId() == NULL OR $this->getQuestionId() == "") {
			return false;
		}
		if ($this->getTrueNextNode() == NULL OR $this->getTrueNextNode() == "") {
			$this->setTrueNextNode('-1');
		}
		if ($this->getFalseNextNode() == NULL OR $this->getTrueNextNode() == "") {
			$this->setFalseNextNode('-1');
		}
		//Other Not Null variables:
		if ($this->getAnswerTest() == NULL OR $this->getAnswerTest() == "") {
			if ($solve_problems) {
				$this->setAnswerTest("");
			} else {
				return false;
			}
		}
		if ($this->getStudentAnswer() == NULL OR $this->getStudentAnswer() == "") {
			if ($solve_problems) {
				$this->setStudentAnswer("");
			} else {
				return false;
			}
		}
		if ($this->getTeacherAnswer() == NULL OR $this->getTeacherAnswer() == "") {
			if ($solve_problems) {
				$this->setTeacherAnswer("");
			} else {
				return false;
			}
		}
		if ($this->getTestOptions() == NULL OR $this->getTestOptions() == "") {
			if ($solve_problems) {
				$this->setTestOptions("");
			} else {
				return false;
			}
		}
		if ($this->getTrueScore() == NULL OR $this->getTrueScore() == "") {
			if ($solve_problems) {
				$this->setTrueScore("0");
			} else {
				return false;
			}
		}
		if ($this->getTruePenalty() == NULL OR $this->getTruePenalty() == "") {
			if ($solve_problems) {
				$this->setTruePenalty("0");
			} else {
				return false;
			}
		}
		if ($this->getTrueAnswerNote() == NULL OR $this->getTrueAnswerNote() == " " OR $this->getTrueAnswerNote() == "") {
			if ($this->getPRTName() == 'new_prt' OR $this->getNodeName() == $this->getPRTName() . '_new_node') {
				$this->setTrueAnswerNote("");
			} else {
				$this->setTrueAnswerNote($this->getPRTName() . '-' . $this->getNodeName() . '-T');
			}
		}
		if ($this->getTrueFeedback() == NULL OR $this->getTrueFeedback() == "") {
			if ($solve_problems) {
				$this->setTrueFeedback("");
			} else {
				return false;
			}
		}
		if ($this->getTrueFeedbackFormat() == NULL OR $this->getTrueFeedbackFormat() == "") {
			if ($solve_problems) {
				$this->setTrueFeedbackFormat(1);
			} else {
				return false;
			}
		}
		if ($this->getFalseScore() == NULL OR $this->getFalseScore() == "") {
			if ($solve_problems) {
				$this->setFalseScore("0");
			} else {
				return false;
			}
		}
		if ($this->getFalsePenalty() == NULL OR $this->getFalsePenalty() == "") {
			if ($solve_problems) {
				$this->setFalsePenalty("0");
			} else {
				return false;
			}
		}
		if ($this->getFalseAnswerNote() == NULL OR $this->getFalseAnswerNote() == " " OR $this->getFalseAnswerNote() == "") {
			if ($this->getPRTName() == 'new_prt' OR $this->getNodeName() == $this->getPRTName() . '_new_node') {
				$this->setFalseAnswerNote("");
			} else {
				$this->setFalseAnswerNote($this->getPRTName() . '-' . $this->getNodeName() . '-F');
			}
		}
		if ($this->getFalseFeedback() == NULL OR $this->getFalseFeedback() == "") {
			if ($solve_problems) {
				$this->setFalseFeedback("");
			} else {
				return false;
			}
		}
		if ($this->getFalseFeedbackFormat() == NULL OR $this->getFalseFeedbackFormat() == "") {
			if ($solve_problems) {
				$this->setFalseFeedbackFormat(1);
			} else {
				return false;
			}
		}
		return true;
	}

	public function isComplete()
	{
		if (strlen($this->getStudentAnswer()) AND strlen($this->getTeacherAnswer())) {
				return TRUE;
		}

		return FALSE;
	}

}
