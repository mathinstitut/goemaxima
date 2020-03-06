<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * assStackQuestionTestExpected object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionTestExpected
{

	private $test_expected_id;
	private $question_id;
	private $test_case;
	private $test_prt_name;
	private $expected_score;
	private $expected_penalty;
	private $expected_answer_note;

	function __construct($test_expected_id, $question_id, $test_case, $test_prt_name)
	{
		$this->setTestExpectedId($test_expected_id);
		$this->setQuestionId($question_id);
		$this->setTestCase($test_case);
		$this->setTestPRTName($test_prt_name);
	}

	/*
	 *  GETTERS AND SETTERS
	 */

	public function getTestExpectedId()
	{
		return $this->test_expected_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getTestCase()
	{
		return $this->test_case;
	}

	public function getTestPRTName()
	{
		return $this->test_prt_name;
	}

	public function getExpectedScore()
	{
		return $this->expected_score;
	}

	public function getExpectedPenalty()
	{
		return $this->expected_penalty;
	}

	public function getExpectedAnswerNote()
	{
		return $this->expected_answer_note;
	}

	public function setTestExpectedId($test_expected_id)
	{
		$this->test_expected_id = $test_expected_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setTestCase($test_case)
	{
		$this->test_case = $test_case;
	}

	public function setTestPRTName($test_prt_name)
	{
		$this->test_prt_name = $test_prt_name;
	}

	public function setExpectedScore($expected_score)
	{
		$this->expected_score = $expected_score;
	}

	public function setExpectedPenalty($expected_penalty)
	{
		$this->expected_penalty = $expected_penalty;
	}

	public function setExpectedAnswerNote($expected_answer_note)
	{
		$this->expected_answer_note = $expected_answer_note;
	}

	/*
	 * CRUD OPERATIONS
	 */

	/**
	 *
	 * @return boolean
	 */
	public function save()
	{
		if ($this->getTestExpectedId() < 0) {
			return $this->create();
		} else {
			return $this->update();
		}
	}

	public function create()
	{
		global $DIC;
		$db = $DIC->database();

		//Get an ID for this object
		$this->setTestExpectedId((int)$db->nextId('xqcas_qtest_expected'));
		//Insert Object into DB
		$db->insert("xqcas_qtest_expected", array(
			"id" => array("integer", $this->getTestExpectedId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"test_case" => array("integer", $this->getTestCase()),
			"prt_name" => array("text", $this->getTestPRTName()),
			"expected_score" => array("text", $this->getExpectedScore()),
			"expected_penalty" => array("text", $this->getExpectedPenalty()),
			"expected_answer_note" => array("text", $this->getExpectedAnswerNote())
		));
		return true;
	}

	public static function _read($question_id, $testcase_name)
	{
		global $DIC;
		$db = $DIC->database();
		//Inputs array
		$tests_expected = array();
		//Select query
		$query = 'SELECT * FROM xqcas_qtest_expected WHERE question_id = '
			. $db->quote($question_id, 'integer') . ' AND test_case = ' . $db->quote($testcase_name, 'integer');
		$res = $db->query($query);

		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//Options object to return in case there are options in DB for this $question_id
			$expected = new assStackQuestionTestExpected((int)$row["id"], $question_id, $row["test_case"], $row["prt_name"]);
			//Filling object with data from DB
			$expected->setExpectedAnswerNote($row["expected_answer_note"]);
			$expected->setExpectedScore($row["expected_score"]);
			$expected->setExpectedPenalty($row["expected_penalty"]);
			$tests_expected[] = $expected;
		}
		return $tests_expected;
	}

	public function update()
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'UPDATE xqcas_qtest_expected SET expected_score="' . $this->getExpectedScore()
			. '", expected_penalty="' . $this->getExpectedPenalty() . '", expected_answer_note="' . $this->getExpectedAnswerNote()
			. '"  WHERE id=' . $this->getTestExpectedId();
		$res = $db->query($query);

		return;
	}

	public function delete()
	{

	}

	/**
	 * This function checks if each parameter of an assStackQuestionTestExpected object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkTestExpected($solve_problems = TRUE)
	{
		//This method should be called when the assStackQuestionTestExpected obj is created.
		//This attributes cannot be null or void in any case.
		if ($this->getQuestionId() === NULL OR $this->getQuestionId() === "") {
			return false;
		}
		if ($this->getTestCase() === NULL OR $this->getTestCase() === "") {
			return false;
		}
		if ($this->getTestPRTName() === NULL OR $this->getTestPRTName() === "") {
			return false;
		}
		//Other Not Null variables:
		if ($this->getExpectedAnswerNote() == NULL OR $this->getExpectedAnswerNote() == "") {
			if ($solve_problems) {
				$this->setExpectedAnswerNote("");
			} else {
				return false;
			}
		}
		return true;
	}

}
