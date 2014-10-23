<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once("./Services/Init/classes/class.ilInitialisation.php");

/**
* ILIAS REST Initialisation
*/
class ilRestInitialisation extends ilInitialisation
{
    function __construct()
    {
        include_once "Services/Context/classes/class.ilContext.php";
        ilContext::init(ilContext::CONTEXT_REST);
    }

    function initIliasREST()
    {

        if (isset($_GET['client_id']) || isset($_GET['ilias_client_id'])) {
            // The term "client_id" is used twice within this REST context:
            //  (1) ilias client_id
            //  (2) oauth2 client_id (RFC 6749)
            // In order to solve the conflict for the variable "client_id" some counter measures are necessary.
            // Solution: it is required to provide the variable ilias_client_id if a specific ilias client needs to be adressed.
            $_GET['api_key'] = $_GET['client_id']; // see changes in oauth2 authentication code.
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
        } else { // json post request
              $_GET['client_id'] = "";
                //$_GET['client_id'] = $req_data['ilias_client_id'];
        }
        //var_dump($_POST);
       // var_dump($_GET);
        $this->initILIAS();
        $this->initRESTSettings();
        //session_destroy();
    }

    function initRESTSettings()
    {
        global $ilDB;
        $query = "SELECT * FROM rest_config";// WHERE setting_name='uuid'";
        $set = $ilDB->query($query);
        if (empty($set) == true)
        {
            echo "Severe problem: uuid value is missing in table rest_config.";
            http_response_code(500);
            die();
        } else
        {

        }
        while ($row = $ilDB->fetchAssoc($set))
        {
            switch ($row['setting_name']) {
                case "uuid" :
                    define("UUID", $row['setting_value']);
                    break;
                case "rest_system_user":
                    define("REST_USER", $row['setting_value']);
                    break;
                case "rest_user_pass":
                    define("REST_PASS", $row['setting_value']);
                    break;
            }
        }
    }

    public static function setCookieParams()
    {
        // Cookies are not required in the REST context.
        // Overwriting the parent function is not possible here, because it is called via self:: instead of static::.
        // Solution: Ideally the function "setCookieParams" should not be invoked if CONTEXT_REST is active.
    }

}
?>
