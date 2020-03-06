<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question scoring GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.0$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionScoringGUI
{

	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var ilTemplate for showing the scoring panel
	 */
	private $template;

	/**
	 * @var mixed array with the potential response trees.
	 */
	private $potentialresponse_trees;

	/**
	 * @var float question points
	 */
	private $question_points;

	/**
	 * @param $plugin ilassStackQuestionPlugin
	 * @param $question_id int	question id
	 * @param $question_points float question points value
	 */
	function __construct($plugin, $question_id, $question_points)
	{
		//Set plugin and template
		$this->setPlugin($plugin);
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_scoring_panel.html'));

		//Set PRT by question_id calling the object class
		$this->getPlugin()->includeClass('model/question_authoring/class.assStackQuestionScoring.php');
		$this->getPlugin()->includeClass('model/ilias_object/class.assStackQuestionPRT.php');
		$this->object = new assStackQuestionScoring(assStackQuestionPRT::_read($question_id));
		$this->setPotentialresponseTrees($this->object->getPotentialresponseTrees());

		//Set question points by ID
		$this->setQuestionPoints((float)$question_points);
	}


	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * Creates and returns the scoring panel
	 * @return HTML
	 */
	public function showScoringPanel($new_question_points = '')
	{
		//Step #1: Get points per PRT and set the strcuture as PRT
		$this->setPotentialresponseTrees($this->object->reScalePotentialresponseTrees($this->getQuestionPoints()));
		//Step #2: Fill form and general data in the scoring template
		$this->fillGeneralData($new_question_points);
		//Step #3: Fill specific PRT data
		$this->fillPRTspecific('current');
		//Step #4: Fill specific PRT data when comparison is required
		if (is_float($new_question_points)) {
			//Set new points and get the new structure for comparison
			$this->setQuestionPoints($new_question_points);
			$this->setPotentialresponseTrees($this->object->reScalePotentialresponseTrees($this->getQuestionPoints()));
			$this->fillPRTspecific('new');
		}
		//Step #5: Return HTML
		return $this->getTemplate()->get();
	}

	/*
	 * FILL TEMPLATE METHODS
	 */

	/**
	 * Fill general data as the title and the points form.
	 * @param float $new_question_points
	 */
	private function fillGeneralData($new_question_points = '')
	{
		//Fill Title
		$this->getTemplate()->setVariable('SCORING_TABLE_TITLE', $this->getPlugin()->txt('sco_scoring'));
		$this->getTemplate()->setVariable('SCORING_TABLE_SUBTITLE', $this->getPlugin()->txt('sco_subtitle'));
		//Fill Forms
		$this->getTemplate()->setVariable('SCORING_FORM', $this->getScoringCreationForm($new_question_points)->getHTML());
	}

	/**
	 * Points management form creation
	 * @param float $new_question_points_value
	 * @return ilPropertyFormGUI
	 */
	private function getScoringCreationForm($new_question_points_value = '')
	{
		global $DIC;

		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormActionByClass('assStackQuestionGUI'));
		$form->setTitle($this->getPlugin()->txt("sco_scoring_form"));

		//Current points field
		$current_question_points = new ilNonEditableValueGUI($this->getPlugin()->txt("sco_current_scoring_form_input"), 'current_scoring');
		$current_question_points->setValue($this->getQuestionPoints());
		$current_question_points->setInfo($this->getPlugin()->txt("sco_current_scoring_info"));
		$form->addItem($current_question_points);

		//New points field
		$new_question_points = new ilTextInputGUI($this->getPlugin()->txt("sco_new_scoring_form_input"), 'new_scoring');
		$new_question_points->setValue($new_question_points_value);
		$new_question_points->setInfo($this->getPlugin()->txt("sco_new_scoring_info"));
		$form->addItem($new_question_points);

		//2.3.9 Show info about behaviour of this scoring page
		$scoring_info = new ilNonEditableValueGUI("", "scoring_info");
		$scoring_info->setValue($this->getPlugin()->txt("sco_info"));
		$form->addItem($scoring_info);

		//This command is used when the user want to show a comparison but no to set the input as point value.
		$form->addCommandButton("showScoringComparison", $this->getPlugin()->txt("sco_show_new_scoring_form_button"));
		//This command is used when the wants to set the input value as point value.
		$form->addCommandButton("saveNewScoring", $this->getPlugin()->txt("sco_set_new_scoring_form_button"));
		$form->setShowTopButtons(FALSE);

		return $form;
	}

	/**
	 * Fill PRT part
	 * @param string $mode
	 */
	private function fillPRTspecific($mode)
	{
		if ($mode == 'current') {
			//Fill the current PRT info.
			foreach ($this->getPotentialresponseTrees() as $prt_name => $prt) {
				$this->getTemplate()->setCurrentBlock('prt_part');
				$this->getTemplate()->setVariable('PRT_NAME_MESSAGE', $this->getPlugin()->txt('sco_prt_name'));
				$this->getTemplate()->setVariable('PRT_NAME', $prt_name);
				$this->getTemplate()->setVariable('PRT_POINTS_MESSAGE', $this->getPlugin()->txt('sco_prt_value'));
				$this->getTemplate()->setVariable('PRT_POINTS', $prt['max_points']);
				unset($prt['max_points']);
				//Fill nodes
				foreach ($prt as $node_name => $node) {
					$this->getTemplate()->setCurrentBlock('node_part');
					$this->fillNodeSpecific($mode, $node_name, $node);
					$this->getTemplate()->ParseCurrentBlock();
				}
				$this->getTemplate()->setCurrentBlock('prt_part');
				$this->getTemplate()->ParseCurrentBlock();
			}
		} elseif ($mode == 'new') {
			//Fill the new PRT info in order to compare it with current one.
			foreach ($this->getPotentialresponseTrees() as $prt_name => $prt) {
				$this->getTemplate()->setCurrentBlock('n_prt_part');
				$this->getTemplate()->setVariable('N_PRT_NAME_MESSAGE', $this->getPlugin()->txt('sco_prt_name'));
				$this->getTemplate()->setVariable('N_PRT_NAME', $prt_name);
				$this->getTemplate()->setVariable('N_PRT_POINTS_MESSAGE', $this->getPlugin()->txt('sco_prt_value'));
				$this->getTemplate()->setVariable('N_PRT_POINTS', $prt['max_points']);
				unset($prt['max_points']);
				//Fill Nodes
				foreach ($prt as $node_name => $node) {
					$this->getTemplate()->setCurrentBlock('n_node_part');
					$this->fillNodeSpecific($mode, $node_name, $node);
					$this->getTemplate()->ParseCurrentBlock();
				}
				$this->getTemplate()->setCurrentBlock('n_prt_part');
				$this->getTemplate()->ParseCurrentBlock();
			}
		}
	}

	/**
	 * Fill node specific part
	 * @param string $mode
	 * @param string $node_name
	 * @param array $node
	 */
	private function fillNodeSpecific($mode, $node_name, $node)
	{
		if ($mode == 'current') {
			//Fill the current node info.
			$this->getTemplate()->setVariable('NODE_NAME_MESSAGE', $this->getPlugin()->txt('sco_node_name'));
			$this->getTemplate()->setVariable('NODE_NAME', $node_name);
			$this->getTemplate()->setVariable('TRUE_SCORING', $node['true_mode'] . $node['true_value']);
			$this->getTemplate()->setVariable('FALSE_SCORING', $node['false_mode'] . $node['false_value']);
		} elseif ($mode == 'new') {
			//Fill the new node info in order to compare it with current one.
			$this->getTemplate()->setVariable('N_NODE_NAME_MESSAGE', $this->getPlugin()->txt('sco_node_name'));
			$this->getTemplate()->setVariable('N_NODE_NAME', $node_name);
			$this->getTemplate()->setVariable('N_TRUE_SCORING', $node['true_mode'] . $node['true_value']);
			$this->getTemplate()->setVariable('N_FALSE_SCORING', $node['false_mode'] . $node['false_value']);
		}
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param \ilassStackQuestionPlugin $plugin
	 */
	public function setPlugin($plugin)
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
	 * @param mixed $potentialresponse_trees
	 */
	public function setPotentialresponseTrees($potentialresponse_trees)
	{
		$this->potentialresponse_trees = $potentialresponse_trees;
	}

	/**
	 * @return mixed
	 */
	public function getPotentialresponseTrees()
	{
		return $this->potentialresponse_trees;
	}

	/**
	 * @param \ilTemplate $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * @return \ilTemplate
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param float $question_points
	 */
	public function setQuestionPoints($question_points)
	{
		$this->question_points = $question_points;
	}

	/**
	 * @return float
	 */
	public function getQuestionPoints()
	{
		return $this->question_points;
	}

} 