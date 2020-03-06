<?php

/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */
require_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

/**
 * STACK Question plugin config GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id$
 *
 */
class ilassStackQuestionConfigGUI extends ilPluginConfigGUI
{
    /** @var assStackQuestionConfig */
    protected $config;


    /** @var ilassStackQuestionPlugin */
    protected $plugin_object = null;

	/**
	 *
	 * @param string $cmd
	 */
	public function performCommand($cmd)
	{
		global $DIC;

		//Set config object
		$this->plugin_object->includeClass("model/configuration/class.assStackQuestionConfig.php");
		$this->config = new assStackQuestionConfig($this->plugin_object);

		// control flow
		$ctrl = $DIC->ctrl();
		$cmd = $ctrl->getCmd($this, "configure");
		switch ($cmd)
		{
		    case 'configure';
            case 'showConnectionSettings':
            case 'saveConnectionSettings':
            case 'showServerList':
            case 'addServer':
            case 'editServer':
            case 'saveConnectionSettings':
            case 'saveServerSettings':
            case 'confirmDeleteServers':
                $this->initTabs('show_connection_settings');
                break;

			case 'showOtherSettings':
			case 'showDisplaySettings':
			case 'showDefaultOptionsSettings':
			case 'showDefaultInputsSettings':
			case 'showDefaultPRTsSettings':
			case 'saveDisplaySettings':
			case 'saveDefaultInputsSettings':
			case 'saveDefaultOptionsSettings':
			case 'saveDefaultPRTsSettings':
			case 'setDefaultSettingsForDisplay':
			case 'setDefaultSettingsForInputs':
			case 'setDefaultSettingsForOptions':
			case 'setDefaultSettingsForPRTs':
				$this->initTabs('show_other_settings');
				break;

			case 'showHealthcheck':
            case 'runHealthcheck':
				$this->initTabs('show_healthcheck');
				break;
		}

        $this->$cmd();
	}

	/**
     * Init the tabs
	 * @param string $a_active  id of the active tab (activates it an adds its sub tabs)
	 */
	public function initTabs($a_active = "")
	{
		global $DIC;
		$ctrl = $DIC->ctrl();
		$tabs = $DIC->tabs();

        $tabs->addTab("show_connection_settings", $this->plugin_object->txt('show_connection_settings'), $ctrl->getLinkTarget($this, 'showConnectionSettings'));
        $tabs->addTab("show_other_settings", $this->plugin_object->txt('show_other_settings'), $ctrl->getLinkTarget($this, 'showOtherSettings'));
        $tabs->addTab("show_healthcheck", $this->plugin_object->txt('show_healthcheck'), $ctrl->getLinkTarget($this, 'showHealthcheck'));

        $tabs->activateTab($a_active);

        switch ($a_active)
		{
            case 'show_connection_settings':
                $tabs->addSubTab('basic_connection_settings', $this->plugin_object->txt('basic_connection_settings'), $ctrl->getLinkTarget($this, 'showConnectionSettings'));
                if ($this->config->get('platform_type') == 'server')
                {
                    $tabs->addSubTab('server_configuration', $this->plugin_object->txt('server_configuration'), $ctrl->getLinkTarget($this, 'showServerList'));
                }
                break;

			case 'show_other_settings':
				$tabs->addSubTab('show_display_settings', $this->plugin_object->txt('show_display_settings'), $ctrl->getLinkTargetByClass('ilassStackQuestionConfigGUI', 'showDisplaySettings'));
				$tabs->addSubTab('show_default_options_settings', $this->plugin_object->txt('show_default_options_settings'), $ctrl->getLinkTargetByClass('ilassStackQuestionConfigGUI', 'showDefaultOptionsSettings'));
				$tabs->addSubTab('show_default_inputs_settings', $this->plugin_object->txt('show_default_inputs_settings'), $ctrl->getLinkTargetByClass('ilassStackQuestionConfigGUI', 'showDefaultInputsSettings'));
				$tabs->addSubTab('show_default_prts_settings', $this->plugin_object->txt('show_default_prts_settings'), $ctrl->getLinkTargetByClass('ilassStackQuestionConfigGUI', 'showDefaultPRTsSettings'));
				break;
		}
	}

	/**
	 * Entry point for configuring the module
	 */
	function configure()
	{
		//By default show connection settings
		$this->showConnectionSettings();
	}

	/*
	 * SHOW SETTINGS CALLING METHODS
	 */

	public function showConnectionSettings()
	{
        global $DIC, $tpl;
        $tabs = $DIC->tabs();
        $tabs->activateSubTab('basic_connection_settings');

		$form = $this->getConnectionSettingsForm();
		$tpl->setContent($form->getHTML());
	}

	public function showServerList()
    {
        global $DIC, $tpl;
        $DIC->tabs()->activateSubTab('server_configuration');

        $button = ilLinkButton::getInstance();
        $button->setCaption($this->plugin_object->txt('add_server'), false);
        $button->setUrl($DIC->ctrl()->getLinkTarget($this, 'addServer'));
        $DIC->toolbar()->addButtonInstance($button);

        $this->plugin_object->includeClass('GUI/tables/class.assStackQuestionServerTableGUI.php');
        $table = new assStackQuestionServerTableGUI($this, 'showServerList');
        $tpl->setContent($table->getHTML());
    }

    public function addServer()
    {
        global $DIC, $tpl;
        $tabs = $DIC->tabs();
        $tabs->activateSubTab('server_configuration');

        $form = $this->getServerSettingsForm();
        $tpl->setContent($form->getHTML());
    }

    public function editServer()
    {
        global $DIC, $tpl;
        $tabs = $DIC->tabs();
        $tabs->activateSubTab('server_configuration');

        $DIC->ctrl()->setParameter($this, 'server_id', $_GET['server_id']);
        $button = ilLinkButton::getInstance();
        $button->setCaption($this->plugin_object->txt('show_healthcheck'), false);
        $button->setUrl($DIC->ctrl()->getLinkTarget($this, 'runHealthcheck'));
        $DIC->toolbar()->addButtonInstance($button);

        $form = $this->getServerSettingsForm($_GET['server_id']);
        $tpl->setContent($form->getHTML());
    }


    public function activateServers()
    {
        $this->changeServerActivation(true);
    }

    public function deactivateServers()
    {
        $this->changeServerActivation(false);
    }

    protected function changeServerActivation($active)
    {
        global $DIC;

        $this->plugin_object->includeClass("model/configuration/class.assStackQuestionServer.php");

        if (isset($_POST['server_id']))
        {
            $server_ids = (array) $_POST['server_id'];
        }
        elseif (isset($_GET['server_id']))
        {
            $server_ids = (array) $_GET['server_id'];
        }

        if (empty($server_ids))
        {
            ilUtil::sendFailure($this->plugin_object->txt('no_server_selected'), true);
        }
        else
        {
            foreach ($server_ids as $server_id)
            {
                $server = assStackQuestionServer::getServerById($server_id);
                $server->setActive($active);
            }
            assStackQuestionServer::saveServers();

            if (count($server_ids) == 1)
            {
                ilUtil::sendSuccess($this->plugin_object->txt($active ? 'server_activated' : 'server_deactivated'), true);
            }
            else
            {
                ilUtil::sendSuccess($this->plugin_object->txt($active ? 'servers_activated' : 'servers_deactivated'), true);
            }
        }
        $DIC->ctrl()->redirect($this, 'showServerList');
    }

    public function confirmDeleteServers()
    {
        global $DIC, $tpl;

        $this->plugin_object->includeClass("model/configuration/class.assStackQuestionServer.php");

        if (isset($_POST['server_id']))
        {
            $server_ids = (array) $_POST['server_id'];
        }
        elseif (isset($_GET['server_id']))
        {
            $server_ids = (array) $_GET['server_id'];
        }

        if (empty($server_ids))
        {
            ilUtil::sendFailure($this->plugin_object->txt('no_server_selected'), true);
            $DIC->ctrl()->redirect($this, 'showServerList');
        }

        $gui = new ilConfirmationGUI();
        $gui->setHeaderText($this->plugin_object->txt('confirm_delete_servers'));
        $gui->setFormAction($DIC->ctrl()->getFormAction($this));
        $gui->setConfirm($DIC->language()->txt('delete'), 'deleteServers');
        $gui->setCancel($DIC->language()->txt('cancel'), 'showServerList');

        foreach ($server_ids as $server_id)
        {
            $server = assStackQuestionServer::getServerById($server_id);
            $gui->addItem('server_id[]', $server_id, $server->getAddress());
        }
        $tpl->setContent($gui->getHTML());
    }

    public function deleteServers()
    {
        global $DIC;
        $this->plugin_object->includeClass("model/configuration/class.assStackQuestionServer.php");

        $server_ids = (array) $_POST['server_id'];
        assStackQuestionServer::deleteServers($server_ids);

        ilUtil::sendSuccess($this->plugin_object->txt(count($server_ids) == 1 ? 'server_deleted' : 'servers_deleted'), true);
        $DIC->ctrl()->redirect($this, 'showServerList');
    }


    public function showOtherSettings()
	{
		global $DIC;
		$tabs = $DIC->tabs();
		$tabs->activateSubTab('show_display_settings');

		$this->showDisplaySettings();
	}

	public function showDisplaySettings()
	{
		global $DIC, $tpl;
		$tabs = $DIC->tabs();
		$tabs->activateSubTab('show_display_settings');

		$form = $this->getDisplaySettingsForm();
		$tpl->setContent($form->getHTML());
	}

	public function showDefaultOptionsSettings()
	{
		global $DIC, $tpl;
		$tabs = $DIC->tabs();
		$tabs->activateSubTab('show_default_options_settings');

		$form = $this->getDefaultOptionsSettingsForm();
		$tpl->setContent($form->getHTML());
	}

	public function showDefaultInputsSettings()
	{
		global $DIC, $tpl;
		$tabs = $DIC->tabs();
		$tabs->activateSubTab('show_default_inputs_settings');

		$form = $this->getDefaultInputsSettingsForm();
		$tpl->setContent($form->getHTML());
	}

	public function showDefaultPRTsSettings()
	{
		global $DIC, $tpl;
		$tabs = $DIC->tabs();
		$tabs->activateSubTab('show_default_prts_settings');

		$form = $this->getDefaultPRTSettingsForm();
		$tpl->setContent($form->getHTML());
	}


	/**
	 * Show the healthcheck screen
	 * @param bool $a_run   run the healthcheck
	 */
	public function showHealthcheck($a_run = false)
	{
		global $DIC, $tpl;

		$toolbar = new ilToolbarGUI();
		$ctrl = $DIC->ctrl();

		$ctrl->saveParameter($this, 'server_id');
		$toolbar->setFormAction($ctrl->getFormAction($this));

		$healthcheck_reduced_button = ilSubmitButton::getInstance();
		$healthcheck_reduced_button->setCaption($this->plugin_object->txt("healthcheck_reduced"), FALSE);
		$healthcheck_reduced_button->setCommand("runHealthcheck");
		$toolbar->addButtonInstance($healthcheck_reduced_button);

		$clear_cache_button = ilSubmitButton::getInstance();
		$clear_cache_button->setCaption($this->plugin_object->txt("clear_cache"), FALSE);
		$clear_cache_button->setCommand("clearCache");
		$toolbar->addButtonInstance($clear_cache_button);

		if ($a_run)
		{
		    if ($this->config->get('platform_type') == 'server')
            {
                ilUtil::sendInfo($this->plugin_object->txt('srv_address') . ':<br/>'.  assStackQuestionConfig::_getServerAddress());
            }

			//Create Healthcheck
			$this->plugin_object->includeClass("model/configuration/class.assStackQuestionHealthcheck.php");
			$healthcheck_object = new assStackQuestionHealthcheck($this->plugin_object);

			try
			{
				$healthcheck_data = $healthcheck_object->doHealthcheck();
			} catch (Exception $e)
			{
				ilUtil::sendFailure($e->getMessage());
				$healthcheck_data = false;
			}

			if ($healthcheck_data)
			{
				//Show healthcheck
				$this->plugin_object->includeClass("GUI/configuration/class.assStackQuestionHealthcheckGUI.php");
				$healthcheck_gui_object = new assStackQuestionHealthcheckGUI($this->plugin_object, $healthcheck_data);
				$healthcheck_gui = $healthcheck_gui_object->showHealthcheck();
				$result_html = $healthcheck_gui->get();
			}
		}

		$tpl->setContent($toolbar->getHTML() . $result_html);
	}

    /**
     * Run a healthcheck
     */
    public function runHealthcheck()
    {
        $this->showHealthcheck(true);
    }

	/*
	 * FORMS CREATION METHODS
	 */

	public function getConnectionSettingsForm()
	{
		global $DIC;
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormAction($this));

		//Values from DB
		$connection_data = assStackQuestionConfig::_getStoredSettings('connection');

		//Platform selection

		//IF AUTOMATIC DETECTION IS ACTIVATED
		/*
		$platform_type = new ilNonEditableValueGUI($this->plugin_object->txt('platform_type'), 'platform_type');
		$platform_type->setInfo($this->plugin_object->txt('platform_type_info'));
		$platform_type->setValue($connection_data['platform_type']);
		$form->addItem($platform_type);
		*/

		//IF MANUAL SELECTION ACTIVATED UNCOMMENT THIS
		$platform_type = new ilSelectInputGUI($this->plugin_object->txt('platform_type'), 'platform_type');
		$platform_type->setOptions(array("win" => $this->plugin_object->txt('windows'), "unix" => $this->plugin_object->txt('unix'), //"unix-optimised" => $this->plugin_object->txt('unix_optimised'),
			"server" => $this->plugin_object->txt('server')));
		$platform_type->setInfo($this->plugin_object->txt('platform_type_info'));
		$platform_type->setValue($connection_data['platform_type']);
		$form->addItem($platform_type);


		//Maxima version
		$maxima_version = new ilSelectInputGUI($this->plugin_object->txt('maxima_version'), 'maxima_version');
		$maxima_version->setOptions(array('5.23.2' => '5.23.2', '5.25.1' => '5.25.1', '5.26.0' => '5.26.0', '5.27.0' => '5.27.0', '5.28.0' => '5.28.0', '5.30.0' => '5.30.0', '5.31.1' => '5.31.1', '5.31.2' => '5.31.2', '5.31.3' => '5.31.3', '5.32.0' => '5.32.0', '5.32.1' => '5.32.1', '5.33.0' => '5.33.0', '5.34.0' => '5.34.0', '5.34.1' => '5.34.1', '5.35.1' => '5.35.1', '5.35.1.2' => '5.35.1.2', '5.36.0' => '5.36.0', '5.36.1' => '5.36.1', '5.37.3' => '5.37.3', '5.38.0' => '5.38.0', '5.38.1' => '5.38.1', '5.39.0' => '5.39.0', '5.40.0' => '5.40.0', '5.41.0' => '5.41.0', 'default' => 'default'));
		$maxima_version->setInfo($this->plugin_object->txt('maxima_version_info'));
		$maxima_version->setValue($connection_data['maxima_version']);
		$form->addItem($maxima_version);

		//CAS connection timeout
		$cas_connection_timeout = new ilTextInputGUI($this->plugin_object->txt('cas_connection_timeout'), 'cas_connection_timeout');
		$cas_connection_timeout->setInfo($this->plugin_object->txt('cas_connection_timeout_info'));
		$cas_connection_timeout->setValue($connection_data['cas_connection_timeout']);
		$form->addItem($cas_connection_timeout);

		//CAS result caching
		//NOT USED BY ILIAS VERSION
		/*
		$cas_result_caching = new ilSelectInputGUI($this->plugin_object->txt('cas_result_caching'), 'cas_result_caching');
		$cas_result_caching->setOptions(array(
			"db" => $this->plugin_object->txt('cache_in_the_database'),
			"otherdb" => $this->plugin_object->txt('do_not_cache')
		));
		$cas_result_caching->setInfo($this->plugin_object->txt('cas_result_caching_info'));
		$cas_result_caching->setValue($connection_data['cas_result_caching']);
		$form->addItem($cas_result_caching);
		*/
		$cas_result_caching = new ilHiddenInputGUI('cas_result_caching');
		$cas_result_caching->setValue('db');
		$form->addItem($cas_result_caching);

		if ($connection_data['platform_type'] == 'win') {

            //Maxima command
            $maxima_command = new ilTextInputGUI($this->plugin_object->txt('maxima_command'), 'maxima_command');
            $maxima_command->setInfo($this->plugin_object->txt('maxima_command_info'));
            $maxima_command->setValue($connection_data['maxima_command']);
            $form->addItem($maxima_command);
        }
        elseif ($connection_data['platform_type'] == 'server')
        {
            $link = $DIC->ctrl()->getLinkTarget($this,'showServerList');
            $maxima_command = new ilNonEditableValueGUI($this->plugin_object->txt('maxima_command'), '');
            $maxima_command->setValue($this->plugin_object->txt('maxima_command_server'));
            $maxima_command->setInfo(sprintf($this->plugin_object->txt('maxima_command_server_info'), $link));
            $form->addItem($maxima_command);
        }

        if ($connection_data['platform_type'] == 'win' OR $connection_data['platform_type'] == 'server')
        {
            //Plot command
			$plot_command = new ilTextInputGUI($this->plugin_object->txt('plot_command'), 'plot_command');
			$plot_command->setInfo($this->plugin_object->txt('plot_command_info'));
			$plot_command->setValue($connection_data['plot_command']);
			$form->addItem($plot_command);
		}

		//CAS debugging
		//NOT USED BY ILIAS VERSION
		/*
		$cas_debugging = new ilCheckboxInputGUI($this->plugin_object->txt('cas_debugging'), 'cas_debugging');
		$cas_debugging->setInfo($this->plugin_object->txt("cas_debugging_info"));
		$cas_debugging->setChecked($connection_data['cas_debugging']);
		$form->addItem($cas_debugging);
		*/
		$cas_debugging = new ilHiddenInputGUI('cas_debugging');
		$cas_debugging->setValue('0');
		$form->addItem($cas_debugging);

		//Maxima libraries
		$maxima_libraries = new ilTextInputGUI($this->plugin_object->txt('maxima_libraries'), 'cas_maxima_libraries');
		$maxima_libraries->setInfo($this->plugin_object->txt('cas_maxima_libraries_info'));
		$maxima_libraries->setValue($connection_data['cas_maxima_libraries']);
		$form->addItem($maxima_libraries);

		$form->setTitle($this->plugin_object->txt('connection_settings'));
		$form->addCommandButton("saveConnectionSettings", $this->plugin_object->txt("save"));
		$form->addCommandButton("showConnectionSettings", $this->plugin_object->txt("cancel"));
		$form->addCommandButton("setDefaultSettingsForConnection", $this->plugin_object->txt("default_settings"));

		return $form;
	}

	public function getDisplaySettingsForm()
	{
		global $DIC;
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormAction($this));

		//Values from DB
		$display_data = assStackQuestionConfig::_getStoredSettings('display');
		$connection_data = assStackQuestionConfig::_getStoredSettings('connection');

		//Instant validation
		if ($connection_data['platform_type'] == 'server')
		{
			$instant_validation = new ilCheckboxInputGUI($this->plugin_object->txt('instant_validation'), 'instant_validation');
			$instant_validation->setInfo($this->plugin_object->txt("instant_validation_info"));
			$instant_validation->setChecked($display_data['instant_validation']);
		} else
		{
			$instant_validation = new ilCheckboxInputGUI($this->plugin_object->txt('instant_validation'), 'instant_validation');
			$instant_validation->setInfo($this->plugin_object->txt("instant_validation_info"));
			$instant_validation->setChecked(FALSE);
			$instant_validation->setDisabled(TRUE);
		}
		$form->addItem($instant_validation);

		//Maths filter
		$maths_filter = new ilSelectInputGUI($this->plugin_object->txt('maths_filter'), 'maths_filter');
		$maths_filter->setOptions(array("mathjax" => "MathJax"));
		$maths_filter->setInfo($this->plugin_object->txt('maths_filter_info'));
		$maths_filter->setValue($display_data['maths_filter']);
		$form->addItem($maths_filter);

		//Replace dollars
		$replace_dollars = new ilCheckboxInputGUI($this->plugin_object->txt('replace_dollars'), 'replace_dollars');
		$replace_dollars->setInfo($this->plugin_object->txt("replace_dollars_info"));
		$replace_dollars->setChecked($display_data['replace_dollars']);
		$form->addItem($replace_dollars);

		$form->setTitle($this->plugin_object->txt('display_settings'));
		$form->addCommandButton("saveDisplaySettings", $this->plugin_object->txt("save"));
		$form->addCommandButton("showDisplaySettings", $this->plugin_object->txt("cancel"));
		$form->addCommandButton("setDefaultSettingsForDisplay", $this->plugin_object->txt("default_settings"));

		return $form;
	}

	public function getDefaultOptionsSettingsForm()
	{
		global $DIC;
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormAction($this));

		//Values from DB
		$options_data = assStackQuestionConfig::_getStoredSettings('options');

		//Options question simplify
		$options_question_simplify = new ilCheckboxInputGUI($this->plugin_object->txt('options_question_simplify'), 'options_question_simplify');
		$options_question_simplify->setInfo($this->plugin_object->txt('options_question_simplify_info'));
		$options_question_simplify->setChecked($options_data['options_question_simplify']);
		$form->addItem($options_question_simplify);

		//Options assume positive
		$options_assume_positive = new ilCheckboxInputGUI($this->plugin_object->txt('options_assume_positive'), 'options_assume_positive');
		$options_assume_positive->setInfo($this->plugin_object->txt('options_assume_positive_info'));
		$options_assume_positive->setChecked($options_data['options_assume_positive']);
		$form->addItem($options_assume_positive);

		//Options Standard feedback for correct answer
		$options_prt_correct = new ilTextAreaInputGUI($this->plugin_object->txt('options_prt_correct'), 'options_prt_correct');
		$this->setRTESupport($options_prt_correct);
		$options_prt_correct->setValue($options_data['options_prt_correct']);
		$form->addItem($options_prt_correct);

		//Options Standard feedback for partially correct answer
		$options_prt_partially_correct = new ilTextAreaInputGUI($this->plugin_object->txt('options_prt_partially_correct'), 'options_prt_partially_correct');
		$this->setRTESupport($options_prt_partially_correct);
		$options_prt_partially_correct->setValue($options_data['options_prt_partially_correct']);
		$form->addItem($options_prt_partially_correct);

		//Options Standard feedback for incorrect answer
		$options_prt_incorrect = new ilTextAreaInputGUI($this->plugin_object->txt('options_prt_incorrect'), 'options_prt_incorrect');
		$this->setRTESupport($options_prt_incorrect);
		$options_prt_incorrect->setValue($options_data['options_prt_incorrect']);
		$form->addItem($options_prt_incorrect);

		//Options multiplication sign
		$options_multiplication_sign = new ilSelectInputGUI($this->plugin_object->txt('options_multiplication_sign'), 'options_multiplication_sign');
		$options_multiplication_sign->setOptions(array("dot" => $this->plugin_object->txt('options_mult_sign_dot'), "cross" => $this->plugin_object->txt('options_mult_sign_cross'), "none" => $this->plugin_object->txt('options_mult_sign_none')));
		$options_multiplication_sign->setInfo($this->plugin_object->txt('options_multiplication_sign'));
		$options_multiplication_sign->setValue($options_data['options_multiplication_sign']);
		$form->addItem($options_multiplication_sign);

		//Options Sqrt sign
		$options_sqrt_sign = new ilCheckboxInputGUI($this->plugin_object->txt('options_sqrt_sign'), 'options_sqrt_sign');
		$options_sqrt_sign->setInfo($this->plugin_object->txt('options_sqrt_sign_info'));
		$options_sqrt_sign->setChecked($options_data['options_sqrt_sign']);
		$form->addItem($options_sqrt_sign);

		//Options Complex numbers
		$options_complex_numbers = new ilSelectInputGUI($this->plugin_object->txt('options_complex_numbers'), 'options_complex_numbers');
		$options_complex_numbers->setOptions(array("i" => $this->plugin_object->txt('options_complex_numbers_i'), "j" => $this->plugin_object->txt('options_complex_numbers_j'), "symi" => $this->plugin_object->txt('options_complex_numbers_symi'), "symj" => $this->plugin_object->txt('options_complex_numbers_symj')));
		$options_complex_numbers->setInfo($this->plugin_object->txt('options_complex_numbers_info'));
		$options_complex_numbers->setValue($options_data['options_complex_numbers']);
		$form->addItem($options_complex_numbers);

		//Options inverse trigonometric
		$options_inverse_trigonometric = new ilSelectInputGUI($this->plugin_object->txt('options_inverse_trigonometric'), 'options_inverse_trigonometric');
		$options_inverse_trigonometric->setOptions(array("cos-1" => $this->plugin_object->txt('options_inverse_trigonometric_cos'), "acos" => $this->plugin_object->txt('options_inverse_trigonometric_acos'), "arccos" => $this->plugin_object->txt('options_inverse_trigonometric_arccos')));
		$options_inverse_trigonometric->setInfo($this->plugin_object->txt('options_inverse_trigonometric_info'));
		$options_inverse_trigonometric->setValue($options_data['options_inverse_trigonometric']);
		$form->addItem($options_inverse_trigonometric);

		//Options Matrix Parents
		$options_matrix_parents = new ilSelectInputGUI($this->plugin_object->txt('options_matrix_parens'), 'options_matrix_parents');
		$options_matrix_parents->setOptions(array("[" => "[", "(" => "(", "" => "", "{" => "{", "|" => "|"));
		$options_matrix_parents->setInfo($this->plugin_object->txt('options_matrix_parens_info'));
		$options_matrix_parents->setValue($options_data['options_matrix_parents']);
		$form->addItem($options_matrix_parents);

		$form->setTitle($this->plugin_object->txt('default_options_settings'));
		$form->addCommandButton("saveDefaultOptionsSettings", $this->plugin_object->txt("save"));
		$form->addCommandButton("showDefaultOptionsSettings", $this->plugin_object->txt("cancel"));
		$form->addCommandButton("setDefaultSettingsForOptions", $this->plugin_object->txt("default_settings"));

		return $form;
	}

	public function getDefaultInputsSettingsForm()
	{
		global $DIC;
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormAction($this));

		//Values from DB
		$inputs_data = assStackQuestionConfig::_getStoredSettings('inputs');

		//Input type
		$input_type = new ilSelectInputGUI($this->plugin_object->txt('input_type'), 'input_type');
		$input_type->setOptions(array("algebraic" => $this->plugin_object->txt('input_type_algebraic'), "boolean" => $this->plugin_object->txt('input_type_boolean'), "matrix" => $this->plugin_object->txt('input_type_matrix'), "singlechar" => $this->plugin_object->txt('input_type_singlechar'), "textarea" => $this->plugin_object->txt('input_type_textarea')));
		$input_type->setInfo($this->plugin_object->txt('input_type_info'));
		$input_type->setValue($inputs_data['input_type']);
		$form->addItem($input_type);

		//Input box size
		$input_box_size = new ilTextInputGUI($this->plugin_object->txt('input_box_size'), 'input_box_size');
		$input_box_size->setInfo($this->plugin_object->txt('input_box_size_info'));
		$input_box_size->setValue($inputs_data['input_box_size']);
		$form->addItem($input_box_size);

		//Input strict syntax
		$input_strict_syntax = new ilCheckboxInputGUI($this->plugin_object->txt('input_strict_syntax'), 'input_strict_syntax');
		$input_strict_syntax->setInfo($this->plugin_object->txt("input_strict_syntax_info"));
		$input_strict_syntax->setChecked($inputs_data['input_strict_syntax']);
		$form->addItem($input_strict_syntax);

		//Input insert stars
		$input_insert_stars = new ilCheckboxInputGUI($this->plugin_object->txt('input_insert_stars'), 'input_insert_stars');
		$input_insert_stars->setInfo($this->plugin_object->txt("input_insert_stars_info"));
		$input_insert_stars->setChecked($inputs_data['input_insert_stars']);
		$form->addItem($input_insert_stars);

		//Input forbid float
		$input_forbid_float = new ilCheckboxInputGUI($this->plugin_object->txt('input_forbid_float'), 'input_forbid_float');
		$input_forbid_float->setInfo($this->plugin_object->txt("input_forbid_float_info"));
		$input_forbid_float->setChecked($inputs_data['input_forbid_float']);
		$form->addItem($input_forbid_float);

		//Input Require lowest terms
		$input_require_lowest_terms = new ilCheckboxInputGUI($this->plugin_object->txt('input_require_lowest_terms'), 'input_require_lowest_terms');
		$input_require_lowest_terms->setInfo($this->plugin_object->txt("input_require_lowest_terms_info"));
		$input_require_lowest_terms->setChecked($inputs_data['input_require_lowest_terms']);
		$form->addItem($input_require_lowest_terms);

		//Input Check answer type
		$input_check_answer_type = new ilCheckboxInputGUI($this->plugin_object->txt('input_check_answer_type'), 'input_check_answer_type');
		$input_check_answer_type->setInfo($this->plugin_object->txt("input_check_answer_type_info"));
		$input_check_answer_type->setChecked($inputs_data['input_check_answer_type']);
		$form->addItem($input_check_answer_type);

		//Input Student must verify
		$input_must_verify = new ilCheckboxInputGUI($this->plugin_object->txt('input_must_verify'), 'input_must_verify');
		$input_must_verify->setInfo($this->plugin_object->txt("input_must_verify_info"));
		$input_must_verify->setChecked($inputs_data['input_must_verify']);
		$form->addItem($input_must_verify);

		//Input show validation
		$input_show_validation = new ilSelectInputGUI($this->plugin_object->txt('input_show_validation'), 'input_show_validation');
		$input_show_validation->setOptions(array(0 => $this->plugin_object->txt('show_validation_no'), 1 => $this->plugin_object->txt('show_validation_yes_with_vars'), 2 => $this->plugin_object->txt('show_validation_yes_without_vars')));
		$input_show_validation->setInfo($this->plugin_object->txt("input_show_validation_info"));
		$input_show_validation->setValue($inputs_data['input_show_validation']);
		$form->addItem($input_show_validation);

		//Input syntax hint
		$input_syntax_hint = new ilTextInputGUI($this->plugin_object->txt('input_syntax_hint'), 'input_syntax_hint');
		$input_syntax_hint->setInfo($this->plugin_object->txt('input_syntax_hint_info'));
		$input_syntax_hint->setValue($inputs_data['input_syntax_hint']);
		$form->addItem($input_syntax_hint);

		//Input forbidden words
		$input_forbidden_words = new ilTextInputGUI($this->plugin_object->txt('input_forbidden_words'), 'input_forbidden_words');
		$input_forbidden_words->setInfo($this->plugin_object->txt('input_forbidden_words_info'));
		$input_forbidden_words->setValue($inputs_data['input_forbidden_words']);
		$form->addItem($input_forbidden_words);

		//Input Allow words
		$input_allow_words = new ilTextInputGUI($this->plugin_object->txt('input_allow_words'), 'input_allow_words');
		$input_allow_words->setInfo($this->plugin_object->txt('input_allow_words_info'));
		$input_allow_words->setValue($inputs_data['input_allow_words']);
		$form->addItem($input_allow_words);

		//Input extra options
		$input_extra_options = new ilTextInputGUI($this->plugin_object->txt('input_options'), 'input_extra_options');
		$input_extra_options->setInfo($this->plugin_object->txt('input_options_info'));
		$input_extra_options->setValue($inputs_data['input_extra_options']);
		$form->addItem($input_extra_options);

		$form->setTitle($this->plugin_object->txt('default_input_settings'));
		$form->addCommandButton("saveDefaultInputsSettings", $this->plugin_object->txt("save"));
		$form->addCommandButton("showDefaultInputsSettings", $this->plugin_object->txt("cancel"));
		$form->addCommandButton("setDefaultSettingsForInputs", $this->plugin_object->txt("default_settings"));

		return $form;
	}

	public function getDefaultPRTSettingsForm()
	{
		global $DIC;
		require_once './Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/classes/utils/class.assStackQuestionInitialization.php';
		require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$ctrl = $DIC->ctrl();
		$form->setFormAction($ctrl->getFormAction($this));

		//Values from DB
		$prts_data = assStackQuestionConfig::_getStoredSettings('prts');

		//General settings
		//Simplify
		$lng = $DIC->language();
		$prt_simplify = new ilSelectInputGUI($this->plugin_object->txt('prt_simplify'), 'prt_simplify');
		$prt_simplify->setOptions(array(TRUE => $lng->txt('yes'), FALSE => $lng->txt('no'),));
		$prt_simplify->setInfo($this->plugin_object->txt('prt_simplify_info'));
		$prt_simplify->setValue($prts_data['prt_simplify']);
		$form->addItem($prt_simplify);

		//First node
		//Answer test
		$answer_test = new ilSelectInputGUI($this->plugin_object->txt('prt_node_answer_test'), 'prt_node_answer_test');
		// Prepare answer test types.
		$this->plugin_object->includeClass('stack/answertest/controller.class.php');
		$answertests = stack_ans_test_controller::get_available_ans_tests();
		$answertestchoices = array();

		foreach ($answertests as $test => $string)
		{
			$answertestchoices[$test] = stack_string($string);
		}
		$answer_test->setOptions($answertestchoices);
		$answer_test->setInfo($this->plugin_object->txt('prt_node_answer_test_info'));
		$answer_test->setValue($prts_data['prt_node_answer_test']);
		$form->addItem($answer_test);

		//Test-Options
		$node_options = new ilTextInputGUI($this->plugin_object->txt('prt_node_options'), 'prt_node_options');
		$node_options_info_text = $this->plugin_object->txt('prt_node_options_info');
		$node_options->setInfo($node_options_info_text);
		$node_options->setValue($prts_data['prt_node_options']);
		$form->addItem($node_options);

		//Quiet
		$node_quiet = new ilSelectInputGUI($this->plugin_object->txt('prt_node_quiet'), 'prt_node_quiet');
		$node_quiet->setOptions(array(TRUE => $lng->txt('yes'), FALSE => $lng->txt('no'),));
		$node_quiet->setInfo($this->plugin_object->txt('prt_node_quiet_info'));
		$node_quiet->setValue($prts_data['prt_node_quiet']);
		$form->addItem($node_quiet);

		//Mode when Positive
		$node_pos_mode = new ilSelectInputGUI($this->plugin_object->txt('prt_node_pos_mod'), 'prt_pos_mod');
		$node_pos_mode->setOptions(array("=" => "=", "+" => "+", "-" => "-"));
		$node_pos_mode->setInfo($this->plugin_object->txt('prt_node_pos_mod_info'));
		$node_pos_mode->setValue($prts_data['prt_pos_mod']);
		$form->addItem($node_pos_mode);

		//Positive score
		$node_pos_score = new ilTextInputGUI($this->plugin_object->txt('prt_node_pos_score'), 'prt_pos_score');
		$node_pos_score->setInfo($this->plugin_object->txt('prt_node_pos_score_info'));
		$node_pos_score->setValue($prts_data['prt_pos_score']);
		$form->addItem($node_pos_score);

		//Positive penalty
		$node_pos_penalty = new ilTextInputGUI($this->plugin_object->txt('prt_node_pos_penalty'), 'prt_pos_penalty');
		$node_pos_penalty->setInfo($this->plugin_object->txt('prt_node_pos_penalty_info'));
		$node_pos_penalty->setValue($prts_data['prt_pos_penalty']);
		$form->addItem($node_pos_penalty);

		//Positive answer note
		$node_pos_answernote = new ilTextInputGUI($this->plugin_object->txt('prt_node_pos_answernote'), 'prt_pos_answernote');
		$node_pos_answernote->setInfo($this->plugin_object->txt('prt_node_pos_answernote_info'));
		$node_pos_answernote->setValue($prts_data['prt_pos_answernote']);
		$form->addItem($node_pos_answernote);

		//Mode when Negative
		$node_neg_mode = new ilSelectInputGUI($this->plugin_object->txt('prt_node_neg_mod'), 'prt_neg_mod');
		$node_neg_mode->setOptions(array("=" => "=", "+" => "+", "-" => "-"));
		$node_neg_mode->setInfo($this->plugin_object->txt('prt_node_neg_mod_info'));
		$node_neg_mode->setValue($prts_data['prt_neg_mod']);
		$form->addItem($node_neg_mode);

		//Negative score
		$node_neg_score = new ilTextInputGUI($this->plugin_object->txt('prt_node_neg_score'), 'prt_neg_score');
		$node_neg_score->setInfo($this->plugin_object->txt('prt_node_neg_score_info'));
		$node_neg_score->setValue($prts_data['prt_neg_score']);
		$form->addItem($node_neg_score);

		//Negative penalty
		$node_neg_penalty = new ilTextInputGUI($this->plugin_object->txt('prt_node_neg_penalty'), 'prt_neg_penalty');
		$node_neg_penalty->setInfo($this->plugin_object->txt('prt_node_neg_penalty_info'));
		$node_neg_penalty->setValue($prts_data['prt_neg_penalty']);
		$form->addItem($node_neg_penalty);

		//Negative answer note
		$node_neg_answernote = new ilTextInputGUI($this->plugin_object->txt('prt_node_neg_answernote'), 'prt_neg_answernote');
		$node_neg_answernote->setInfo($this->plugin_object->txt('prt_node_neg_answernote_info'));
		$node_neg_answernote->setValue($prts_data['prt_neg_answernote']);
		$form->addItem($node_neg_answernote);

		$form->setTitle($this->plugin_object->txt('default_prts_settings'));
		$form->addCommandButton("saveDefaultPRTsSettings", $this->plugin_object->txt("save"));
		$form->addCommandButton("showDefaultPRTsSettings", $this->plugin_object->txt("cancel"));
		$form->addCommandButton("setDefaultSettingsForPRTs", $this->plugin_object->txt("default_settings"));

		return $form;
	}

	public function getServerSettingsForm($a_server_id = null)
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $lng = $DIC->language();

        $this->plugin_object->includeClass("model/configuration/class.assStackQuestionServer.php");

        if (isset($a_server_id) && $a_server_id > 0)
        {
            $server = assStackQuestionServer::getServerById($a_server_id);
            $title = $this->plugin_object->txt('edit_server');
            $ctrl->setParameter($this, 'server_id', $a_server_id);
        }
        else
        {
            $server = assStackQuestionServer::getDefaultServer();
            $title = $this->plugin_object->txt('add_server');
        }

        $form = new ilPropertyFormGUI();
        $form->setTitle($title);
        $form->setFormAction($ctrl->getFormAction($this));

        // purpose
        $options = [];
        foreach (assStackQuestionServer::getPurposes() as $purpose)
        {
            $options[$purpose] = $this->plugin_object->txt('srv_purpose_' . $purpose);
        }
        $purpose = new ilSelectInputGUI($this->plugin_object->txt('srv_purpose'), 'purpose');
        $purpose->setInfo($this->plugin_object->txt('srv_purpose_info'));
        $purpose->setRequired(true);
        $purpose->setOptions($options);
        $purpose->setValue($server->getPurpose());
        $form->addItem($purpose);

        $address = new ilTextInputGUI($this->plugin_object->txt('srv_address'), 'address');
        $address->setInfo($this->plugin_object->txt('srv_address_info'));
        $address->setRequired(true);
        $address->setValue($server->getAddress());
        $form->addItem($address);

        $active = new ilCheckboxInputGUI($lng->txt('active'), 'active');
        $active->setInfo($this->plugin_object->txt('srv_active_info'));
        $active->setChecked($server->isActive());
        $form->addItem($active);

        $form->addCommandButton('saveServerSettings', $lng->txt('save'));
        $form->addCommandButton('showServerList', $lng->txt('cancel'));

        return $form;
    }


	public function clearCache()
	{
		//Create Healthcheck
		$this->plugin_object->includeClass("model/configuration/class.assStackQuestionHealthcheck.php");
		$healthcheck_object = new assStackQuestionHealthcheck($this->plugin_object);
		$cache_is_clear = $healthcheck_object->clearCache();

		if ($cache_is_clear)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('cache_successfully_deleted'));
		}

		$this->showHealthcheck("");
	}


	/*
	 * SAVE CONFIGURATION METHODS
	 */

	public function saveConnectionSettings()
	{
		try
		{
			$ok = $this->config->saveConnectionSettings();
			if ($ok)
			{
				ilUtil::sendSuccess($this->plugin_object->txt('config_connection_changed_message'));
			} else
			{
				ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
			}
		} catch (Exception $exception)
		{
			ilUtil::sendFailure($exception->getMessage());
		}
		$this->showConnectionSettings();
	}

	public function saveDisplaySettings()
	{
		$ok = $this->config->saveDisplaySettings();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_display_changed_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDisplaySettings();
	}

	public function saveDefaultOptionsSettings()
	{
		$ok = $this->config->saveDefaultOptionsSettings();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_options_changed_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDefaultOptionsSettings();
	}

	public function saveDefaultInputsSettings()
	{
		$ok = $this->config->saveDefaultInputsSettings();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_inputs_changed_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDefaultInputsSettings();
	}

	public function saveDefaultPRTsSettings()
	{
		$ok = $this->config->saveDefaultPRTsSettings();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_prts_changed_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDefaultPRTsSettings();
	}

	public function saveServerSettings()
    {
        global $DIC, $tpl;
        $form = $this->getServerSettingsForm($_GET['server_id']);
        if ($form->checkInput())
        {
            if (isset($_GET['server_id']))
            {
                $server = assStackQuestionServer::getServerById($_GET['server_id']);
            }
            else
            {
                $server = assStackQuestionServer::getDefaultServer();
            }
            $server->setPurpose($form->getInput('purpose'));
            $server->setAddress($form->getInput('address'));
            $server->setActive($form->getInput('active'));
            $server->save();

            ilUtil::sendSuccess($this->plugin_object->txt('server_saved'), true);
            $DIC->ctrl()->redirect($this, 'showServerList');
        }
        else
        {
            $form->setValuesByPost();
            $tpl->setContent($form->getHTML());
        }
    }


	/*
	 * SET DEFAULT VALUES METHODS
	 */

	public function setDefaultSettingsForConnection()
	{
		$ok = $this->config->setDefaultSettingsForConnection();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_default_connection_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showConnectionSettings();
	}

	public function setDefaultSettingsForDisplay()
	{
		$ok = $this->config->setDefaultSettingsForDisplay();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_default_display_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDisplaySettings();
	}

	public function setDefaultSettingsForOptions()
	{
		$ok = $this->config->setDefaultSettingsForOptions();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_default_options_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDefaultOptionsSettings();
	}

	public function setDefaultSettingsForInputs()
	{
		$ok = $this->config->setDefaultSettingsForInputs();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_default_inputs_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDefaultInputsSettings();
	}

	public function setDefaultSettingsForPRTs()
	{
		$ok = $this->config->setDefaultSettingsForPRTs();
		if ($ok)
		{
			ilUtil::sendSuccess($this->plugin_object->txt('config_default_prts_message'));
		} else
		{
			ilUtil::sendFailure($this->plugin_object->txt('config_error_message'));
		}
		$this->showDefaultPRTsSettings();
	}

	/**
	 * Set the STACK specific rich text editing support in textarea fields
	 * This uses an own module instead of "assessment" to determine the allowed tags
	 */
	public function setRTESupport(ilTextAreaInputGUI $field)
	{
		if (empty($this->rte_tags))
		{
			$this->initRTESupport();
		}
		$field->setUseRte(true);
		$field->setRteTags($this->rte_tags);
		$field->addPlugin("latex");
		$field->addButton("latex");
		$field->addButton("pastelatex");
		$field->setRTESupport($this->plugin_object->getId(), "qpl", $this->rte_module);
	}

	/**
	 * Get a list of allowed RTE tags
	 * This is used for ilUtil::stripSpashes() when saving the RTE fields
	 *
	 * @return string    allowed html tags, e.g. "<em><strong>..."
	 */
	public function getRTETags()
	{
		if (empty($this->rte_tags))
		{
			$this->initRTESupport();
		}

		return '<' . implode('><', $this->rte_tags) . '>';
	}

	/**
	 * Init the STACK specific rich text editing support
	 * The allowed html tags are stored in an own settings module instead of "assessment"
	 * This enabled an independent tag set from the editor settings in ILIAS administration
	 * Text area fields will be initialized with SetRTESupport using this module
	 */
	public function initRTESupport()
	{
		include_once "./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php";
		$this->rte_tags = ilObjAdvancedEditing::_getUsedHTMLTags($this->rte_module);

		$this->required_tags = array("a", "blockquote", "br", "cite", "code", "div", "em", "h1", "h2", "h3", "h4", "h5", "h6", "hr", "img", "li", "ol", "p", "pre", "span", "strike", "strong", "sub", "sup", "table", "caption", "thead", "th", "td", "tr", "u", "ul", "i", "b", "gap");

		if (serialize($this->rte_tags) != serialize(($this->required_tags)))
		{

			$this->rte_tags = $this->required_tags;
			//TODO change this uncomment
			//ilObjAdvancedEditing::_setUsedHTMLTags($this->rte_tags, $this->rte_module);
		}
	}
}
