<?php
require_once "./Services/User/classes/class.ilObjUser.php";

/**
 * This file is part of the RESTPlugin library layer.
 * Its purpose is to provide utilities to enable REST models
 * to use the ILIAS SOAP webservice.
 */

class ilRESTSoapAdapter {
    //static private $instance = null;
    public $SID = "";

    /**
     * Replaces the SOAP login method. Creates a valid session, which can be used for SOAP calls.
     */
    public function loginSOAP()
    {
        ilRESTLib::initDefaultRESTGlobals();
        ilRESTLib::initAccessHandling();
        ilRESTLib::initSettings();

        define ("IL_SOAPMODE", IL_SOAPMODE_INTERNAL);
        include_once "Services/Context/classes/class.ilContext.php";
        ilContext::init(ilContext::CONTEXT_SOAP);

        $query = "SELECT `setting_name`, `setting_value` FROM `ui_uihk_rest_config` WHERE `setting_name` IN ('rest_system_user', 'rest_user_pass')";
        $set = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($set)) {
            // Make rest plugin settings globally available
            switch ($row['setting_name']) {
                case "rest_soap_user":
                    $username = $row['setting_value'];
                    break;
                case "rest_soap_pass":
                    $password = $row['setting_value'];
                    break;
            }
        }
        if (!isset($username) || !isset($password)) {
            // TODO: Throw an error header here!
            logoutSOAP();
        }

        // Get username and password
        require_once "./Services/Calendar/classes/class.ilDatePresentation.php";
        require_once "./Services/User/classes/class.ilObjUser.php";
        $user_id = ilObjUser::getUserIdByLogin($username);

        if ($user_id == 0)
        {
            return false;
        }
        $ilUser = new ilObjUser($user_id);
        ilRESTLib::initGlobal("ilUser", $ilUser);

        $_POST['username'] = $username;
        $_POST['password'] = $password;

        // add code 1
        if (!is_object($GLOBALS["ilPluginAdmin"]))
        {
            ilRESTLib::initGlobal("ilPluginAdmin", "ilPluginAdmin",
                "./Services/Component/classes/class.ilPluginAdmin.php");
        }
        // add code 2
        include_once "Services/Authentication/classes/class.ilSession.php";
        include_once "Services/Authentication/classes/class.ilSessionControl.php";

        require_once "Auth/Auth.php";
        require_once "./Services/AuthShibboleth/classes/class.ilShibboleth.php";
        include_once("./Services/Authentication/classes/class.ilAuthUtils.php");
        ilAuthUtils::_initAuth();
        global $ilAuth;

        $ilAuth->start();
        //$checked_in = $ilAuth->getAuth();


        require_once("./Services/Init/classes/class.ilIniFile.php");
        $ilIliasIniFile = new ilIniFile("./ilias.ini.php");
        $ilIliasIniFile->read();
        $client = $ilIliasIniFile->readVariable("clients","default");

        $this->SID = (session_id().'::'.$client);
        return true;
    }

    public function executeSOAPFunction($str_function, $a_parameters)
    {
        include_once('webservice/soap/include/inc.soap_functions.php');
        $result = call_user_func_array('ilSoapFunctions::'.$str_function, $a_parameters);
        //var_dump($result);
        return $result;
    }

    public function logoutSOAP()
    {
        global $ilAuth;
        $ilAuth->logout();
        session_destroy();
        header_remove('Set-Cookie');
    }

}
