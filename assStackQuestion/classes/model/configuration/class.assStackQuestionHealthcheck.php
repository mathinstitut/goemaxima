<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionUtils.php';
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionStackFactory.php';
require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionInitialization.php';


/**
 * STACK Question Healthcheck
 * This class checks that all parameters are properly set for running a STACK Question
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version    $Id: 2.3$$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionHealthcheck
{
	/**
	 * Plugin instance for templates and language management
	 * @var ilassStackQuestionPlugin
	 */
	private $plugin;

	/**
	 * @var assStackQuestionStackFactory the clas for create stack objects
	 */
	private $stack_factory;

	/**
	 * @var mixed The current status of the maxima connection
	 */
	private $maxima_connection_status;


	function __construct(ilassStackQuestionPlugin $plugin)
	{
		//Set plugin object
		$this->setPlugin($plugin);

		//Create STACK factory
		$this->setStackFactory(new assStackQuestionStackFactory());
	}

	public function doHealthcheck()
	{
		global $tpl;
		//Include all classes needed
		$this->getPlugin()->includeClass('utils/class.assStackQuestionInitialization.php');
		$this->getPlugin()->includeClass('../exceptions/class.assStackQuestionException.php');

		$this->checkMaximaConnection();

		//Add MathJax (Ensure MathJax is loaded)
		include_once "./Services/Administration/classes/class.ilSetting.php";
		$mathJaxSetting = new ilSetting("MathJax");
		$tpl->addJavaScript($mathJaxSetting->get("path_to_mathjax"));
		//Ad CSS to Templates
		$tpl->addCss($this->getPlugin()->getStyleSheetLocation('css/qpl_xqcas_healthcheck.css'));

		return $this->getMaximaConnectionStatus();
	}

	public function checkMaximaConnection()
	{
		global $CFG;
		$this->getPlugin()->includeClass('stack/mathsoutput/mathsoutput.class.php');
		$this->getPlugin()->includeClass('stack/cas/castext.class.php');

		//Check LaTeX is being converted correctly
		$this->setMaximaConnectionStatus('<b>' . html_writer::tag('p', stack_string('healthchecklatex')) . '</b>', 'healthchecklatex');
		//healthcheckmathsdisplaymethod
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthcheckmathsdisplaymethod', stack_maths::configured_output_name())), 'healthcheckmathsdisplaymethod');
		//healthchecklatexintro
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthchecklatexintro')), 'healthchecklatexintro');
		//texdisplaystyle
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('texdisplaystyle')), 'texdisplaystyle');
		//healthchecksampledisplaytex
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthchecksampledisplaytex')), 'healthchecksampledisplaytex');
		//texinlinestyle
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('texinlinestyle')), 'texinlinestyle');
		//healthchecksampleinlinetex
		$this->setMaximaConnectionStatus(html_writer::tag('p', assStackQuestionUtils::_solveKeyBracketsBug(stack_string('healthchecksampleinlinetex'))), 'healthchecksampleinlinetex');
		//healthchecklatexmathjax
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthchecklatexmathjax')), 'healthchecklatexmathjax');

		//Maxima configuration file
		// Try to list available versions of Maxima (linux only, without the DB).
		if ($this->config->platform !== 'win')
		{
			$connection = stack_connection_helper::make();
			if (is_a($connection, 'stack_cas_connection_unix'))
			{
				$this->setMaximaConnectionStatus('<b>' . html_writer::tag('pre', $connection->get_maxima_available()) . '</b>', 'pre');
			}
		}
		//Check for location of Maxima.
		$maximalocation = stack_cas_configuration::confirm_maxima_win_location();
		if ('' != $maximalocation)
		{
			$message = stack_string('healthcheckconfigintro1') . ' ' . html_writer::tag('tt', $maximalocation);
			$this->setMaximaConnectionStatus('<b>' . html_writer::tag('p', $message) . '</b>', 'healthcheckconfigintro1');
		}
		//Check if the current options for library packages are permitted (maximalibraries).
		list($valid, $message) = stack_cas_configuration::validate_maximalibraries();
		if (!$valid)
		{
			$this->setMaximaConnectionStatus('<b>' . html_writer::tag('p', $message) . '</b>', 'validatemaximalibraries');
		}
		//Try to connect to create maxima local.
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthcheckconfigintro2')), 'healthcheckconfigintro2');
		//Create maximalocal
		stack_cas_configuration::create_maximalocal();

		$this->setMaximaConnectionStatus(html_writer::tag('textarea', stack_cas_configuration::generate_maximalocal_contents(), array('readonly' => 'readonly', 'wrap' => 'virtual', 'rows' => '32', 'cols' => '100')), 'generatemaximalocalcontents');

		// Maxima config.
		if (stack_cas_configuration::maxima_bat_is_missing())
		{
			$message = stack_string('healthcheckmaximabatinfo', $CFG->dataroot);
			$this->setMaximaConnectionStatus(html_writer::tag('p', $message), 'healthcheckmaximabatinfo');
		}
		// Test an *uncached* call to the CAS.  I.e. a genuine call to the process.
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthuncachedintro')), 'healthuncachedintro');
		list($message, $genuinedebug, $result) = stack_connection_helper::stackmaxima_genuine_connect();
		$this->setMaximaConnectionStatus($result, 'healthuncachedresult');
		$this->setMaximaConnectionStatus(html_writer::tag('p', $message), 'healthuncachedmessage');
		$this->setMaximaConnectionStatus($this->output_debug(stack_string('debuginfo'), $genuinedebug), 'debuginfo');
		$genuinecascall = $result;
		// Test Maxima connection.
		//// Intentionally use get_string for the sample CAS and plots, so we don't render
		/// // the maths too soon.
		$healthcheckconnect = $this->showCASText(stack_string('healthchecksamplecas'));
		if (is_array($healthcheckconnect))
		{
			foreach ($healthcheckconnect as $name => $item)
			{
				$this->setMaximaConnectionStatus($item, 'healthcheckconnect' . $name);
			}
		}

		// If we have a linux machine, and we are testing the raw connection then we should
		//// attempt to automatically create an optimized maxima image on the system.
		if ($this->config->platform === 'unix' and $genuinecascall)
		{
			$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string('healthautomaxoptintro')), 'healthautomaxoptintro');
			list($message, $debug, $result, $commandline) = stack_connection_helper::stackmaxima_auto_maxima_optimise($genuinedebug);#
			$this->setMaximaConnectionStatus(html_writer::tag('p', $message), 'healthautomaxoptintromessage');
			$this->setMaximaConnectionStatus($this->output_debug(stack_string('debuginfo'), $debug), 'healthautomaxoptintrodebug');
			$this->setMaximaConnectionStatus($result, 'healthautomaxoptintroresult');
		}
		// Test the version of the STACK libraries that Maxima is using.
		// When Maxima is being run pre-compiled (maxima-optimise) or on a server,
		// it is possible for the version of the Maxima libraries to get out of synch
		// with the qtype_stack code.
		list($message, $details, $result) = stack_connection_helper::stackmaxima_version_healthcheck();
		$this->setMaximaConnectionStatus(html_writer::tag('p', stack_string($message, $details)), 'maximaversionhealthcheckmessage');

		// Test plots.

		$healthcheckplotsintro = $this->output_cas_text(stack_string('healthcheckplots'), stack_string('healthcheckplotsintro'), stack_string('healthchecksampleplots'));

		if (is_array($healthcheckplotsintro))
		{
			foreach ($healthcheckplotsintro as $name => $item)
			{
				$this->setMaximaConnectionStatus($item, 'healthcheckplotsintro' . $name);
			}
		}

		// State of the cache.
		$message = stack_string('healthcheckcache_' . $this->config->casresultscache);
		$this->setMaximaConnectionStatus(html_writer::tag('p', $message), 'healthcheckcache');
	}

	public function showCASText($cas_text)
	{
		$this->getPlugin()->includeClass('stack/mathsoutput/mathsoutput.class.php');
		$CAS_text_content = array();
		//Create CAS text
		$ct = $this->getStackFactory()->get('cas_text', array('raw' => $cas_text));
		//Set content to array
		$CAS_text_content['display'] = assStackQuestionUtils::_solveKeyBracketsBug(stack_maths::process_display_castext($ct["text"]));
		$CAS_text_content['errors'] = $ct["errors"];
		$CAS_text_content['debug_info'] = $ct["debug"];

		return $CAS_text_content;
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
	 * @param \assStackQuestionStackFactory $stack_factory
	 */
	public function setStackFactory($stack_factory)
	{
		$this->stack_factory = $stack_factory;
	}

	/**
	 * @return \assStackQuestionStackFactory
	 */
	public function getStackFactory()
	{
		return $this->stack_factory;
	}

	/**
	 * @param mixed $maxima_connection_status
	 */
	public function setMaximaConnectionStatus($maxima_connection_status, $selector = '')
	{
		if ($selector)
		{
			$this->maxima_connection_status[$selector] = $maxima_connection_status;
		} else
		{
			$this->maxima_connection_status = $maxima_connection_status;
		}
	}

	/**
	 * @return mixed
	 */
	public function getMaximaConnectionStatus($selector = '')
	{
		if ($selector)
		{
			return $this->maxima_connection_status[$selector];
		} else
		{
			return $this->maxima_connection_status;
		}
	}

	public function clearCache()
	{
		global $DIC;
		$db = $DIC->database();
		$query = "TRUNCATE table xqcas_cas_cache";
		$db->manipulate($query);

		return TRUE;
	}

	function output_cas_text($title, $intro, $castext)
	{
		if (is_string($castext))
		{
			global $OUTPUT;
			$summary['intro'] = html_writer::tag('p', $intro);
			$summary['castext'] = html_writer::tag('pre', s($castext));

			$ct = new stack_cas_text($castext, null, 0, 't');

			$summary['display'] = html_writer::tag('p', stack_maths::process_display_castext($ct->get_display_castext()));
			$summary['errors'] = $this->output_debug(stack_string('errors'), $ct->get_errors());
			$summary['debug'] = $this->output_debug(stack_string('debuginfo'), $ct->get_debuginfo());

			return $summary;
		} else
		{
			return array();
		}

	}

	function output_debug($title, $message)
	{
		global $OUTPUT;

		if (!$message)
		{
			return;
		}

		return $title . $message;
	}
}