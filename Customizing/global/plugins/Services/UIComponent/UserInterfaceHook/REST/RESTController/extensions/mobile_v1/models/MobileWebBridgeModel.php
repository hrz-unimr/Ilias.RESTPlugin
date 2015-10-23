<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;


class MobileWebBridgeModel
{
    /**
     * Returns the web directory, where e.g. learning modules are located.
     * In contrast to ilUtil::getWebDir() this functions  returns the
     * dir path without any prefix.
     * @return string
     */
    public static function getWebDir()
    {
        return ILIAS_WEB_DIR."/".CLIENT_ID;
    }


    /**
     * Returns the URL of the current ILIAS installation.
     * @return string
     */
    static public function getBaseUrl() {
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https://';
        }
        else {
            $protocol = 'http://';
        }
        $domainName = $_SERVER['HTTP_HOST'];
        if ($GLOBALS['COOKIE_PATH']=='/') {
            $baseUrl = $protocol.$domainName.'/';
        } else {
            $baseUrl = $protocol.$domainName.$GLOBALS['COOKIE_PATH'].'/';
        }
        return $baseUrl;
    }


    /**
     * Returns the perma link of a repository object specified by its ref_id.
     * @param $ref_id
     * @return string
     * @throws \Exception
     */
    static public function getPermanentLink($ref_id) {
        $obj_id = self::getObjIdFromRef($ref_id);
        $type = self::getTypeOfObject($obj_id);
        // mimics Services/Link/../class.ilLink.php::_getStaticLink
        $permaLink = self::getBaseUrl().'goto.php'.'?target='.$type.'_'.$ref_id.'&client_id='.CLIENT_ID;
        return $permaLink;
    }


    /**
     * Initiates an ILIAS Session for a user specified by $user_id.
     * (Requires ILIAS >5.0)
     * @param $user_id
     */
    public static function initSession($user_id)
    {
        global $ilLog;
        $user_name = RESTLib::getUserNameFromUserId($user_id);

        require_once('Auth/Auth.php');
        require_once('Services/Authentication/classes/class.ilSession.php');
        require_once('Services/Authentication/classes/class.ilSessionControl.php');
        require_once('Services/AuthShibboleth/classes/class.ilShibboleth.php');
        require_once('Services/Authentication/classes/class.ilAuthUtils.php');

        \ilAuthUtils::_initAuth();

        global $ilAuth;
        $ilAuth->setAuth($user_name);
        $ilAuth->start();
        $checked_in = $ilAuth->getAuth();

        $ilLog->write('Custom session via REST initiated. Check in value: '.$checked_in);

        \ilSession::set("AccountId", $user_id);
        \ilSession::set('orig_request_target', '');

        header_remove('Set-Cookie');
        \ilUtil::setCookie("ilClientId", CLIENT_ID);

        \ilInitialisation::setSessionHandler(); // will put an entry in usr_session table
    }
}
