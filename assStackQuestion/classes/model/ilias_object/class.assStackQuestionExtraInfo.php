<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE 
 */

/**
 * STACK Question specific variables
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jestus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionExtraInfo
{

    private $specific_id;
    private $question_id;
    private $how_to_solve;
	private $penalty;
	private $hidden;

    function __construct($specific_id, $question_id)
    {
        $this->setSpecificId($specific_id);
        $this->setQuestionId($question_id);
    }

	/**
	 * @param mixed $how_to_solve
	 */
	public function setHowToSolve($how_to_solve)
	{
		$this->how_to_solve = $how_to_solve;
	}

	/**
	 * @return mixed
	 */
	public function getHowToSolve()
	{
		return $this->how_to_solve;
	}

	/**
	 * @param mixed $penalty
	 */
	public function setPenalty($penalty)
	{
		$this->penalty = $penalty;
	}

	/**
	 * @return mixed
	 */
	public function getPenalty()
	{
		return $this->penalty;
	}

	/**
	 * @param mixed $question_id
	 */
	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	/**
	 * @return mixed
	 */
	public function getQuestionId()
	{
		return $this->question_id;
	}

	/**
	 * @param mixed $specific_id
	 */
	public function setSpecificId($specific_id)
	{
		$this->specific_id = $specific_id;
	}

	/**
	 * @return mixed
	 */
	public function getSpecificId()
	{
		return $this->specific_id;
	}

	/**
	 * @param mixed $hidden
	 */
	public function setHidden($hidden)
	{
		$this->hidden = $hidden;
	}

	/**
	 * @return mixed
	 */
	public function getHidden()
	{
		return $this->hidden;
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
        if($this->getSpecificId() < 0)
        {
            return $this->create();
        } else
        {
            return $this->update();
        }
    }

    public function create()
    {
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

		//Get an ID for this object
        $this->setSpecificId((int) $db->nextId('xqcas_extra_info'));
        //Insert Object into DB
        $db->insert("xqcas_extra_info", array(
            "id" => array("integer", $this->getSpecificId()),
            "question_id" => array("integer", $this->getQuestionId()),
            "general_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getHowToSolve(), 0)),
			"penalty" => array("text", $this->getPenalty()),
			"hidden" => array("integer", $this->getHidden())
        ));
        return true;
    }

    /**
     * READ ALL SEED FROM A QUESTION
     * @param integer $question_id
     * @return assStackQuestionExtraInfo
     */
    public static function _read($question_id)
    {
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

        //Inputs array
        $extra_info = array();
        //Select query
        $query = 'SELECT * FROM xqcas_extra_info WHERE question_id = '
                . $db->quote($question_id, 'integer');
        $res = $db->query($query);

        //If there is a result returns object, otherwise returns false.
        while ($row = $db->fetchAssoc($res))
        {
            //Options object to return in case there are options in DB for this $question_id
            $specific = new assStackQuestionExtraInfo((int) $row["id"], (int) $question_id);
            $specific->setHowToSolve(ilRTE::_replaceMediaObjectImageSrc($row["general_feedback"], 1));
			$specific->setPenalty($row["penalty"]);
			$specific->setHidden($row["hidden"]);
            $extra_info = $specific;
        }
        return $extra_info;
    }

    public function update()
    {
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

        $db->replace('xqcas_extra_info',
            array(
                "id" => array('integer', $this->getSpecificId())),
            array(
                "question_id" => array("integer", $this->getQuestionId()),
				"general_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getHowToSolve(), 0)),
                "penalty" => array("text", $this->getPenalty()),
                "hidden" => array("integer", $this->getHidden())
            )
        );

        return TRUE;
    }

    public function delete()
    {
        
    }

	/**
	 * Write the posted data from the question editing form
	 * @param string $a_rte_tags	allowed html tags for RTE fields, e.g. "<em><strong>..."
	 */
	public function writePostData($a_rte_tags = "")
    {
        $this->setHowToSolve(ilUtil::stripSlashes($_POST['options_how_to_solve'], true, $a_rte_tags));
    }

    /**
     * This function checks if each parameter of an assStackQuestionExtraInfo object is properly set.
     * If $solve_problems is true, sets the atribute with an empty or default value.
     * @param boolean $solve_problems
     * @return boolean
     */
    public function checkExtraInfo($solve_problems = TRUE)
    {
        //This method should be called when the options obj is created.
        if ($this->getQuestionId() == NULL OR $this->getQuestionId() == "")
        {
            return false;
        }
        return true;
    }

}
