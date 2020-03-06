<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * STACK Question input object
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jestus Copado <jesus.copado@ili.fau.de>
 * @version $Id 1.6$
 *
 */
class assStackQuestionInput
{

	//ATTRIBUTES
	private $input_id;
	private $question_id;
	private $input_name;
	private $input_type;
	private $teacher_answer;
	private $box_size;
	private $strict_syntax;
	private $insert_stars;
	private $syntax_hint;
	private $forbid_words;
	private $allow_words;
	private $forbid_float;
	private $require_lowest_terms;
	private $check_answer_type;
	private $must_verify;
	private $show_validation;
	private $options;

	function __construct($input_id, $question_id, $input_name, $input_type, $teacher_answer)
	{
		$this->setInputId($input_id);
		$this->setQuestionId($question_id);
		$this->setInputName($input_name);
		$this->setInputType($input_type);
		$this->setTeacherAnswer($teacher_answer);
	}

	public function getInputId()
	{
		return $this->input_id;
	}

	public function getQuestionId()
	{
		return $this->question_id;
	}

	public function getInputName()
	{
		return $this->input_name;
	}

	public function getInputType()
	{
		return $this->input_type;
	}

	public function getTeacherAnswer()
	{
		return $this->teacher_answer;
	}

	public function getBoxSize()
	{
		return $this->box_size;
	}

	public function getStrictSyntax()
	{
		return $this->strict_syntax;
	}

	public function getInsertStars()
	{
		return $this->insert_stars;
	}

	public function getSyntaxHint()
	{
		return $this->syntax_hint;
	}

	public function getForbidWords()
	{
		return $this->forbid_words;
	}

	public function getAllowWords()
	{
		return $this->allow_words;
	}

	public function getForbidFloat()
	{
		return $this->forbid_float;
	}

	public function getRequireLowestTerms()
	{
		return $this->require_lowest_terms;
	}

	public function getCheckAnswerType()
	{
		return $this->check_answer_type;
	}

	public function getMustVerify()
	{
		return $this->must_verify;
	}

	public function getShowValidation()
	{
		return $this->show_validation;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function setInputId($input_id)
	{
		$this->input_id = $input_id;
	}

	public function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	public function setInputName($input_name)
	{
		$this->input_name = $input_name;
	}

	public function setInputType($input_type)
	{
		$this->input_type = $input_type;
	}

	public function setTeacherAnswer($teacher_answer)
	{
		$this->teacher_answer = $teacher_answer;
	}

	public function setBoxSize($box_size)
	{
		$this->box_size = $box_size;
	}

	public function setStrictSyntax($strict_syntax)
	{
		$this->strict_syntax = $strict_syntax;
	}

	public function setInsertStars($insert_stars)
	{
		$this->insert_stars = $insert_stars;
	}

	public function setSyntaxHint($syntax_hint)
	{
		$this->syntax_hint = $syntax_hint;
	}

	public function setAllowWords($allow_words)
	{
		$this->allow_words = $allow_words;
	}

	public function setForbidWords($forbid_words)
	{
		$this->forbid_words = $forbid_words;
	}

	public function setForbidFloat($forbid_float)
	{
		$this->forbid_float = $forbid_float;
	}

	public function setRequireLowestTerms($require_lowest_terms)
	{
		$this->require_lowest_terms = $require_lowest_terms;
	}

	public function setCheckAnswerType($check_answer_type)
	{
		$this->check_answer_type = $check_answer_type;
	}

	public function setMustVerify($must_verify)
	{
		$this->must_verify = $must_verify;
	}

	public function setShowValidation($show_validation)
	{
		$this->show_validation = $show_validation;
	}

	public function setOptions($options)
	{
		$this->options = $options;
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
		if ($this->getInputId() < 0) {
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
		$this->setInputId((int)$db->nextId('xqcas_inputs'));
		//Insert Object into DB
		$db->insert("xqcas_inputs", array(
			"id" => array("integer", $this->getInputId()),
			"question_id" => array("integer", $this->getQuestionId()),
			"name" => array("text", $this->getInputName()),
			"type" => array("text", $this->getInputType()),
			"tans" => array("text", $this->getTeacherAnswer() !== NULL ? $this->getTeacherAnswer() : ""),
			"box_size" => array("integer", $this->getBoxSize() !== NULL ? $this->getBoxSize() : "15"),
			"strict_syntax" => array("integer", $this->getStrictSyntax() !== NULL ? $this->getStrictSyntax() : "1"),
			"insert_stars" => array("integer", $this->getInsertStars() !== NULL ? $this->getInsertStars() : "0"),
			"syntax_hint" => array("text", $this->getSyntaxHint() !== NULL ? $this->getSyntaxHint() : ""),
			"forbid_words" => array("text", $this->getForbidWords() !== NULL ? $this->getForbidWords() : ""),
			"allow_words" => array("text", $this->getAllowWords() !== NULL ? $this->getAllowWords() : ""),
			"forbid_float" => array("integer", $this->getForbidFloat() !== NULL ? $this->getForbidFloat() : "1"),
			"require_lowest_terms" => array("integer", $this->getRequireLowestTerms() !== NULL ? $this->getRequireLowestTerms() : "0"),
			"check_answer_type" => array("integer", $this->getCheckAnswerType() !== NULL ? $this->getCheckAnswerType() : "0"),
			"must_verify" => array("integer", $this->getMustVerify() !== NULL ? $this->getMustVerify() : "1"),
			"show_validation" => array("integer", $this->getShowValidation() !== NULL ? $this->getShowValidation() : "1"),
			"options" => array("clob", $this->getOptions() !== NULL ? $this->getOptions() : "")
		));
		return true;
	}

	public static function _read($question_id)
	{
		global $DIC;
		$db = $DIC->database();
		//Inputs array
		$inputs = array();
		//Select query
		$query = 'SELECT * FROM xqcas_inputs WHERE question_id = '
			. $db->quote($question_id, 'integer');
		$res = $db->query($query);

		//If there is a result returns object, otherwise returns false.
		while ($row = $db->fetchAssoc($res)) {
			//Options object to return in case there are options in DB for this $question_id
			$input = new assStackQuestionInput((int)$row["id"], (int)$question_id, $row["name"], $row["type"], $row["tans"]);
			//Filling object with data from DB
			$input->setBoxSize((int)$row["box_size"]);
			$input->setStrictSyntax((boolean)$row["strict_syntax"]);
			$input->setInsertStars((int)$row["insert_stars"]);
            $input->setTeacherAnswer($row["tans"]);
			$input->setSyntaxHint((isset($row["syntax_hint"]) AND $row["syntax_hint"] != NULL) ? trim($row["syntax_hint"]) : "");
			$input->setForbidWords($row["forbid_words"]);
			$input->setAllowWords($row["allow_words"]);
			$input->setForbidFloat((boolean)$row["forbid_float"]);
			$input->setRequireLowestTerms((boolean)$row["require_lowest_terms"]);
			$input->setCheckAnswerType((boolean)$row["check_answer_type"]);
			$input->setMustVerify((boolean)$row["must_verify"]);
			$input->setShowValidation((int)$row["show_validation"]);
			$input->setOptions($row["options"]);
			$inputs[$row["name"]] = $input;
		}
		return $inputs;
	}

	public function update()
	{
		global $DIC;
		$db = $DIC->database();
		$db->replace('xqcas_inputs',
			array(
				"id" => array('integer', $this->getInputId())),
			array(
				"question_id" => array("integer", $this->getQuestionId()),
				"name" => array("text", $this->getInputName()),
				"type" => array("text", $this->getInputType()),
				"tans" => array("text", $this->getTeacherAnswer()),
				"box_size" => array("integer", $this->getBoxSize()),
				"strict_syntax" => array("integer", $this->getStrictSyntax()),
				"insert_stars" => array("integer", $this->getInsertStars()),
				"syntax_hint" => array("text", $this->getSyntaxHint() == NULL ? "" : $this->getSyntaxHint()),
				"forbid_words" => array("text", $this->getForbidWords()== NULL ? "" : $this->getForbidWords()),
				"allow_words" => array("text", $this->getAllowWords()== NULL ? "" : $this->getAllowWords()),
				"forbid_float" => array("integer", $this->getForbidFloat()== NULL ? 0 : $this->getForbidFloat()),
				"require_lowest_terms" => array("integer", $this->getRequireLowestTerms()== NULL ? 0 : $this->getRequireLowestTerms()),
				"check_answer_type" => array("integer", $this->getCheckAnswerType()== NULL ? 0 : $this->getCheckAnswerType()),
				"must_verify" => array("integer", $this->getMustVerify()== NULL ? 0 : $this->getMustVerify()),
				"show_validation" => array("integer", $this->getShowValidation()== NULL ? 0 : $this->getShowValidation()),
				"options" => array("clob", $this->getOptions()== NULL ? "" : $this->getOptions())
			)
		);

		return TRUE;
	}

	public function delete()
	{
		global $DIC;
		$db = $DIC->database();

		$query = 'DELETE FROM xqcas_inputs WHERE id = ' . $db->quote($this->getInputId(), 'integer');
		$db->manipulate($query);
	}

	public function getDefaultInput()
	{
		global $DIC;
		$db = $DIC->database();


		//Select query
		$query = 'SELECT * FROM xqcas_configuration WHERE group_name = "inputs"';
		$res = $db->query($query);

		while ($row = $db->fetchAssoc($res)) {
			if ($row['parameter_name'] == 'input_box_size') {
				$this->setBoxSize((int)$row['value']);
			}
			if ($row['parameter_name'] == 'input_strict_syntax') {
				$this->setStrictSyntax((int)$row['value']);
			}
			if ($row['parameter_name'] == 'input_insert_stars') {
				$this->setInsertStars($row['value']);
			}
			$this->setSyntaxHint('');
			if ($row['parameter_name'] == 'input_forbidden_words') {
				$this->setForbidWords($row['value']);
			}
			if ($row['parameter_name'] == 'input_forbid_float') {
				$this->setForbidFloat($row['value']);
			}
			if ($row['parameter_name'] == 'input_require_lowest_terms') {
				$this->setRequireLowestTerms((boolean)$row['value']);
			}
			if ($row['parameter_name'] == 'input_check_answer_type') {
				$this->setCheckAnswerType((boolean)$row['value']);
			}
			if ($row['parameter_name'] == 'input_must_verify') {
				$this->setMustVerify((boolean)$row['value']);
			}
			if ($row['parameter_name'] == 'input_show_validation') {
				$this->setShowValidation((int)$row['value']);
			}
		}
	}


	public function writePostData($input_name)
	{
		$this->setInputName($input_name);
		$this->setInputType(ilUtil::stripSlashes($_POST[$input_name . '_input_type']));
		$this->setBoxSize(ilUtil::stripSlashes($_POST[$input_name . '_input_box_size']));
		$this->setStrictSyntax(ilUtil::stripSlashes($_POST[$input_name . '_input_strict_syntax']));
		$this->setInsertStars(ilUtil::stripSlashes($_POST[$input_name . '_input_insert_stars']));
		$this->setSyntaxHint((isset($_POST[$input_name . '_input_syntax_hint']) AND $_POST[$input_name . '_input_syntax_hint'] != NULL) ? trim($_POST[$input_name . '_input_syntax_hint']) : "");
		$this->setForbidWords(ilUtil::stripSlashes($_POST[$input_name . '_input_forbidden_words']));
		$this->setAllowWords(ilUtil::stripSlashes($_POST[$input_name . '_input_allow_words']));
		$this->setForbidFloat(ilUtil::stripSlashes($_POST[$input_name . '_input_forbid_float']));
		$this->setRequireLowestTerms((boolean)ilUtil::stripSlashes($_POST[$input_name . '_input_require_lowest_terms']));
		$this->setCheckAnswerType(ilUtil::stripSlashes($_POST[$input_name . '_input_check_answer_type']));
		$this->setMustVerify(ilUtil::stripSlashes($_POST[$input_name . '_input_must_verify']));
		$this->setShowValidation(ilUtil::stripSlashes($_POST[$input_name . '_input_show_validation']));
		$this->setOptions(ilUtil::stripSlashes($_POST[$input_name . '_input_options']));
        $this->setTeacherAnswer(ilUtil::stripSlashes($_POST[$input_name . '_input_model_answer']));
    }

	/**
	 * This function checks if each parameter of an assStackQuestionInput object is properly set.
	 * If $solve_problems is true, sets the atribute with an empty or default value.
	 * @param boolean $solve_problems
	 * @return boolean
	 */
	public function checkInput($solve_problems = TRUE)
	{
		//This method should be called when the input obj is created.
		//This attributes cannot be null or void in any case.
		if ($this->getInputType() == NULL OR $this->getInputType() == "") {
			if ($solve_problems) {
				$this->setSyntaxHint("algebraic");
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
		//Other Not Null variables:
		if ($this->getSyntaxHint() == NULL OR $this->getSyntaxHint() == "") {
			if ($solve_problems) {
				$this->setSyntaxHint("");
			} else {
				return false;
			}
		}
		if ($this->getForbidWords() == NULL OR $this->getForbidWords() == "") {
			if ($solve_problems) {
				$this->setForbidWords("");
			} else {
				return false;
			}
		}
		if ($this->getAllowWords() == NULL OR $this->getAllowWords() == "") {
			if ($solve_problems) {
				$this->setAllowWords("");
			} else {
				return false;
			}
		}
		if ($this->getShowValidation() == NULL OR $this->getShowValidation() == "") {
			if ($solve_problems) {
				$this->setShowValidation(FALSE);
			} else {
				return false;
			}
		}
		if ($this->getOptions() == NULL OR $this->getOptions() == "") {
			if ($solve_problems) {
				$this->setOptions("");
			} else {
				return false;
			}
		}
		return true;
	}

	//INCOMPLETE
	public function getArrayOfParameters()
	{
		$parameters = array();
		$parameters['mustVerify'] = $this->getMustVerify();
		//$parameters['hideFeedback']=  $this->get();
		$parameters['boxWidth'] = $this->getBoxSize();
		$parameters['strictSyntax'] = $this->getStrictSyntax();
		$parameters['insertStars'] = $this->getInsertStars();
		$parameters['syntaxHint'] = $this->getSyntaxHint();
		$parameters['forbidWords'] = $this->getForbidWords();
		//$parameters['allowWords']=  $this->getMustVerify();
		$parameters['forbidFloats'] = $this->getForbidFloat();
		$parameters['lowestTerms'] = $this->getRequireLowestTerms();
		//$parameters['sameType']=;
		return $parameters;
	}


}
