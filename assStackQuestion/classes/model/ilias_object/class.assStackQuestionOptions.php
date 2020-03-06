<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * STACK Question Options object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jestus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionOptions
{

	//ATTRIBUTES
	private $options_id;
	private $question_id;
	private $question_variables;
	private $specific_feedback;
	private $specific_feedback_format;
	private $question_note;
	private $question_simplify;
	private $assume_positive;
	private $prt_correct;
	private $prt_correct_format;
	private $prt_partially_correct;
	private $prt_partially_correct_format;
	private $prt_incorrect;
	private $prt_incorrect_format;
	private $multiplication_sign;
	private $sqrt_sign;
	private $complex_numbers;
	private $inverse_trig;
	private $variants_selection_seeds;
	//STACK 3.3
	private $matrix_parens;

	function __construct($options_id, $question_id)
	{
		$this->setOptionsId($options_id);
		$this->setQuestionId($question_id);
	}

	/*
	 * GETTERS AND SETTERS
	 */

	public function getOptionsId()
	{
		return $this->options_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getQuestionVariables()
	{
		return $this->question_variables;
	}

	public function getSpecificFeedback()
	{
		return $this->specific_feedback;
	}

	public function getSpecificFeedbackFormat()
	{
		return $this->specific_feedback_format;
	}

	public function getQuestionNote()
	{
		return $this->question_note;
	}

	public function getQuestionSimplify()
	{
		return $this->question_simplify;
	}

	public function getAssumePositive()
	{
		return $this->assume_positive;
	}

	public function getPRTCorrect()
	{
		return $this->prt_correct;
	}

	public function getPRTCorrectFormat()
	{
		return $this->prt_correct_format;
	}

	public function getPRTPartiallyCorrect()
	{
		return $this->prt_partially_correct;
	}

	public function getPRTPartiallyCorrectFormat()
	{
		return $this->prt_partially_correct_format;
	}

	public function getPRTIncorrect()
	{
		return $this->prt_incorrect;
	}

	public function getPRTIncorrectFormat()
	{
		return $this->prt_incorrect_format;
	}

	public function getMultiplicationSign()
	{
		return $this->multiplication_sign;
	}

	public function getSqrtSign()
	{
		return $this->sqrt_sign;
	}

	public function getComplexNumbers()
	{
		return $this->complex_numbers;
	}

	public function getInverseTrig()
	{
		return $this->inverse_trig;
	}

	public function getVariantsSelectionSeeds()
	{
		return $this->variants_selection_seeds;
	}

	public function setOptionsId($options_id)
	{
		$this->options_id = $options_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setQuestionVariables($question_variables)
	{
		$this->question_variables = $question_variables;
	}

	public function setSpecificFeedback($specific_feedback)
	{
		$this->specific_feedback = $specific_feedback;
	}

	public function setSpecificFeedbackFormat($specific_feedback_format)
	{
		$this->specific_feedback_format = $specific_feedback_format;
	}

	public function setQuestionNote($question_note)
	{
		$this->question_note = $question_note;
	}

	public function setQuestionSimplify($question_simplify)
	{
		$this->question_simplify = $question_simplify;
	}

	public function setAssumePositive($assume_positive)
	{
		$this->assume_positive = $assume_positive;
	}

	public function setPRTCorrect($prt_correct)
	{
		$this->prt_correct = $prt_correct;
	}

	public function setPRTCorrectFormat($prt_correct_format)
	{
		$this->prt_correct_format = $prt_correct_format;
	}

	public function setPRTPartiallyCorrect($prt_partially_correct)
	{
		$this->prt_partially_correct = $prt_partially_correct;
	}

	public function setPRTPartiallyCorrectFormat($prt_partially_correct_format)
	{
		$this->prt_partially_correct_format = $prt_partially_correct_format;
	}

	public function setPRTIncorrect($prt_incorrect)
	{
		$this->prt_incorrect = $prt_incorrect;
	}

	public function setPRTIncorrectFormat($prt_incorrect_format)
	{
		$this->prt_incorrect_format = $prt_incorrect_format;
	}

	public function setMultiplicationSign($multiplication_sign)
	{
		$this->multiplication_sign = $multiplication_sign;
	}

	public function setSqrtSign($sqrt_sign)
	{
		$this->sqrt_sign = $sqrt_sign;
	}

	public function setComplexNumbers($complex_numbers)
	{
		$this->complex_numbers = $complex_numbers;
	}

	public function setInverseTrig($inverse_trig)
	{
		$this->inverse_trig = $inverse_trig;
	}

	public function setVariantsSelectionSeeds($variants_selection_seeds)
	{
		$this->variants_selection_seeds = $variants_selection_seeds;
	}

	/**
	 * @param mixed $matrix_parens
	 */
	public function setMatrixParens($matrix_parens)
	{
		if ($matrix_parens == "]") {
			$this->matrix_parens = "[";
		} elseif ($matrix_parens == ")") {
			$this->matrix_parens = "(";
		} else {
			$this->matrix_parens = $matrix_parens;
		}
	}

	/**
	 * @return mixed
	 */
	public function getMatrixParens()
	{
		return $this->matrix_parens;
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
		if ($this->getOptionsId() < 0) {
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
		$this->setOptionsId((int)$db->nextId('xqcas_options'));
		//Insert Object into DB
		$db->insert("xqcas_options", array(
			"id" => array("integer", $this->getOptionsId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"question_variables" => array("clob", $this->getQuestionVariables()),
			"specific_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getSpecificFeedback(), 0)),
			"specific_feedback_format" => array("integer", $this->getSpecificFeedbackFormat()),
			"question_note" => array("text", $this->getQuestionNote()),
			"question_simplify" => array("integer", $this->getQuestionSimplify()),
			"assume_positive" => array("integer", $this->getAssumePositive()),
			"prt_correct" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getPRTCorrect(), 0)),
			"prt_correct_format" => array("integer", $this->getPRTCorrectFormat()),
			"prt_partially_correct" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getPRTPartiallyCorrect(), 0)),
			"prt_partially_correct_format" => array("integer", $this->getPRTPartiallyCorrectFormat()),
			"prt_incorrect" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getPRTIncorrect(), 0)),
			"prt_incorrect_format" => array("integer", $this->getPRTIncorrectFormat()),
			"multiplication_sign" => array("text", $this->getMultiplicationSign() == NULL ? "dot" : $this->getMultiplicationSign()),
			"sqrt_sign" => array("integer", $this->getSqrtSign()),
			"complex_no" => array("text", $this->getComplexNumbers() == NULL ? "i" : $this->getComplexNumbers()),
			"inverse_trig" => array("text", $this->getInverseTrig()),
			"variants_selection_seed" => array("text", $this->getVariantsSelectionSeeds()),
			"matrix_parens" => array("text", $this->getMatrixParens())
		));
		return true;
	}

	public static function _read($question_id)
	{
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

		//Options object to return in case there are options in DB for this $question_id
		$options = new assStackQuestionOptions(-1, $question_id);

		//Select query
		$query = 'SELECT * FROM xqcas_options WHERE question_id = '
			. $db->quote($question_id, 'integer');
		$res = $db->query($query);
		$row = $db->fetchObject($res);

		//If there is a result returns object, otherwise returns false.
		if ($row) {
			//Filling object with data from DB
			$options->setOptionsId((int)$row->id);
			$options->setQuestionId((int)$row->question_id);
			$options->setQuestionVariables($row->question_variables);
			$options->setSpecificFeedback(ilRTE::_replaceMediaObjectImageSrc($row->specific_feedback, 1));
			$options->setSpecificFeedbackFormat((int)$row->specific_feedback_format);
			$options->setQuestionNote($row->question_note);
			$options->setQuestionSimplify((int)$row->question_simplify);
			$options->setAssumePositive((int)$row->assume_positive);
			$options->setPRTCorrect(ilRTE::_replaceMediaObjectImageSrc($row->prt_correct, 1));
			$options->setPRTCorrectFormat((int)$row->prt_correct_format);
			$options->setPRTPartiallyCorrect(ilRTE::_replaceMediaObjectImageSrc($row->prt_partially_correct, 1));
			$options->setPRTPartiallyCorrectFormat((int)$row->prt_partially_correct_format);
			$options->setPRTIncorrect(ilRTE::_replaceMediaObjectImageSrc($row->prt_incorrect, 1));
			$options->setPRTIncorrectFormat((int)$row->prt_incorrect_format);
			$options->setMultiplicationSign($row->multiplication_sign);
			$options->setSqrtSign((int)$row->sqrt_sign);
			$options->setComplexNumbers($row->complex_no);
			$options->setInverseTrig($row->inverse_trig);
			$options->setVariantsSelectionSeeds($row->variants_selection_seed);
			$options->setMatrixParens($row->matrix_parens);
			return $options;
		} else {
			return false;
		}
	}

	public function update()
	{
		global $DIC;
		$db = $DIC->database();
		include_once("./Services/RTE/classes/class.ilRTE.php");

		$db->replace('xqcas_options',
			array(
				"id" => array('integer', $this->getOptionsId())),
			array(
				"question_id" => array("integer", $this->getQuestionId()),
				"question_variables" => array("clob", $this->getQuestionVariables()),
				"specific_feedback" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getSpecificFeedback(), 0)),
				"specific_feedback_format" => array("integer", $this->getSpecificFeedbackFormat()),
				"question_note" => array("text", $this->getQuestionNote()),
				"question_simplify" => array("integer", $this->getQuestionSimplify()),
				"assume_positive" => array("integer", $this->getAssumePositive()),
				"prt_correct" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getPRTCorrect(), 0)),
				"prt_correct_format" => array("integer", $this->getPRTCorrectFormat()),
				"prt_partially_correct" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getPRTPartiallyCorrect(), 0)),
				"prt_partially_correct_format" => array("integer", $this->getPRTPartiallyCorrectFormat()),
				"prt_incorrect" => array("clob", ilRTE::_replaceMediaObjectImageSrc($this->getPRTIncorrect(), 0)),
				"prt_incorrect_format" => array("integer", $this->getPRTIncorrectFormat()),
				"multiplication_sign" => array("text", $this->getMultiplicationSign() == NULL ? "dot" : $this->getMultiplicationSign()),
				"sqrt_sign" => array("integer", $this->getSqrtSign()),
				"complex_no" => array("text", $this->getComplexNumbers() == NULL ? "i" : $this->getComplexNumbers()),
				"inverse_trig" => array("text", $this->getInverseTrig()),
				"variants_selection_seed" => array("text", $this->getVariantsSelectionSeeds()),
				"matrix_parens" => array("text", $this->getMatrixParens() === NULL ? "[" : $this->getMatrixParens())
			)
		);

		return TRUE;
	}

	public function delete()
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'DELETE FROM xqcas_options WHERE id = ' . $db->quote($this->getOptionsId(), 'integer');
		$db->manipulate($query);
	}

	public function getDefaultOptions()
	{
		global $DIC;
		$db = $DIC->database();

		//Select query
		$query = 'SELECT * FROM xqcas_configuration WHERE group_name = "options"';
		$res = $db->query($query);

		while ($row = $db->fetchAssoc($res)) {
			//Filling object with data from DB
			$this->setQuestionVariables("");
			$this->setSpecificFeedback("");
			$this->setSpecificFeedbackFormat(1);
			$this->setQuestionNote("");
			if ($row['parameter_name'] == 'options_question_simplify') {
				$this->setQuestionSimplify((int)$row['value']);
			}
			if ($row['parameter_name'] == 'options_assume_positive') {
				$this->setAssumePositive((int)$row['value']);
			}
			if ($row['parameter_name'] == 'options_prt_correct') {
				$this->setPRTCorrect($row['value']);
			}
			$this->setPRTCorrectFormat(1);
			if ($row['parameter_name'] == 'options_prt_partially_correct') {
				$this->setPRTPartiallyCorrect($row['value']);
			}
			$this->setPRTPartiallyCorrectFormat(1);
			if ($row['parameter_name'] == 'options_prt_incorrect') {
				$this->setPRTIncorrect($row['value']);
			}
			$this->setPRTIncorrectFormat(1);
			if ($row['parameter_name'] == 'options_multiplication_sign') {
				$this->setMultiplicationSign($row['value']);
			}
			if ($row['parameter_name'] == 'options_sqrt_sign') {
				$this->setSqrtSign((int)$row['value']);
			}
			if ($row['parameter_name'] == 'options_complex_numbers') {
				$this->setComplexNumbers($row['value']);
			}
			if ($row['parameter_name'] == 'options_inverse_trigonometric') {
				$this->setInverseTrig($row['value']);
			}
		}
	}

	/**
	 * Write the posted data from the question editing form
	 * @param string $a_rte_tags allowed html tags for RTE fields, e.g. "<em><strong>..."
	 */
	public function writePostData($a_rte_tags = "")
	{
		$this->setQuestionVariables($_POST['options_question_variables']);
		$this->setQuestionSimplify(ilUtil::stripSlashes($_POST['options_question_simplify']));
		$this->setSpecificFeedback(ilUtil::stripSlashes($_POST['options_specific_feedback'], true, $a_rte_tags));
		$this->setPRTCorrect(ilUtil::stripSlashes($_POST['options_prt_correct'], true, $a_rte_tags));
		$this->setPRTPartiallyCorrect(ilUtil::stripSlashes($_POST['options_prt_partially_correct'], true, $a_rte_tags));
		$this->setPRTIncorrect(ilUtil::stripSlashes($_POST['options_prt_incorrect'], true, $a_rte_tags));
		$this->setMultiplicationSign(ilUtil::stripSlashes($_POST['options_multiplication_sign']));
		$this->setSqrtSign(ilUtil::stripSlashes($_POST['options_sqrt_sign']));
		$this->setComplexNumbers(ilUtil::stripSlashes($_POST['options_complex_numbers']));
		$this->setInverseTrig(ilUtil::stripSlashes($_POST['options_inverse_trigonometric']));
		$this->setMatrixParens(ilUtil::stripSlashes($_POST['options_matrix_parens']));
		$this->setAssumePositive(ilUtil::stripSlashes($_POST['options_assume_positive']));
		$this->setQuestionNote(ilUtil::stripSlashes($_POST['options_question_note']));
	}

	/**
	 * This function checks if each parameter of an assStackQuestionOptions object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkOptions($solve_problems = TRUE)
	{
		//This method should be called when the options obj is created.
		if ($this->getQuestionId() == NULL OR $this->getQuestionId() == "") {
			return false;
		}
		//Not Null variables:
		if ($this->getQuestionVariables() == NULL OR $this->getQuestionVariables() == "") {
			if ($solve_problems) {
				$this->setQuestionVariables("");
			} else {
				return false;
			}
		}
		if ($this->getSpecificFeedback() == NULL OR $this->getSpecificFeedback() == "") {
			if ($solve_problems) {
				$this->setSpecificFeedback("");
			} else {
				return false;
			}
		}
		if ($this->getSpecificFeedbackFormat() == NULL OR $this->getSpecificFeedbackFormat() == "") {
			if ($solve_problems) {
				$this->setSpecificFeedbackFormat(1);
			} else {
				return false;
			}
		}
		if ($this->getQuestionNote() == NULL OR $this->getQuestionNote() == "") {
			if ($solve_problems) {
				$this->setQuestionNote("");
			} else {
				return false;
			}
		}
		if ($this->getQuestionNote() == NULL OR $this->getQuestionNote() == "") {
			if ($solve_problems) {
				$this->setQuestionNote("");
			} else {
				return false;
			}
		}
		if ($this->getPRTCorrect() == NULL OR $this->getPRTCorrect() == "") {
			if ($solve_problems) {
				$this->setPRTCorrect("");
			} else {
				return false;
			}
		}
		if ($this->getPRTPartiallyCorrect() == NULL OR $this->getPRTPartiallyCorrect() == "") {
			if ($solve_problems) {
				$this->setPRTPartiallyCorrect("");
			} else {
				return false;
			}
		}
		if ($this->getPRTIncorrect() == NULL OR $this->getPRTIncorrect() == "") {
			if ($solve_problems) {
				$this->setPRTIncorrect("");
			} else {
				return false;
			}
		}
		return true;
	}

}
