<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 

// Buffers all output in order to prevent data from beeing displayed
// before any header was sent/set, since we are doing REST und not just html
ob_start();

// Workaround see https://github.com/eqsoft/RESTPlugin/issues/1
if (isset($_GET['client_id'])) {
    $oauth_client_id = $_GET['client_id'];
    unset($_GET['client_id']);
}

// Tell ILIAS which context to load
include_once('./Services/Context/classes/class.ilContext.php');
ilContext::init(ilContext::CONTEXT_UNITTEST);

// Initialize ILIAS
include_once('./Services/Init/classes/class.ilInitialisation.php');
$ilInit = new ilInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initILIAS();

// Workaround see https://github.com/eqsoft/RESTPlugin/issues/1
if (isset($oauth_client_id)) { 
    $_GET['client_id'] = $oauth_client_id;
}

// Stops buffering output and ignores any output writen so far
ob_end_clean();

// Run the slim-application
if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")) {
    $ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");
    require_once($ilRESTPlugin->getDirectory() . "/RESTController/slimnode.php");
    $app->run();
} else {
    header("HTTP/1.0 405 Disabled");
    header("Warning: REST-Interface is disabled");
    echo "REST-Interface is disabled\r\n";
}
