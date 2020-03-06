<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * STACK Question deployed seed object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jestus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionDeployedSeed
{
	/**
	 * @var
	 */
	private $seed_id;
	/**
	 * @var
	 */
	private $question_id;
	/**
	 * @var
	 */
	private $seed;

	/**
	 * @var string Only used in the deployed seed management
	 */
	private $question_note;


	function __construct($seed_id = '', $question_id, $seed)
	{
		$this->setSeedId($seed_id);
		$this->setQuestionId($question_id);
		if ($seed > 0) {
			$this->setSeed($seed);
		}
	}

	public function getSeedId()
	{
		return $this->seed_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getSeed()
	{
		return $this->seed;
	}

	public function setSeedId($seed_id)
	{
		$this->seed_id = $seed_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setSeed($seed)
	{
		$this->seed = $seed;
	}

	/**
	 * @param string $question_note
	 */
	public function setQuestionNote($question_note)
	{
		$this->question_note = $question_note;
	}

	/**
	 * @return string
	 */
	public function getQuestionNote()
	{
		return $this->question_note;
	}



	/*
	 * CRUD OPERATIONS
	 */

	/**
	 * Save function
	 * @return boolean
	 */
	public function save()
	{
		if (is_int($this->getSeed()) AND $this->getSeed() > 0) {
			if ((int)$this->getSeedId() <= 0) {
				return $this->create();
			} else {
				return TRUE;
			}
		} else {
			return FALSE;
		}
	}

	/**
	 * Creation function
	 * @return bool
	 */
	private function create()
	{
		global $DIC;
		$db = $DIC->database();

		//Get an ID for this object
		$this->setSeedId((int)$db->nextId('xqcas_deployed_seeds'));
		//Insert Object into DB
		$db->insert("xqcas_deployed_seeds", array(
			"id" => array("integer", $this->getSeedId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"seed" => array("integer", $this->getSeed())
		));
		return TRUE;
	}

	/**
	 * READ ALL SEED FROM A QUESTION
	 * @param integer $question_id
	 * @return array
	 */
	public static function _read($question_id)
	{
		global $DIC;
		$db = $DIC->database();
		//Inputs array
		$seeds = array();
		//Select query
		$query = 'SELECT * FROM xqcas_deployed_seeds WHERE question_id = ' . $db->quote($question_id, 'integer');
		$res = $db->query($query);

		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//Options object to return in case there are options in DB for this $question_id
			$seed = new assStackQuestionDeployedSeed((int)$row["id"], (int)$question_id, (int)$row["seed"]);
			$seeds[] = $seed;
		}
		return $seeds;
	}

	public function delete()
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'DELETE FROM xqcas_deployed_seeds WHERE id = ' . $db->quote($this->getSeedId(), 'integer');
		$db->manipulate($query);
	}

	/**
	 * This function checks if each parameter of an assStackQuestionDeployedSeed object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkDeployedSeed($solve_problems = TRUE)
	{
		//This method should be called when the options obj is created.
		if ($this->getQuestionId() == NULL OR $this->getQuestionId() == "") {
			return false;
		}
		//Not Null variables:
		if ($this->getSeed() == NULL OR $this->getSeed() == "") {
			if ($solve_problems) {
				$this->setSeed(1);
			} else {
				return false;
			}
		}
		return true;
	}

}
