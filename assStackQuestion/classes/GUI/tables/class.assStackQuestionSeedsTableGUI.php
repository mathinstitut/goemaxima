<?php
/**
 * Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
include_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question deployed seeds Table GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.4$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionSeedsTableGUI extends ilTable2GUI
{
	/**
	 * Constructor
	 * @param   assStackQuestionDeployedSeedsGUI $a_parent_obj
	 * @param   string $a_parent_cmd
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $DIC;

		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();

		$this->plugin = $a_parent_obj->getPlugin();
		//$this->deployed_seeds = $a_parent_obj->getDeployedSeeds();

		$this->setId('assStackQuestionSeeds');
		$this->setPrefix('assStackQuestionSeeds');
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setStyle('table', 'fullwidth');

		$this->addColumn($this->plugin->txt('dsm_deployed_seeds_header'));
		$this->addColumn($this->plugin->txt('dsm_question_notes_header'));
		$this->addColumn($this->plugin->txt('dsm_view_form_header'));

		$this->setRowTemplate("tpl.il_as_qpl_xqcas_seeds_row.html", $a_parent_obj->getPlugin()->getDirectory());
		$this->setEnableAllCommand(FALSE);
		$this->setEnableHeader(TRUE);
		$this->setEnableNumInfo(FALSE);
		$this->setEnableTitle(FALSE);
	}

	/**
	 * Get selectable columns
	 */
	public function getSelectableColumns()
	{
		return array();
	}

	public function prepareData($deployed_seeds)
	{
		foreach ($deployed_seeds as $seed)
		{
			$row['seed'] = $seed->getSeed();
			$row['question_note'] = assStackQuestionUtils::_getLatex(assStackQuestionUtils::_solveKeyBracketsBug($seed->getQuestionNote()));
			$row['form'] = $seed;
			$data[] = $row;
		}
		$this->setData($data);
	}

	/**
	 * @param assStackQuestionDeployedSeed $deployed_seed
	 */
	public function fillRow($deployed_seed)
	{
		$this->tpl->setCurrentBlock('column');
		$this->tpl->setVariable('CONTENT', $deployed_seed['seed']);
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock('column');
		$this->tpl->setVariable('CONTENT', $deployed_seed['question_note']);
		$this->tpl->parseCurrentBlock();

		$this->tpl->setCurrentBlock('column');
		$form = $this->getDeployedSeedViewForm($deployed_seed['form']);
		$this->tpl->setVariable('CONTENT', $form->getHTML());
		$this->tpl->parseCurrentBlock();
	}

	/**
	 * @param assStackQuestionDeployedSeed $seed
	 * @return ilPropertyFormGUI
	 */
	private function getDeployedSeedViewForm($seed)
	{
		global $DIC;

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormActionByClass('assStackQuestionGUI'));

		$delete_seed = new ilHiddenInputGUI('deployed_seed');
		$delete_seed->setValue($seed->getSeed());
		$form->addItem($delete_seed);

		$fixed_seed = new ilHiddenInputGUI('fixed_seed');
		$fixed_seed->setValue($ctrl->getLinkTargetByClass("ilassquestionpagegui", "preview"));
		$form->addItem($fixed_seed);

		$question_id = new ilHiddenInputGUI('question_id');
		$question_id->setValue($seed->getQuestionId());
		$form->addItem($question_id);

		$ctrl->setParameterByClass("ilAssQuestionPageGUI", "fixed_seed", $seed->getSeed());

		$ftpl = new ilTemplate("tpl.external_settings.html", true, true, "Services/Administration");

		$ftpl->setCurrentBlock("edit_bl");
		$ftpl->setVariable("URL_EDIT", $ctrl->getLinkTargetByClass("ilassquestionpagegui", "preview"));
		$ftpl->setVariable("TXT_EDIT", $this->plugin->txt("dsm_fix_deployed_seed_form_button"));
		$ftpl->parseCurrentBlock();

		$ext = new ilCustomInputGUI($this->plugin->txt("dsm_fix_deployed_seed_form_button_text"));
		$ext->setHtml($ftpl->get());
		$form->addItem($ext);


		$form->addCommandButton("deleteDeployedSeed", $this->plugin->txt("dsm_delete_deployed_seed_form_button"));

		$form->setShowTopButtons(FALSE);

		return $form;
	}

}