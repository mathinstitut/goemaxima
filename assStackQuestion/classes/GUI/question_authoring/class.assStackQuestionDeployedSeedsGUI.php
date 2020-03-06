<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question deployed seeds authoring GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 1.6.2$
 * @ingroup    ModulesTestQuestionPool
 *
 * @ilCtrl_isCalledBy assStackQuestionDeployedSeedsGUI: ilObjQuestionPoolGUI
 */
class assStackQuestionDeployedSeedsGUI
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var ilTemplate for showing the deployed seeds panel
	 */
	private $template;

	/**
	 * @var mixed Array with the assStackQuestionDeployedSeed object of the current question.
	 */
	private $deployed_seeds;

	/**
	 * @var int
	 */
	private $question_id;

	private $parent_obj;

	/**
	 * Sets required data for deployed seeds management
	 * @param $plugin ilassStackQuestionPlugin instance
	 * @param $question_id int
	 */
	function __construct($plugin, $question_id, $parent_obj)
	{
		//Set plugin and template objects
		$this->setPlugin($plugin);
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_deployed_seeds_panel.html'));
		$this->setQuestionId($question_id);

		//Get deployed seeds for current question
		$this->setDeployedSeeds(assStackQuestionDeployedSeed::_read($this->getQuestionId()));
		$this->parent_obj = $parent_obj;
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * @return HTML
	 */
	public function showDeployedSeedsPanel()
	{
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/GUI/tables/class.assStackQuestionSeedsTableGUI.php';
		$seeds_table = new assStackQuestionSeedsTableGUI($this->parent_obj, "deployedSeedsManagement");
		$this->getQuestionNotesForSeeds();
		$seeds_table->prepareData($this->getDeployedSeeds());

		return $this->getDeployedSeedCreationForm()->getHTML() . $seeds_table->getHTML();
	}


	private function getQuestionNotesForSeeds()
	{
		//Create ILIAS options objects and raws
		$ilias_options = assStackQuestionOptions::_read($this->getQuestionId());
		$question_variables_raw = $ilias_options->getQuestionVariables();
		$question_note_raw = $ilias_options->getQuestionNote();
		//Create STACK question
		$this->getPlugin()->includeClass('model/class.assStackQuestionStackQuestion.php');
		$stack_question = new assStackQuestionStackQuestion();
		$stack_question->createOptions($ilias_options);

		//Get question note for each different seed
		foreach ($this->getDeployedSeeds() as $deployed_seed)
		{
			$deployed_seed->setQuestionNote($stack_question->getQuestionNoteForSeed($deployed_seed->getSeed(), $question_variables_raw, $question_note_raw, $this->getQuestionId()));
		}

		//Avoid duplicates bugr 16727#
		$valid_seeds = array();
		$number_of_valid_seeds = 0;
		foreach ($this->getDeployedSeeds() as $deployed_seed)
		{
			$q_note = $deployed_seed->getQuestionNote();
			$include = TRUE;

			if (sizeof($valid_seeds))
			{
				foreach ($valid_seeds as $valid_seed)
				{
					if ($valid_seed->getQuestionNote() == $q_note)
					{
						$deployed_seed->delete();
						$include = FALSE;
						break;
					}
				}
			}

			if ($include)
			{
				$number_of_valid_seeds++;
				$valid_seeds[] = $deployed_seed;
			}
		}

		$this->setDeployedSeeds($valid_seeds);
	}

	private function getDeployedSeedCreationForm()
	{
		global $DIC;

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormActionByClass('assStackQuestionGUI'));
		$form->setTitle($this->getPlugin()->txt("dsm_new_deployed_seed_form"));

		//Input field
		$random_seed = mt_rand(1000000000, 9000000000);
		$new_seed = new ilNumberInputGUI($this->getPlugin()->txt("dsm_new_deployed_seed_form_input"), 'deployed_seed');
		$new_seed->setValue($random_seed);
		$form->addItem($new_seed);

		$question_id = new ilHiddenInputGUI('question_id');
		$question_id->setValue($this->getQuestionId());
		$form->addItem($question_id);

		$form->addCommandButton("createNewDeployedSeed", $this->getPlugin()->txt("dsm_new_deployed_seed_form_button"));
		$form->setShowTopButtons(FALSE);

		return $form;
	}



	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param \ilassStackQuestionPlugin $plugin
	 */
	private function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return \ilassStackQuestionPlugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * @param ilTemplate $template
	 */
	private function setTemplate(ilTemplate $template)
	{
		$this->template = $template;
	}

	/**
	 * @return ilTemplate
	 */
	private function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param mixed $deployed_seeds
	 */
	private function setDeployedSeeds($deployed_seeds)
	{
		$this->deployed_seeds = $deployed_seeds;
	}

	/**
	 * @return mixed
	 */
	public function getDeployedSeeds()
	{
		return $this->deployed_seeds;
	}

	/**
	 * @param int $question_id
	 */
	private function setQuestionId($question_id)
	{
		$this->question_id = $question_id;
	}

	/**
	 * @return int
	 */
	private function getQuestionId()
	{
		return $this->question_id;
	}


}