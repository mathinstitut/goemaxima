<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
namespace question_authoring;
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question authoring model class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 1.6.2$
 * @ingroup    ModulesTestQuestionPool
 *
 */

class assStackQuestionAuthoring {

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * The question already evaluated
	 * @var assStackQuestion
	 */
	private $question;

	//DATA ARRAYS

	/**
	 * @var array the global array to be sent to assStackQuestionAuthoringGUI
	 */
	private $authoring_data;

	/**
	 * @var array the data for general question settings
	 */
	private $general_data;

	/**
	 * @var array the data for options part
	 */
	private $options_data;

	/**
	 * @var array the data for inputs part
	 */
	private $inputs_data;

	/**
	 * @var array the data for PRT part
	 */
	private $prt_data;


	/**
	 * @param $plugin ilassStackQuestionPlugin
	 * @param $question assStackQuestion
	 */
	function __construct($plugin, $question)
	{
		$this->setPlugin($plugin);
		$this->setQuestion($question);
	}

	/**
	 * @param array $authoring_data
	 */
	public function setAuthoringData($authoring_data)
	{
		$this->authoring_data = $authoring_data;
	}

	/**
	 * @return array
	 */
	public function getAuthoringData()
	{
		return $this->authoring_data;
	}

	/**
	 * @param array $general_data
	 */
	public function setGeneralData($general_data)
	{
		$this->general_data = $general_data;
	}

	/**
	 * @return array
	 */
	public function getGeneralData()
	{
		return $this->general_data;
	}

	/**
	 * @param array $inputs_data
	 */
	public function setInputsData($inputs_data)
	{
		$this->inputs_data = $inputs_data;
	}

	/**
	 * @return array
	 */
	public function getInputsData()
	{
		return $this->inputs_data;
	}

	/**
	 * @param array $options_data
	 */
	public function setOptionsData($options_data)
	{
		$this->options_data = $options_data;
	}

	/**
	 * @return array
	 */
	public function getOptionsData()
	{
		return $this->options_data;
	}

	/**
	 * @param \question_authoring\ilassStackQuestionPlugin $plugin
	 */
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return \question_authoring\ilassStackQuestionPlugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param array $prt_data
	 */
	public function setPrtData($prt_data)
	{
		$this->prt_data = $prt_data;
	}

	/**
	 * @return array
	 */
	public function getPrtData()
	{
		return $this->prt_data;
	}

	/**
	 * @param \question_authoring\assStackQuestion $question
	 */
	public function setQuestion($question)
	{
		$this->question = $question;
	}

	/**
	 * @return \question_authoring\assStackQuestion
	 */
	public function getQuestion()
	{
		return $this->question;
	}

	/*
	 * GETTERS AND SETTERS
	 */



} 