<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 
 
// Include core configuration UI class
require_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
// Requires <$tpl>, <$ilUser>, <$ilCtrl>, <$ilTabs>


/**
 * REST Plugin Configuration
 *  Implements the plugins GUI inside ILIAS.
 *  Only creates a link to open the Admin-Panel and
 *  passes some optional POST data along.
 *
 * @author Dirk Schaefer <schaefer at hrz.uni-marburg.de>
 * @version $Id$
 */
class ilRESTConfigGUI extends ilPluginConfigGUI {    
    /**
     * Handles all commmands
     *  - configure
     *  - save
     *  - editProperties
     *  - showContent
     * ...
     */
    function performCommand($cmd) {
        global $ilTabs;
        $ilTabs->clearTargets();
        
        // Handle commands
        switch ($cmd) {
            case "configure":
                $this->$cmd();
                break;

        }
    }

    /**
     * Configure screen
     */
    function configure() {
        global $tpl, $ilUser, $ilCtrl;
        
        // Get base ILIAS directory for POST data
        $inst_folder = dirname($_SERVER['SCRIPT_NAME']);
        $inst_folder = str_replace('\\', '/', $inst_folder);
        $inst_folder = ($inst_folder == '/' ? '' : $inst_folder);
        
        // Get plugin o bject for translations
        $pl = $this->getPluginObject();
        
        // Required to be able to fetch rtoken on $ilCtrl
        $ilCtrl->getFormAction($this);
        
        // Create HTML layout
        $configHTML  = '<h3>'.$pl->txt("welcome_config").'</h3>';
        $configHTML .= '<p>'.$pl->txt("welcome_redirect").'</p>';
        $configHTML .= '<hr/>';
        $configHTML .= '<p>'.$pl->txt("welcome_fail").'</p>';
        $configHTML .= '
            <form action="' . $pl->getDirectory() . '/apps/admin/index.php" method="post" target="_blank" id="redirectForm">
                <input type="hidden" name="userId" value="'.$ilUser->getId().'" />
                <input type="hidden" name="sessionId" value="'.session_id().'" />
                <input type="hidden" name="rtoken" value="'.$ilCtrl->rtoken.'" />
                <input type="hidden" name="restEndpoint" value="'.$inst_folder.'" />
                <input type="submit" class="btn btn-default" value="'.$pl->txt("button_redirect").'" />
            </form>
        ';
        $configHTML  .= '<hr/>';
        $configHTML  .= '
            <script type="text/javascript">
                // Get form element
                var form = document.getElementById("redirectForm");
                
                // Redirect in 3 seconds
                setTimeout(function() { 
                    form.removeAttribute("target");
                    form.submit();
                }, 3000);
            </script>
        ';

        // Render content in ILIAS
        $tpl->setContent($configHTML);
    }
}
