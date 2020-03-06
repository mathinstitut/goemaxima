<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * assStackQuestionTestInput object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionTestInput
{

	private $test_input_id;
	private $question_id;
	private $test_case;
	private $test_input_name;
	private $test_input_value;

	function __construct($test_input_id, $question_id, $test_case)
	{
		$this->setTestInputId($test_input_id);
		$this->setQuestionId($question_id);
		$this->setTestCase($test_case);
	}

	/*
	 *  GETTERS AND SETTERS
	 */

	public function getTestInputId()
	{
		return $this->test_input_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getTestCase()
	{
		return $this->test_case;
	}

	public function getTestInputName()
	{
		return $this->test_input_name;
	}

	public function getTestInputValue()
	{
		return $this->test_input_value;
	}

	public function setTestInputId($test_input_id)
	{
		$this->test_input_id = $test_input_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setTestCase($test_case)
	{
		$this->test_case = $test_case;
	}

	public function setTestInputName($test_input_name)
	{
		$this->test_input_name = $test_input_name;
	}

	public function setTestInputValue($test_input_value)
	{
		$this->test_input_value = $test_input_value;
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
		if ($this->getTestInputId() < 0) {
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
		$this->setTestInputId((int)$db->nextId('xqcas_qtest_inputs'));
		//Insert Object into DB
		$db->insert("xqcas_qtest_inputs", array(
			"id" => array("integer", $this->getTestInputId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"test_case" => array("integer", $this->getTestCase()),
			"input_name" => array("text", $this->getTestInputName()),
			"value" => array("text", $this->getTestInputValue())
		));
		return true;
	}

	public static function _read($question_id, $testcase_name)
	{
		global $DIC;
		$db = $DIC->database();
		//Inputs array
		$tests_input = array();
		//Select query
		$query = 'SELECT * FROM xqcas_qtest_inputs WHERE question_id = '
			. $db->quote($question_id, 'integer') . ' AND test_case = ' . $db->quote($testcase_name, 'integer');
		$res = $db->query($query);

		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//Options object to return in case there are options in DB for this $question_id
			$input = new assStackQuestionTestInput((int)$row["id"], $question_id, (int)$row["test_case"]);
			//Filling object with data from DB
			$input->setTestInputName($row["input_name"]);
			$input->setTestInputValue($row["value"]);
			$tests_input[] = $input;
		}
		return $tests_input;
	}

	public function update()
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'UPDATE xqcas_qtest_inputs SET value="' . $this->getTestInputValue() . '" WHERE id=' . $this->getTestInputId();
		$res = $db->query($query);

		return;
	}

	public function delete()
	{

	}

	/**
	 * This function checks if each parameter of an assStackQuestionTestInput object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkTestInput($solve_problems = TRUE)
	{
		//This method should be called when the assStackQuestionTestInput obj is created.
		//This attributes cannot be null or void in any case.
		if ($this->getQuestionId() === NULL OR $this->getQuestionId() === "") {
			return false;
		}
		if ($this->getTestCase() === NULL OR $this->getTestCase() === "") {
			return false;
		}
		//Other Not Null variables:
		if ($this->getTestInputName() === NULL OR $this->getTestInputName() === "") {
			if ($solve_problems) {
				$this->setTestInputName("");
			} else {
				return false;
			}
		}
		if ($this->getTestInputValue() === NULL OR $this->getTestInputValue() === "") {
			if ($solve_problems) {
				$this->setTestInputValue("");
			} else {
				return false;
			}
		}
		return true;
	}

}
