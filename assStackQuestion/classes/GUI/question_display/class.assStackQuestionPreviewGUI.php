<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
require_once './Modules/TestQuestionPool/classes/class.ilAssQuestionPreviewGUI.php';


/**
 * STACK Question PREVIEW of question GUI class
 * This class provides a view for the preview of a specific STACK Question when not in a test
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionPreviewGUI extends ilAssQuestionPreviewGUI
{
	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var ilTemplate for showing the preview
	 */
	private $template;

	/**
	 * @var array with the data from assStackQuestionPreview
	 */
	private $preview;


	/**
	 * Set all the data needed for call the getQuestionPreviewGUI() method.
	 * @param ilassStackQuestionPlugin $plugin
	 * @param array $preview_data
	 */
	function __construct(ilassStackQuestionPlugin $plugin, $preview_data)
	{
		//Set plugin object
		$this->setPlugin($plugin);

		//Set template for preview
		$this->setTemplate($this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_question_preview.html'));
		//Add CSS to the template
		$this->getTemplate()->addCss($this->getPlugin()->getStyleSheetLocation('css/qpl_xqcas_question_preview.css'));

		//Add MathJax (Ensure MathJax is loaded)
		include_once "./Services/Administration/classes/class.ilSetting.php";
		$mathJaxSetting = new ilSetting("MathJax");
		$this->getTemplate()->addJavaScript($mathJaxSetting->get("path_to_mathjax"));
		//Set preview data
		$this->setPreview($preview_data);
	}

	/**
	 * ### MAIN METHOD OF THIS CLASS ###
	 * This method is called from assStackQuestionGUI to get the question Preview HTML.
	 * @return ilTemplate the STACK Question preview HTML
	 */
	public function getQuestionPreviewGUI($showInlineFeedback = FALSE)
	{
		//Step 1 Prepare Display GUI
		$display_gui = $this->prepareDisplayGUI();

		$feedback_gui = '';

		//Step 3: Fill the template with data from preview
		$this->fillTemplate($display_gui, $feedback_gui);

		//Step 4: Returns the template with filled data
		return $this->getTemplate();
	}

	public function getBestSolutionPreviewGUI($graphicalOutput = FALSE){
		$this->getPlugin()->includeClass('GUI/question_display/class.assStackQuestionFeedbackGUI.php');
		$feedback_gui = new assStackQuestionFeedbackGUI($this->getPlugin(), $this->getPreview('question_feedback'), TRUE);
		return $feedback_gui->getBestSolutionGUI();
	}

	/**
	 * Gets the Question display GUI
	 * @return ilTemplate
	 */
	private function prepareDisplayGUI()
	{
		$this->getPlugin()->includeClass('GUI/question_display/class.assStackQuestionDisplayGUI.php');
		$display_gui = new assStackQuestionDisplayGUI($this->getPlugin(), $this->getPreview('question_display'));
		return $display_gui->getQuestionDisplayGUI(TRUE);
	}

	/**
	 * Gets the Question feedback GUI
	 * @return ilTemplate
	 */
	private function prepareFeedbackGUI()
	{
		$this->getPlugin()->includeClass('GUI/question_display/class.assStackQuestionFeedbackGUI.php');
		$feedback_gui = new assStackQuestionFeedbackGUI($this->getPlugin(), $this->getPreview('question_feedback'), TRUE);
		return $feedback_gui->getFeedbackGUI();
	}

	/**
	 * Fills the template for question preview
	 */
	private function fillTemplate($display_gui, $feedback_gui = '')
	{
		//Part 1: Question display
		$this->getTemplate()->setVariable('QUESTION_DISPLAY', $display_gui->get());

		//Part 3: Question feedback when existing.
		if ($feedback_gui) {
			$this->getTemplate()->setVariable('QUESTION_FEEDBACK', $feedback_gui->get());

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
	 * @param ilTemplate $template
	 */
	public function setTemplate(ilTemplate $template)
	{
		$this->template = $template;
	}

	/**
	 * @return ilTemplate
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param array $preview
	 */
	public function setPreview($preview)
	{
		$this->preview = $preview;
	}

	/**
	 * @return array OR HTML
	 */
	public function getPreview($selector = '')
	{
		if ($selector) {
			return $this->preview[$selector];
		} else {
			return $this->preview;
		}
	}

} 