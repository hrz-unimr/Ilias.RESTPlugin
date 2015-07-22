<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */


// Buffers all output in order to prevent data from beeing displayed
// before any header was sent/set, since we are doing REST und not just html
ob_start();


// Required included to initialize ILIAS
require_once("Services/Context/classes/class.ilContext.php");
require_once("Services/Init/classes/class.ilInitialisation.php");

// Set ILIAS Context. This should tell ILIAS what to load and what not
ilContext::init(ilContext::CONTEXT_REST);

// The term "client_id" is used twice within this REST context:
//  (1) ilias client_id                 [Will be ilias_client_id and client_id]
//  (2) oauth2 client_id (RFC 6749)     [Will be api_key]
// In order to solve the conflict for the variable "client_id" some counter measures are necessary.
// Solution: it is required to provide the variable ilias_client_id if a specific ilias client needs to be adressed.
if (isset($_GET['client_id']) || isset($_GET['ilias_client_id'])) {
    $_GET['api_key'] = $_GET['client_id']; 
    
    if (isset($_GET['ilias_client_id'])) 
        $_GET['client_id'] = $_GET['ilias_client_id'];
    else 
        $_GET['client_id'] = "";
} 
else if (isset($_POST['client_id'])) {
    $_POST['api_key'] = $_POST['client_id'];
    
    if (isset($_POST['ilias_client_id'])) {
        $_GET['client_id'] = $_POST['ilias_client_id'];
    } else {
        $_POST['client_id'] = "";
        $_GET['client_id'] = "";
    }
} else {
    $_GET['client_id'] = "";
}

// Set Cookie Path (necessary for RestLib\initSession)
$cookie_path = dirname($_SERVER['SCRIPT_NAME']);
$cookie_path = str_replace('\\', '/', $cookie_path);
$cookie_path = ($cookie_path == '/' ? '' : $cookie_path);
$GLOBALS['COOKIE_PATH'] = $cookie_path;

// Initialise ILIAS
ilInitialisation::initILIAS();
header_remove('Set-Cookie');

// Stops buffering output and ignores any output written so far
ob_end_clean();


// Run the RESTController or return error-code
if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")) {
    // Fetch plugin object
    $ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");
    $appDirectory = $ilRESTPlugin->getDirectory() . "/RESTController/";
    
    // Include the RESTController application
    require_once($appDirectory . '/app.php');
    
    // Register the RESTController Class-AutoLoader 
    \RESTController\RESTController::registerAutoloader();
    
    // Instantate and run the RESTController application
    $restController = new \RESTController\RESTController($appDirectory);
    $restController->run();
} else {
    // Display an appropriate error-message
    header('HTTP/1.0 404 Disabled');
    header('Warning: REST-Interface is disabled.');
    header('Content-Type: application/json');
    echo '{
        "msg": "REST-Interface is disabled."
    }';
}
