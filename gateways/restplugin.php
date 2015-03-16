<?php /* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

ob_start();
define('REST_ROOT', './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST');

// Load RESTPlugin, which in turn loads required ILIAS components
include_once(REST_ROOT . '/classes/class.ilRESTInitialisation.php');
$ilInit = new ilRESTInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initIliasREST();
ob_end_clean();

// Run the RESTController or return error-code
if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")) {
    require_once(REST_ROOT . '/RESTController/app.php');
    $app->run();
} else {
    header("HTTP/1.0 405 Disabled");
    header("Warning: REST-Interface is disabled");
    echo "REST-Interface is disabled\r\n";
}
