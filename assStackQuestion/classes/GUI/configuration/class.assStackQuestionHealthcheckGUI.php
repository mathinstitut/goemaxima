<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';

/**
 * STACK Question Healthcheck GUI
 * This class provides a GUI to the Healthcheck of the STACK Question plugin
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionHealthcheckGUI
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
	 * @var array with the data from assStackQuestionHealthcheck
	 */
	private $healthcheck_data;

	/**
	 * Set all the data needed for call the getQuestionDisplayGUI() method.
	 * @param ilassStackQuestionPlugin $plugin
	 * @param array $healthcheck_data
	 */
	function __construct(ilassStackQuestionPlugin $plugin, $healthcheck_data)
	{
		global $tpl;
		//Set plugin object
		$this->setPlugin($plugin);

		//Add MathJax (Ensure MathJax is loaded)
		include_once "./Services/Administration/classes/class.ilSetting.php";
		$mathJaxSetting = new ilSetting("MathJax");
		$tpl->addJavaScript($mathJaxSetting->get("path_to_mathjax"));

		$this->setHealthcheckData($healthcheck_data);
	}


	public function showHealthCheck()
	{
		$connection_status_template = $this->getPlugin()->getTemplate('tpl.il_as_qpl_xqcas_healthcheck.html');

		foreach ($this->getHealthcheckData() as $healthcheckDataid => $value)
		{
			$connection_status_template->setCurrentBlock('healthcheck_property');
			$connection_status_template->setVariable('PROPERTY', $value);
			$connection_status_template->ParseCurrentBlock();
		}

		return $connection_status_template;
	}


	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param array $healthcheck_data
	 */
	public function setHealthcheckData($healthcheck_data)
	{
		$this->healthcheck_data = $healthcheck_data;
	}

	/**
	 * @return array
	 */
	public function getHealthcheckData($selector = '')
	{
		if ($selector)
		{
			return $this->healthcheck_data[$selector];
		} else
		{
			return $this->healthcheck_data;
		}
	}

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