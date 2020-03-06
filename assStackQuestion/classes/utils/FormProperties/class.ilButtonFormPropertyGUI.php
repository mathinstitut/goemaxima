<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * Button form property GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.0$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class ilButtonFormProperty extends ilFormPropertyGUI
{
	protected $template;

	protected $value;

	protected $command;

	protected $action;


	function __construct($a_title = "", $a_postvar = "")
	{
		parent::__construct($a_title, $a_postvar);

		//Set template for button
		$template = new ilTemplate('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/tpl.button_form_property.html', TRUE, TRUE);
		$this->setTemplate($template);
	}

	/**
	 * Insert property html
	 *
	 * @return    int    Size
	 */
	function insert(&$a_tpl)
	{
		$html = $this->render();

		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $html);
		$a_tpl->parseCurrentBlock();
	}

	/**
	 * Insert property html
	 */
	function render()
	{
		$this->getTemplate()->setCurrentBlock("prop_button");
		$this->getTemplate()->setVariable("BUTTON_TYPE", "delete_node");
		$this->getTemplate()->setVariable("BUTTON_TITLE", $this->getTitle());
		if ($this->getAction()) {
			$this->getTemplate()->setVariable("ACTION", "[" . $this->getAction() . "]");
		}
		if ($this->getCommand()) {
			$this->getTemplate()->setVariable("COMMAND", "[" . $this->getCommand() . "]");
		}
		$this->getTemplate()->parseCurrentBlock();

		return $this->getTemplate()->get();
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * @return mixed
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param mixed $command
	 */
	public function setCommand($command)
	{
		$this->command = $command;
	}

	/**
	 * @return mixed
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * @param mixed $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @return mixed
	 */
	public function getAction()
	{
		return $this->action;
	}


}