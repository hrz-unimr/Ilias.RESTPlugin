<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v1;

use \RESTController\libs as Libs;
// Requires <ilContext>, <$ilDB>, <ilObjUser>, <ilAuthUtils>, <ilIniFile>, <$ilAuth>

require_once('Services/Authentication/classes/class.ilAuthUtils.php');


/**
 * This file is part of the RESTPlugin library layer.
 * Its purpose is to provide utilities to enable REST models
 * to use the ILIAS SOAP webservice.
 */
class SoapAdapter {
    //protected static $instance = null;
    public $SID = "";

    /**
     * Replaces the SOAP login method.
     * Creates a valid session, which can be used for SOAP calls.
     */
    public function loginSOAP() {
        Libs\RESTilias::initAccessHandling();
        $ilDB = $GLOBALS['ilDB'];

        define ("IL_SOAPMODE", IL_SOAPMODE_INTERNAL);
        include_once("Services/Context/classes/class.ilContext.php");
        \ilContext::init(\ilContext::CONTEXT_SOAP);

        // Load username/password from DB
        $query = 'SELECT setting_name, setting_value FROM ui_uihk_rest_config WHERE setting_name IN ("soap_username", "soap_password")';
        $set = $ilDB->query($query);
        while ($row = $ilDB->fetchAssoc($set))
            switch ($row['setting_name']) {
                case "soap_username":
                    $username = $row['setting_value'];
                    break;
                case "soap_password":
                    $password = $row['setting_value'];
                    break;
            }

        // TODO: Throw an error header here!
        if (!isset($username) || !isset($password))  {
            self::logoutSOAP();
        }

        // Get username and password
        require_once("./Services/Calendar/classes/class.ilDatePresentation.php");
        require_once("./Services/User/classes/class.ilObjUser.php");
        $user_id = \ilObjUser::getUserIdByLogin($username);

        if ($user_id == 0) {
            return false;
        }

        global $ilUser;
        Libs\RESTilias::loadIlUser();
        $ilUser->setId($user_id);
        $ilUser->read();

        $_POST['username'] = $username;
        $_POST['password'] = $password;

        // add code 2
        require_once("Auth/Auth.php");
        include_once("Services/Authentication/classes/class.ilSession.php");
        include_once("Services/Authentication/classes/class.ilSessionControl.php");
        require_once("./Services/AuthShibboleth/classes/class.ilShibboleth.php");
        include_once("./Services/Authentication/classes/class.ilAuthUtils.php");

        \ilAuthUtils::_initAuth();

        global $ilAuth;
        $ilAuth->start();

        require_once("./Services/Init/classes/class.ilIniFile.php");
        $ilIliasIniFile = new \ilIniFile("./ilias.ini.php");
        $ilIliasIniFile->read();
        $client = $ilIliasIniFile->readVariable("clients","default");

        $this->SID = (session_id().'::'.$client);
        return true;
    }


    /**
     * Execute a SOAP command on the server
     * a return the produced result.
     */
    public function executeSOAPFunction($str_function, $a_parameters) {
        $ilLog = $GLOBALS['ilLog'];
        $ilUser = $GLOBALS['ilUser'];
        $ilLog->write("executing SOAP function : uid: ".$ilUser->getId());

        include_once('webservice/soap/include/inc.soap_functions.php');
        $result = call_user_func_array('ilSoapFunctions::'.$str_function, $a_parameters);
        return $result;
    }


    /**
     * Replaces the SOAP logout method.
     * Destroys existing session.
     */
    public function logoutSOAP() {
        global $ilAuth;
        $ilAuth->logout();
        session_destroy();
        header_remove('Set-Cookie');
    }
}
