<?php /* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

// Buffers all output in order to prevent data from beeing displayed
// before any header was sent/set, since we are doing REST und not just html
ob_start();

// This allows us to parse the ilias.ini.php, such that we may find the location
// of the RESTPlugin
require_once("./Services/Init/classes/class.ilIniFile.php");
$ilIliasIniFile = new ilIniFile("./ilias.ini.php");				
$ilIliasIniFile->read();
define("ILIAS_REST_DIR", $ilIliasIniFile->readVariable("rest", "path"));
define("ILIAS_REST_URL", $ilIliasIniFile->readVariable("server", "http_path") . "/" . ILIAS_REST_DIR);

// Load RESTPlugin, which in turn loads required ILIAS components
include_once(ILIAS_REST_DIR . '/classes/class.ilRESTInitialisation.php');
$ilInit = new ilRESTInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initIliasREST();

// Stops buffering output and ignores any output writen so far
ob_end_clean();

// Run the RESTController or return error-code
if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")) {
    require_once(ILIAS_REST_DIR . '/RESTController/app.php');
    $app->run();
} else {
    header("HTTP/1.0 405 Disabled");
    header("Warning: REST-Interface is disabled");
    echo "REST-Interface is disabled\r\n";
}
