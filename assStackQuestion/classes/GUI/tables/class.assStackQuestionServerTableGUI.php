<?php
/**
 * Copyright (c) 2017 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * STACK Question server Table GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 * @version $Id: 2.4$
 * @ingroup    ModulesTestQuestionPool
 *
 */
class assStackQuestionServerTableGUI extends ilTable2GUI
{
    /** @var ilassStackQuestionPlugin $plugin */
    var $plugin;


	/**
	 * Constructor
	 * @param   ilassStackQuestionConfigGUI $a_parent_obj
	 * @param   string $a_parent_cmd
	 * @return
	 */
	public function __construct($a_parent_obj, $a_parent_cmd)
	{
		global $DIC;

		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
		$this->plugin = $a_parent_obj->getPluginObject();

		$this->setId('assStackQuestionServers');
		$this->setPrefix('assStackQuestionServers');

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setFormName('assStackQuestionServers');
        $this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
        $this->setRowTemplate("tpl.il_as_qpl_xqcas_server_row.html", $this->plugin->getDirectory());

        $this->setStyle('table', 'fullwidth');
        $this->setSelectAllCheckbox('server_id');
        $this->setEnableNumInfo(false);
        $this->setEnableHeader(true);
        $this->setExternalSegmentation(true);

        $this->addColumn('', '', '', true);
        $this->addColumn($this->plugin->txt('srv_address'));
        $this->addColumn($this->plugin->txt('srv_purpose'));
		$this->addColumn($this->lng->txt('active'));
        $this->addColumn('');

        $this->addMultiCommand('activateServers', $this->lng->txt('activate'));
        $this->addMultiCommand('deactivateServers', $this->lng->txt('deactivate'));
        $this->addMultiCommand('confirmDeleteServers', $this->lng->txt('delete'));

        $this->plugin->includeClass('model/configuration/class.assStackQuestionServer.php');

        $data = [];
        foreach (assStackQuestionServer::getServers() as $server)
        {
            $data[] = $server->toArray();
        }
        $this->setData($data);
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set)
	{
	    global $DIC;

        $DIC->ctrl()->setParameter($this->parent_obj, 'server_id', $a_set['server_id']);
	    $link = $DIC->ctrl()->getLinkTarget($this->parent_obj, 'editServer');

        $list = new ilAdvancedSelectionListGUI();
        $list->setSelectionHeaderClass('small');
        $list->setItemLinkClass('small');
        $list->setId('xqcas_server_actions');
        $list->setListTitle($this->lng->txt('actions'));

        $list->addItem($this->lng->txt('edit'), '', $this->ctrl->getLinkTarget($this->parent_obj, 'editServer'));
        $list->addItem($this->plugin->txt('show_healthcheck'), '', $this->ctrl->getLinkTarget($this->parent_obj, 'runHealthcheck'));
        $list->addItem($this->lng->txt('activate'), '', $this->ctrl->getLinkTarget($this->parent_obj, 'activateServers'));
        $list->addItem($this->lng->txt('deactivate'), '', $this->ctrl->getLinkTarget($this->parent_obj, 'deactivateServers'));
        $list->addItem($this->lng->txt('delete'), '', $this->ctrl->getLinkTarget($this->parent_obj, 'confirmDeleteServers'));

        $this->tpl->setVariable('SERVER_ID', $a_set['server_id']);
        $this->tpl->setVariable('ACTIVE', $a_set['active'] ? $this->lng->txt('yes') : $this->lng->txt('no'));
        $this->tpl->setVariable('PURPOSE', $this->plugin->txt('srv_purpose_' . $a_set['purpose']));
        $this->tpl->setVariable('ADDRESS', $a_set['address']);
        $this->tpl->setVariable('LINK', $link);
        $this->tpl->setVariable('ACTIONS', $list->getHTML());
    }
}