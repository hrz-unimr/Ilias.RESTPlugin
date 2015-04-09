<?php /* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


// Buffers all output in order to prevent data from beeing displayed
// before any header was sent/set, since we are doing REST und not just html
ob_start();


// Required included to initialize ILIAS
require_once("Services/Context/classes/class.ilContext.php");
require_once("Services/Init/classes/class.ilInitialisation.php");

// Set ILIAS Context. This should tell ILIAS what to load and what not
ilContext::init(ilContext::CONTEXT_REST);

// The term "client_id" is used twice within this REST context:
//  (1) ilias client_id                 [Will be ilias_client_id]
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
    
    if (isset($_POST['ilias_client_id']))
        $_GET['client_id'] = $_POST['ilias_client_id'];
    else {
        $_POST['client_id'] = "";
        $_GET['client_id'] = "";
    }
}
else 
    $_GET['client_id'] = "";

// Initialise ILIAS
ilInitialisation::initILIAS();


// Stops buffering output and ignores any output writen so far
ob_end_clean();


// Run the RESTController or return error-code
if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")) {
    $ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");
    require_once($ilRESTPlugin->getDirectory() . '/RESTController/app.php');
    
    $app->run();
} else {
    header("HTTP/1.0 405 Disabled");
    header("Warning: REST-Interface is disabled");
    echo "REST-Interface is disabled\r\n";
}
