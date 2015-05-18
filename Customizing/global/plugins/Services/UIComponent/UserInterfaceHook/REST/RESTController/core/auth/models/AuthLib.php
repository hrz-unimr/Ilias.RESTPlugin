<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
// Requires <$ilias>, <$ilPluginAdmin>, <$ilSession>, <$ilSessionControl>, <$Auth>, <$ilShibboleth>, <$ilAuthUtils>, <$ilObjUser>
// Requires RESTLib.php


/*
 * This class provides Utility functions related
 * to OAuth/ILIAS user-authentication.
 */
class AuthLib {
    // Allow to re-use status-strings
    const MSG_UC_DISABLED = 'User-credentials grant-type is disabled for this client.';
    const MSG_CC_DISABLED = 'Client-credentials grant-type is disabled for this client.';
    const MSG_AC_DISABLED = 'Authorization-code grant-type is disabled for this client.';
    const MSG_I_DISABLED = 'Implicit grant-type is disabled for this client.';

    /**
     * Initializes ILIAS user application class
     * with given ILIAS user-id.
     *
     * @param $login - ILIAS user id to use as context
     * @return bool - True if context-creation was (probably) successfull, false otherwise
     */
    static public function setUserContext($login) {
        global $ilias;

        require_once('./Services/User/classes/class.ilObjUser.php');
        $userId = \ilObjUser::_lookupId($login);
        if (!$userId)
            return false;

        $ilUser = new \ilObjUser($userId);
        $ilias->account =& $ilUser;
        RESTLib::loadIlUser();

        return true;
    }


    /**
     * Authentication via the ILIAS Auth mechanisms.
     * This method is used as backend for OAuth2.
     *
     * @param $username - ILIAS user-id
     * @param $password - ILIS user-password
     * @return bool - True if authentication was successfull, false otherwise
     */
    static public function authenticateViaIlias($username, $password) {
        RESTLib::initAccessHandling();

        $_POST['username'] = $username;
        $_POST['password'] = $password;

        require_once('Auth/Auth.php');
        require_once('Services/Authentication/classes/class.ilSession.php');
        require_once('Services/Authentication/classes/class.ilSessionControl.php');
        require_once('Services/AuthShibboleth/classes/class.ilShibboleth.php');
        require_once('Services/Authentication/classes/class.ilAuthUtils.php');

        \ilAuthUtils::_initAuth();

        global $ilAuth;
        $ilAuth->start();
        $checked_in = $ilAuth->getAuth();

        $ilAuth->logout();
        session_destroy();
        header_remove('Set-Cookie');

        return $checked_in;
    }


    /*
     * Checks if provided OAuth2 client credentials are valid.
     * Compare with http://tools.ietf.org/html/rfc6749#section-4.4 (client credentials grant type).
     *
     * @param int api_key
     * @param string api_secret
     * @return bool
     */
    static public function checkOAuth2ClientCredentials($api_key, $api_secret) {
        global $ilDB;

        // Fetch client with given api-key (checks existance)
        $query = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s" AND api_secret = "%s"', $api_key, $api_secret);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0)
            return true;
        return false;
    }


    /**
     * Checks if provided OAuth2 - client (aka api_key) does exist.
     *
     * @param  api_key
     * @return bool
     */
    static public function checkOAuth2Client($api_key) {
        global $ilDB;

        // Fetch client with given api-key (checks existance)
        $query = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0)
            return true;
        return false;
    }


    /**
     * Checks if a rest client is allowed to enter a route (aka REST endpoint).
     *
     * @param route
     * @param operation
     * @param api_key
     * @return bool
     */
    static public function checkOAuth2Scope($route, $operation, $api_key) {
        global $ilDB;

        $operation = strtoupper($operation);
        $query = sprintf('
            SELECT pattern, verb
            FROM ui_uihk_rest_perm
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_keys.api_key = "%s"
            AND ui_uihk_rest_keys.id = ui_uihk_rest_perm.api_id
            AND ui_uihk_rest_perm.pattern = "%s"
            AND ui_uihk_rest_perm.verb = "%s"',
            $api_key,
            $route,
            $operation
        );
        $set = $ilDB->query($query);
        if ($ilDB->fetchAssoc($set))
            return true;
        return false;
    }


    /**
     * Checks if an ILIAS session is valid and belongs to a particular user.
     * And furthermore if rToken is valid.
     *
     * @see Services/UICore/classes/class.ilCtrl.php
     * @see Services/Authentication/classes/ilSessionControl.php
     *
     * @param $user_id
     * @param $rtoken
     * @param $session_id
     * @return bool
     */
    static public function authFromIlias($user_id, $rtoken, $session_id) {
        global $ilDB;

        $rtokenValid = false;
        $sessionValid = false;

        $query = sprintf('
            SELECT * FROM il_request_token
            WHERE user_id = %d
            AND token = "%s"
            AND session_id = "%s"',
            $user_id,
            $rtoken,
            $session_id
        );
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0)
            $rtokenValid = true;

        $query = sprintf('
            SELECT * FROM usr_session
            WHERE user_id = %d
            AND session_id = "%s"',
            $user_id,
            $session_id
        );
        $set = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($set))
            if ($row['expires'] > time())
                $sessionValid = true;

        return $rtokenValid && $sessionValid;
    }
}
