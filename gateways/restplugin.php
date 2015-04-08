<?php /* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

// Buffers all output in order to prevent data from beeing displayed
// before any header was sent/set, since we are doing REST und not just html
ob_start();

// Set ILIAS Context. This should tell ILIAS what to load and what not
include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_REST);

// The term "client_id" is used twice within this REST context:
//  (1) ilias client_id                 [Will be ilias_client_id]
//  (2) oauth2 client_id (RFC 6749)     [Will be api_key]
// In order to solve the conflict for the variable "client_id" some counter measures are necessary.
// Solution: it is required to provide the variable ilias_client_id if a specific ilias client needs to be adressed.
if (isset($_GET['client_id']) || isset($_GET['ilias_client_id'])) {
    // see changes in oauth2 authentication code.
    $_GET['api_key'] = $_GET['client_id']; 
    if (isset($_GET['ilias_client_id'])) {
        $_GET['client_id'] = $_GET['ilias_client_id'];
    } else {
        $_GET['client_id'] = "";
    }
} else if (isset($_POST['client_id'])) {
    $_POST['api_key'] = $_POST['client_id'];
    if (isset($_POST['ilias_client_id'])) {
        $_GET['client_id'] = $_POST['ilias_client_id'];
    } else {
        $_POST['client_id'] = "";
        $_GET['client_id'] = "";
    }
// JSON post request
} else { 
      $_GET['client_id'] = "";
}

// Initialise ILIAS
require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

// Stops buffering output and ignores any output writen so far
ob_end_clean();

// Load rest plugin settings from database
global $ilDB;
$query = "SELECT * FROM ui_uihk_rest_config WHERE setting_name IN ('uuid', 'rest_system_user', 'rest_user_pass')";
$set = $ilDB->query($query);
if (empty($set) == true) {
    echo "Severe problem: uuid value is missing in table ui_uihk_rest_config.";
    http_response_code(500);
    die();
} 

// Make rest plugin settings globally available
while ($row = $ilDB->fetchAssoc($set)) {
    switch ($row['setting_name']) {
        case "uuid" :
            define("UUID", $row['setting_value']);
            break;
        case "rest_soap_user":
            define("REST_SOAP_USER", $row['setting_value']);
            break;
        case "rest_soap_pass":
            define("REST_SOAP_PASS", $row['setting_value']);
            break;
    }
}

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
