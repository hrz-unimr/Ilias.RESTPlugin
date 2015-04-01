<?php
include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");


/**
 * REST Plugin Configuration
 *
 * @author Dirk Schaefer <schaefer at hrz.uni-marburg.de>
 * @version $Id$
 *
 */
class ilRESTConfigGUI extends ilPluginConfigGUI
{    
    /**
     * Handles all commmands, default is "configure"
     */
    function performCommand($cmd)
    {
        //global $ilTabs;
        //$ilTabs->clearTargets();
        //$this->setTabs();
        switch ($cmd)
        {
            case "configure":
            case "save":
                $this->$cmd();
                break;

        }
    }

    function setTabs()
    {
        global $ilTabs, $ilCtrl,  $ilAccess;
            $ilTabs->addTab("content", "test1", $ilCtrl->getLinkTarget($this, "showContent"));
       // if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
            $ilTabs->addTab("properties", "test2", $ilCtrl->getLinkTarget($this, "editProperties"));
    }

    /**
     * Configure screen
     */
    function configure()
    {
        global $tpl;
        global $ilUser, $ilCtrl;
        global $ilDB;

        $pl = $this->getPluginObject();

        $form = $this->initConfigurationForm();
        $_html=$form->getHTML();
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $protocol = 'http://';
        } else {
            $protocol = 'https://';
        }

        $pl->txt("welcome_config");
        $pl->txt("info_config");
        $pl->txt("installation_note_config");
        $pl->txt("api_info_config");
        $pl->txt("admin_info_config");
        $pl->txt("api_btn_config");
        $pl->txt("admin_btn_config");
        
        $configHTML = "<h3>".$pl->txt("welcome_config")."</h3>";
        $configHTML .= "<hr />";
        $configHTML .="<p>".$pl->txt("admin_info_config")."</p>";

        $inst_folder = dirname($_SERVER['SCRIPT_NAME']);
        $inst_folder = str_replace('\\', '/', $inst_folder);
        $inst_folder = ($inst_folder == '/' ? '' : $inst_folder);

        $configHTML .= '
            <form action="' . $pl->getDirectory() . '/apps/admin/index.php" method="post" target="_blank">
                <input type="hidden" name="user_id" value="'.$ilUser->getId().'" />
                <input type="hidden" name="session_id" value="'.session_id().'" />
                <input type="hidden" name="rtoken" value="'.$ilCtrl->rtoken.'" />
                <input type="hidden" name="inst_folder" value="'.$inst_folder.'" />
                <input type="submit" value="'.$pl->txt("admin_btn_config").'" />
            </form>
         ';
        $configHTML .= "<hr />";

        $tpl->setContent($configHTML);

    }

    /**
     * Init configuration form.
     *
     * @return object form object
     */
    public function initConfigurationForm()
    {
        global $lng, $ilCtrl;

        $pl = $this->getPluginObject();

        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();

        // setting 1 (a checkbox)
        $cb = new ilCheckboxInputGUI($pl->txt("setting_1"), "setting_1");
        $form->addItem($cb);

        // setting 2 (text)
        $ti = new ilTextInputGUI($pl->txt("setting_2"), "setting_2");
        $ti->setRequired(true);
        $ti->setMaxLength(10);
        $ti->setSize(10);
        $form->addItem($ti);

        $form->addCommandButton("save", $lng->txt("save"));

        $form->setTitle($pl->txt("plugin_configuration"));
        $form->setFormAction($ilCtrl->getFormAction($this));


        $req_header_txt = new ilTextInputGUI($pl->txt("req_header"), "req_header");
        $req_header_txt->setInfo($pl->txt("req_header_info"));
        $req_header_txt->setRequired(true);
        $req_header_txt->setSize(50);
        //$req_header_txt->setValue($req_header);
        $form->addItem($req_header_txt);

        return $form;
    }

    /**
     * Save form input (currently does not save anything to db)
     *
     */
    public function save()
    {
        global $tpl, $lng, $ilCtrl;

        $pl = $this->getPluginObject();

        $form = $this->initConfigurationForm();
        if ($form->checkInput())
        {
            $set1 = $form->getInput("setting_1");
            $set2 = $form->getInput("setting_2");

            // @todo: implement saving to db

            ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
            $ilCtrl->redirect($this, "configure");
        }
        else
        {
            $form->setValuesByPost();
            $tpl->setContent($form->getHtml());
        }
    }

}
