<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
 
 
// Include core configuration UI class
require_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
// Requires <$tpl>, <$ilUser>, <$ilCtrl>, <$ilTabs>


/**
 * REST Plugin Configuration
 *  Implements the plugins GUI inside ILIAS.
 *  Creates a link to open the ngAdmin App and passes some optional POST data along.
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
        $configHTML .= '<hr/>';
        $configHTML .= '<p>'.$pl->txt("welcome_adminpanel").'</p>';
        $configHTML .= '
            <form action="' . $pl->getDirectory() . '/apps/admin/index.php" method="post" target="_blank" id="redirectForm">
		<input type="hidden" name="iliasClient" value="'.CLIENT_ID.'" />
                <input type="hidden" name="userId" value="'.$ilUser->getId().'" />
                <input type="hidden" name="userName" value="'.$ilUser->getLogin().'" />
                <input type="hidden" name="sessionId" value="'.session_id().'" />
                <input type="hidden" name="rtoken" value="'.$ilCtrl->rtoken.'" />
                <input type="hidden" name="restEndpoint" value="'.$inst_folder.'" />
                <input type="hidden" name="apiKey" value="apollon" />
                <input type="submit" class="btn btn-default" value="'.$pl->txt("button_adminpanel").'" />
            </form>
        ';
        $configHTML  .= '<hr/>';
       /* $configHTML  .= '
            <script type="text/javascript">
                // Get form element
                var form = document.getElementById("redirectForm");
                
                // Redirect in 3 seconds
                setTimeout(function() { 
                    form.removeAttribute("target");
                    form.submit();
                }, 3000);
            </script>
        ';*/
        $configHTML .= '<p>'.$pl->txt("welcome_checkout_app").'</p>';
        $configHTML .= '
            <form action="' . $pl->getDirectory() . '/apps/checkout/index.php" method="post" target="_blank" id="redirectForm">
                <input type="hidden" name="userName" value="'.$ilUser->getLogin().'" />
                <input type="hidden" name="apiKey" value="apollon" />
                <input type="submit" class="btn btn-default" value="'.$pl->txt("button_checkout_app").'" />
            </form>
        ';
        $configHTML  .= '<hr/>';
        // Render content in ILIAS
        $tpl->setContent($configHTML);
    }
}
