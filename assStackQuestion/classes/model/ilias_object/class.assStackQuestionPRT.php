<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * STACK Question Potential Response Tree object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionPRT
{

	//Attributes
	private $prt_id;
	private $question_id;
	private $prt_name;
	private $prt_value;
	private $auto_simplify;
	private $prt_feedback_variables;
	private $first_node_name;
	//New ILIAS version attribute, an array with every node of this prt.
	private $prt_nodes;
	private $number_of_nodes;

	public function __construct($prt_id, $question_id)
	{
		$this->setPRTId($prt_id);
		$this->setQuestionId($question_id);
	}

	/*
	 *  GETTERS AND SETTERS
	 */

	public function getPRTId()
	{
		return $this->prt_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getPRTName()
	{
		return $this->prt_name;
	}

	public function getPRTValue()
	{
		return $this->prt_value;
	}

	public function getAutoSimplify()
	{
		return $this->auto_simplify;
	}

	public function getPRTFeedbackVariables()
	{
		return $this->prt_feedback_variables;
	}

	public function getFirstNodeName($update = FALSE)
	{
		if ($update) {
			$min = 1;
			foreach ($this->getPRTNodes() as $node_name => $node) {
				if ((int)$node_name < $min) {
					$min = (int)$node_name;
				}
			}
			return $min;
		} else {
			return $this->first_node_name;
		}
	}

	public function getPRTNodes()
	{
		return $this->prt_nodes;
	}

	public function setPRTId($prt_id)
	{
		$this->prt_id = $prt_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setPRTName($prt_name)
	{
		$this->prt_name = $prt_name;
	}

	public function setPRTValue($prt_value)
	{
		$this->prt_value = $prt_value;
	}

	public function setAutoSimplify($auto_simplify)
	{
		$this->auto_simplify = $auto_simplify;
	}

	public function setPRTFeedbackVariables($prt_feedback_variables)
	{
		$this->prt_feedback_variables = $prt_feedback_variables;
	}

	public function setFirstNodeName($first_node_name)
	{
		$this->first_node_name = $first_node_name;
	}

	public function setPRTNodes($prt_nodes)
	{
		$this->prt_nodes = $prt_nodes;
	}

	public function getNumberOfNodes()
	{
		return $this->number_of_nodes;
	}

	public function setNumberOfNodes($number_of_nodes)
	{
		$this->number_of_nodes = $number_of_nodes;
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
		$this->checkPRT(TRUE);

		if ($this->getPRTId() < 0) {
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
		$this->setPRTId((int)$db->nextId('xqcas_prts'));
		//Insert Object into DB
		$db->insert("xqcas_prts", array(
			"id" => array("integer", $this->getPRTId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"name" => array("text", $this->getPRTName()),
			"value" => array("text", $this->getPRTValue() == NULL ? "1" : $this->getPRTValue()),
			"auto_simplify" => array("integer", $this->getAutoSimplify() == NULL ? 0 : $this->getAutoSimplify()),
			"feedback_variables" => array("clob", $this->getPRTFeedbackVariables() == NULL ? "" : $this->getPRTFeedbackVariables()),
			"first_node_name" => array("text", $this->getFirstNodeName() == NULL ? "0" : $this->getFirstNodeName()),
		));
		return true;
	}

	public static function _read($question_id)
	{
		global $DIC;
		$db = $DIC->database();
		//Inputs array
		$PRTs = array();
		//Select query
		$query = 'SELECT * FROM xqcas_prts WHERE question_id = '
			. $db->quote($question_id, 'integer')
			. ' ORDER BY xqcas_prts.id';
		$res = $db->query($query);

		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/model/ilias_object/class.assStackQuestionPRTNode.php';
		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//PRT object to return in case there are options in DB for this $question_id
			$PRT = new assStackQuestionPRT((int)$row["id"], $question_id);
			//Filling object with data from DB
			$PRT->setPRTName($row["name"]);
			$PRT->setPRTValue($row["value"]);
			$PRT->setAutoSimplify((int)$row["auto_simplify"]);
			$PRT->setPRTFeedbackVariables($row["feedback_variables"]);
			$PRT->setFirstNodeName($row["first_node_name"]);
			//Reading nodes
			$PRT->setPRTNodes(assStackQuestionPRTNode::_read($question_id, $PRT->getPRTName()));
			$PRT->setNumberOfNodes(sizeof($PRT->getPRTNodes()));
			$PRTs[$row["name"]] = $PRT;
		}
		return $PRTs;
	}

	public function update()
	{
		global $DIC;
		$db = $DIC->database();

		$db->replace('xqcas_prts',
			array(
				"id" => array('integer', $this->getPRTId())),
			array(
				"question_id" => array("integer", $this->getQuestionId()),
				"name" => array("text", $this->getPRTName()),
				"value" => array("text", $this->getPRTValue()),
				"auto_simplify" => array("text", $this->getAutoSimplify()),
				"feedback_variables" => array("clob", $this->getPRTFeedbackVariables()),
				"first_node_name" => array("integer", (int)$this->getFirstNodeName())
			)
		);

		return TRUE;
	}

	public function delete()
	{
		global $DIC;
		$db = $DIC->database();
		$query = 'DELETE FROM xqcas_prts WHERE id = ' . $db->quote($this->getPRTId(), 'integer');
		$db->manipulate($query);
	}

	/**
	 * Write the posted data from the question editing form
	 * @param 	string	$prt_name			current prt name
	 * @param	string	$new_prt_name		new prt name
	 * @param 	string	$a_rte_tags			allowed html tags for RTE fields, e.g. "<em><strong>..."
	 */
	public function writePostData($prt_name, $new_prt_name = "", $a_rte_tags = "")
	{
		$this->setPRTValue(ilUtil::stripSlashes($_POST['prt_' . $prt_name . '_value']));
		$this->setAutoSimplify(ilUtil::stripSlashes($_POST['prt_' . $prt_name . '_simplify']));
		$this->setPRTFeedbackVariables($_POST['prt_' . $prt_name . '_feedback_variables']);
		$this->setFirstNodeName(ilUtil::stripSlashes($_POST['prt_' . $prt_name . '_first_node']));
		//The name of the potentialresponse tree cannot be changed. in order to maintain the nodes correspondence
		if ($new_prt_name) {
			//In case of new Prt
			$this->setPRTName($new_prt_name);
			$this->checkPRT(TRUE,TRUE);
			$this->save();
		} else {
			$this->setPRTName($prt_name);
		}

		foreach ($this->getPRTNodes() as $node_name => $node) {
			$node->writePostData($prt_name, $node_name, $new_prt_name, "", $a_rte_tags);
		}
	}

	/**
	 * This function checks if each parameter of an assStackQuestionPRT object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkPRT($solve_problems = TRUE, $new_prt = FALSE)
	{
		//This method should be called when the assStackQuestionPRT obj is created.
		//This attributes cannot be null or void in any case.
		if ($this->getPRTName() == NULL OR $this->getPRTName() == "") {
			if ($solve_problems) {
				$this->setPRTName('prt1');
			}
		}
		if ($this->getPRTValue() == NULL OR $this->getPRTValue() == "") {
			if ($solve_problems) {
				$this->setPRTValue(1);
			}
		}
		if ($this->getFirstNodeName() == NULL OR $this->getFirstNodeName() == "") {
			if ($solve_problems) {
				$this->setFirstNodeName(1);
			}
		}
		//Other Not Null variables:
		if ($this->getPRTFeedbackVariables() == NULL OR $this->getPRTFeedbackVariables() == "") {
			if ($solve_problems) {
				$this->setPRTFeedbackVariables("");
			} else {
				return false;
			}
		}
		if ($new_prt) {
			if (sizeof($this->getPRTNodes())) {
				foreach ($this->getPRTNodes() as $node) {
					if (!$node->isComplete()) {
						return FALSE;
					}
				}
			} else {
				return FALSE;
			}
			return true;
		}
		return true;
	}

	public function getLastNodeName()
	{
		$max = 0;
		foreach ($this->getPRTNodes() as $node_name => $node) {
			(int)$node_name > $max ? $max = (int)$node_name : $max;
		}

		return $max;
	}

}
