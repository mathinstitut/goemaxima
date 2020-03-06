<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/FormProperties/class.ilMultipartFormPropertyGUI.php';

/**
 * Tabs property GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilTabsFormPropertyGUI extends ilMultipartFormPropertyGUI
{

	/**
	 * @var ilTemplate
	 */
	private $template;

	function __construct($a_title = "", $a_postvar = "", $a_container_width = "", $a_show_title = "")
	{
		$a_title = "";
		parent::__construct($a_title, $a_postvar, $a_container_width, $a_show_title);

		$this->setHiddenTitle("Title");
		//Set template for accordion
		$template = new ilTemplate('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/tpl.tabs_form_property.html', TRUE, TRUE);
		$this->setTemplate($template);
	}

	/**
	 * @return HTML for this form property
	 */
	protected function render()
	{
		$this->getTemplate()->setVariable("CONTAINER_WIDTH", $this->getContainerWidth());

		$active = TRUE;

		//Fill tab headers
		$this->getTemplate()->setVariable("PROPERTY_NAME", $this->getTitle());
		foreach ($this->getParts() as $index => $part) {
			$this->getTemplate()->setCurrentBlock('tabs_titles');
			$this->getTemplate()->setVariable("PART_TYPE", $part->getType());
			$this->getTemplate()->setVariable("PART_TITLE", $part->getTitle());
			if ($active) {
				$this->getTemplate()->setVariable("CLASS_ACTIVE", 'class="active"');
				$active = FALSE;
			}
			$this->getTemplate()->parseCurrentBlock();
		}

		$active = TRUE;

		//Fill tabs content
		foreach ($this->getParts() as $index => $part) {

			$this->getTemplate()->setCurrentBlock('tab_content');
			$this->getTemplate()->setVariable("CONTAINER_WIDTH", $this->getContainerWidth());
			$this->getTemplate()->setVariable("PART_TYPE_CONTENT", $part->getType());

			if ($active) {
				$this->getTemplate()->setVariable("ACTIVE", 'active');
				$active = FALSE;
			}
			//Addition of form properties
			foreach ($part->getContent() as $form_property) {

				//Fill Title and Info
				$this->getTemplate()->setCurrentBlock('prop_container');
				//Set width
				$this->getTemplate()->setVariable("TITLE_WIDTH", $this->getWidthDivision('title'));
				$this->getTemplate()->setVariable("CONTENT_WIDTH", $this->getWidthDivision('content'));
				$this->getTemplate()->setVariable("FOOTER_WIDTH", $this->getWidthDivision('footer'));

				if ($this->getShowTitle())
				{
					if ($form_property->getRequired())
					{
						$this->getTemplate()->setVariable("PROP_TITLE", $form_property->getTitle() . "<font color=\"red\"> *</font>");
					} else
					{
						$this->getTemplate()->setVariable("PROP_TITLE", $form_property->getTitle());
					}
				}

				//Fill content
				$form_property->insert($this->getTemplate());
				$this->getTemplate()->setCurrentBlock('prop_container');
				//Set width
				$this->getTemplate()->setVariable("TITLE_WIDTH", $this->getWidthDivision('title'));
				$this->getTemplate()->setVariable("CONTENT_WIDTH", $this->getWidthDivision('content'));
				$this->getTemplate()->setVariable("FOOTER_WIDTH", $this->getWidthDivision('footer'));
				$this->getTemplate()->parseCurrentBlock();
			}
			$this->getTemplate()->setCurrentBlock('tab_content');
			$this->getTemplate()->parseCurrentBlock();
		}

		return $this->getTemplate()->get();
	}

	/*
	 * GETTERS AND SETTERS
	 */

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

}