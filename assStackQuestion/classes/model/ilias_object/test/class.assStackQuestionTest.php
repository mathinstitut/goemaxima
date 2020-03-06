<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * assStackQuestionTest object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionTest
{

	private $test_id;
	private $question_id;
	private $test_case;
	private $test_inputs = array();
	private $test_expected = array();
	private $number_of_tests;

	function __construct($test_id, $question_id, $test_case)
	{
		$this->setTestId($test_id);
		$this->setQuestionId($question_id);
		$this->setTestCase($test_case);
	}

	/*
	 *  GETTERS AND SETTERS
	 */

	public function getTestId()
	{
		return $this->test_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getTestCase()
	{
		return $this->test_case;
	}

	public function getTestInputs()
	{
		return $this->test_inputs;
	}

	public function getTestExpected($index = '')
	{
		if (is_int($index)) {
			return $this->test_expected[$index];
		} else {
			return $this->test_expected;
		}
	}

	public function getNumberOfTests()
	{
		return $this->number_of_tests;
	}

	public function setTestId($test_id)
	{
		$this->test_id = $test_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setTestCase($test_case)
	{
		$this->test_case = $test_case;
	}

	public function setTestInputs($test_inputs)
	{
		$this->test_inputs = $test_inputs;
	}

	public function setTestExpected($test_expected)
	{
		$this->test_expected = $test_expected;
	}

	public function setNumberOfTests($number_of_tests)
	{
		$this->number_of_tests = $number_of_tests;
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
		if ($this->getTestId() < 0) {
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
		$this->setTestId((int)$db->nextId('xqcas_qtests'));
		//Insert Object into DB
		$db->insert("xqcas_qtests", array(
			"id" => array("integer", $this->getTestId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"test_case" => array("integer", $this->getTestCase())
		));
		return true;
	}

	public static function _read($question_id, $test_case = '')
	{
		global $DIC;
		$db = $DIC->database();
		//Inputs array
		$tests = array();
		//Select query
		$query = 'SELECT * FROM xqcas_qtests WHERE question_id = '
			. $db->quote($question_id, 'integer');
		if ($test_case) {
			$query .= ' AND test_case = ' . $test_case;
		}
		$res = $db->query($query);

		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/ilias_object/test/class.assStackQuestionTestInput.php';
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/ilias_object/test/class.assStackQuestionTestExpected.php';
		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//Test object to return in case there are options in DB for this $question_id
			$test = new assStackQuestionTest((int)$row["id"], $question_id, (int)$row["test_case"]);
			//Reading test data
			$test->setTestInputs(assStackQuestionTestInput::_read($question_id, $test->getTestCase()));
			$test->setTestExpected(assStackQuestionTestExpected::_read($question_id, $test->getTestCase()));
			$test->setNumberOfTests(sizeof($test->getTestInputs()));
			$tests[$test->getTestCase()] = $test;
		}

		return $tests;
	}

	public function update()
	{

	}

	public function delete($question_id, $testcase_name)
	{
		global $DIC;
		$db = $DIC->database();

		$test = $db->manipulateF("DELETE FROM xqcas_qtests WHERE question_id = %s AND test_case = %s",
			array('integer','integer'),
			array($question_id, $testcase_name)
		);

		$inputs = $db->manipulateF("DELETE FROM xqcas_qtest_inputs WHERE question_id = %s AND test_case = %s",
			array('integer','integer'),
			array($question_id, $testcase_name)
		);

		$expected = $db->manipulateF("DELETE FROM xqcas_qtest_expected WHERE question_id = %s AND test_case = %s",
			array('integer','integer'),
			array($question_id, $testcase_name)
		);
	}

	/**
	 * This function checks if each parameter of an assStackQuestionTest object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkTest($solve_problems = TRUE)
	{
		//This method should be called when the assStackQuestionTest obj is created.
		//This attributes cannot be null or void in any case.
		if ($this->getQuestionId() === NULL OR $this->getQuestionId() === "") {
			return false;
		}
		if ($this->getTestCase() === NULL OR $this->getTestCase() === "") {
			return false;
		}
		//Arrays filled in:
		if (sizeof($this->getTestInputs()) AND sizeof($this->getTestExpected())) {
			return true;
		}
	}

	/*
	public function conversion()
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionStackFactory.php';
		$stack_factory = new assStackQuestionStackFactory();
		return $stack_factory->get('unit_test', self::_read($this->getQuestionId(), $this->getTestCase()));
	}
	*/

	/**
	 * This function transform the input info to the required structure for evaluation
	 * @return array
	 */
	public function getInputsForUnitTest()
	{
		$inputs = array();
		foreach ($this->getTestInputs() as $input) {
			$inputs[$input->getTestInputName()] = $input->getTestInputValue();
		}
		return $inputs;
	}


}
