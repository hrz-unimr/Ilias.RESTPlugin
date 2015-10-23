<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\auth as Auth;

// Include ILIAS init, ILIAS user, ILIAS role management code
require_once('./Services/Init/classes/class.ilInitialisation.php');
require_once('./Services/User/classes/class.ilObjUser.php');
require_once('./Services/AccessControl/classes/class.ilRbacReview.php');
// Requires <$ilDB>


/*
 * This class provides some common utility functions
 * that should be usefull for many models/routes, such
 * as 'loading' ILIAS classes, working with ILIAS users
 * converting between different id (ref, obj).
 */
class RESTLib {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_IP_NOT_ALLOWED   = 'RESTController\libs\RESTLib::ID_IP_NOT_ALLOWED';
    const ID_NO_ADMIN         = 'RESTController\libs\RESTLib::ID_NO_ADMIN';
    const ID_PARSE_ISSUE      = 'RESTController\libs\RESTLib::ID_PARSE_ISSUE';

    // Allow to re-use status-strings
    const MSG_IP_NOT_ALLOWED  = 'Access denied for client IP address.';
    const MSG_NO_ADMIN        = 'Access denied. Administrator permissions required.';
    const MSG_PARSE_ISSUE     = 'Could not parse id(s) %s from %s.';

    /**
     * @see ilInitialisation::initGlobal($a_name, $a_class, $a_source_file)
     */
    public static function initGlobal($a_name, $a_class, $a_source_file = null) {
        return ilInitialisation_Public::initGlobal_Public($a_name, $a_class, $a_source_file);
    }
    /* <REMOVE THIS COMMENT>
     * Todo:
     *  Remaining 'artifact' of code-refactoring. This is really only used to load the following classes:
     *   ilObjDataCache, objDefinition, ilSetting, ilAppEventHandler, rbacreview, rbacadmin, rbacsystem
     *  Some of of which seems to be used for debugging/development only.
     * Suggestion:
     *  Replace initGlobal(...) with methods similar to loadIlUser().
     */


    /**
     * Load ILIAS user-management. Normally this would be handled by initILIAS(),
     * but CONTEXT:REST (intentionally) returns hasUser()->false. (Which causes
     * initAccessHandling() [and authentification] to be skipped)
     *
     * @see ilInitialisation::initAccessHandling()
     */
    public static function initAccessHandling() {
        return ilInitialisation_Public::initAccessHandling_Public();
    }


    /**
     * Shortcut for loading ilObjUser via initGlobal.
     * Will load user from token, if userId isn't provided
     * and token is available.
     *
     * Throws exception when using on route without auth-middleware!
     */
    public static function loadIlUser($userId = null) {
        // Include ilObjUser and initialize
        self::initGlobal('ilUser', 'ilObjUser', './Services/User/classes/class.ilObjUser.php');

        // Fetch user-id from token if non is given
        if ($userId == null) {
          $auth         = new Auth\Util();
          $accessToken  = $auth->getAccessToken();
          $userId       = $accessToken->getUserId();
        }

        // Create user-object if id is given
        global $ilUser, $ilias;
        $ilUser->setId($userId);
        $ilUser->read();
        self::initAccessHandling();
        $ilias->account = $ilUser;

        return $ilUser;
    }


    /**
    * Use this (or  $ilDB-quote(<value>, <type>) directly) to make sure
    * alle variables in an sql-statement are escaped (thus the query is safe).
    *
    * NOTE: This is a pretty rough implementation that allowed minimal changes
    *       to existing code. Thus it MIGHT not catch all corner cases!
    */
    public static function safeSQL($sql) {
        $params = func_get_args();
        $params = array_slice($params, 1);
        $params = self::quoteParams($params);

        return vsprintf($sql, $params);
    }
    protected static function quoteParams($params) {
        global $ilDB;

        $result = array();
        foreach ($params as $key => $value) {
            if (is_bool($value))
                $result[] = $ilDB->quote($value, 'boolean');
            elseif (is_array($value)) {
                $value = self::safeParams($value);
                $value = sprintf('(%s)', implode(', ', $value));
                $result[] = $value;
            }
            elseif (is_int($value))
                $result[] = $ilDB->quote($value, 'integer');
            elseif (is_integer($value))
                $result[] = $ilDB->quote($value, 'integer');
            elseif (is_float($value))
                $result[] = $ilDB->quote($value, 'float');
            elseif (is_double($value))
                $result[] = $ilDB->quote($value, 'float');
            elseif (is_numeric($value))
                $result[] = $ilDB->quote($value, 'float');
            elseif (is_string($value))
                $result[] = $ilDB->quote($value, 'text');
            // Fallback solution
            else
                $result[] = $value;
        }

        return $result;
    }


    /**
     * Checks if a user with a given login name owns the administration role.
     *
     * @param $user_name
     * @return mixed
     */
    public static function isAdminByUserName($user_name) {
        if ($user_name) {
            $a_id = \ilObjUser::searchUsers($user_name, 1, true);

            if (count($a_id) > 0)
                return self::isAdminByUserId($a_id[0]);
        }
        return false;
    }


    /**
     * Checks if a user with a usr_id owns the administration role.
     *
     * @param $usr_id
     * @return bool
     */
    public static function isAdminByUserId($usr_id) {
        if ($usr_id) {
            $rbacreview = new \ilRbacReview();
            $is_admin = $rbacreview->isAssigned($usr_id, 2);

            return $is_admin;
        }
        return false;
    }


    /**
     * Authentication via the ILIAS Auth mechanisms.
     * This method is used as backend for OAuth2.
     *
     * @param $username - ILIAS user-id
     * @param $password - ILIS user-password
     * @return bool - True if authentication was successfull, false otherwise
     */
    public static function authenticateViaIlias($username, $password) {
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


    /**
     * Determines the ILIAS object id (obj_id) given a ref_id
     *
     * @param $ref_id
     * @return mixed
     * @throws \Exception
     */
    public static function getObjIdFromRef($ref_id) {
        global $ilDB;

        $sql = self::safeSQL('SELECT obj_id FROM object_reference WHERE object_reference.ref_id = %d', intval($ref_id));
        $query = $ilDB->query($sql);
        $ilDB->numRows($query);

        if ($ilDB->numRows($query) == 0)  throw new \Exception('Entry with ref_id '.$ref_id.' does not exist.');
        $row = $ilDB->fetchAssoc($query);

        return $row['obj_id'];
    }


    /**
     * Determines the first (among potential many) ref_id's that are associated with
     * an ILIAS object identified by an obj_id.
     *
     * @param $obj_id
     * @return mixed
     * @throws \Exception
     */
    public static function getRefIdFromObj($obj_id) {
        global $ilDB;

        $sql = self::safeSQL('SELECT ref_id FROM object_reference WHERE object_reference.obj_id = %d', intval($obj_id));
        $query = $ilDB->query($sql);
        if ($ilDB->numRows($query) == 0)  throw new \Exception('Entry with obj_id '.$obj_id.' does not exist.');
        $row = $ilDB->fetchAssoc($query);

        return $row['ref_id'];
    }


    /**
     * Determines all ref_ids that are associated with a particular ILIAS object
     * identified by its obj_id.
     *
     * @param $obj_id
     * @return array
     */
    public static function getRefIdsFromObj($obj_id) {
        global $ilDB;

        $sql = self::safeSQL('SELECT ref_id FROM object_reference WHERE object_reference.obj_id = %d', $obj_id);
        $query = $ilDB->query($sql);

        $res = array();
        while($row = $ilDB->fetchAssoc($query))
            $res[] = $row['ref_id'];

        return $res;
    }


    /**
     * Returns the perma link of a repository object specified by its ref_id.
     * @param $ref_id
     * @return string
     * @throws \Exception
     */
    public static function getPermanentLink($ref_id) {
        global $ilObjDataCache;

        $obj_id     = self::getObjIdFromRef($ref_id);
        $type       = $ilObjDataCache->lookupType($obj_id);
        //$permaLink  = self::getBaseUrl().'goto.php'.'?target='.$type.'_'.$ref_id.'&client_id='.CLIENT_ID;

        // TODO: FIX ME
        $permaLink  = 'http://ilias.me/goto.php'.'?target='.$type.'_'.$ref_id.'&client_id='.CLIENT_ID;

        return $permaLink;
    }


    /**
     * Given a user id, this function returns the ilias login name of a user.
     *
     * @param $user_id
     * @return string
     */
    public static function getUserNameFromUserId($user_id) {
        global $ilDB;

        $sql = self::safeSQL('SELECT login FROM usr_data WHERE usr_id=%s', $user_id);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        if ($row)
            return $row['login'];
    }


    /**
     * Given a user name, this function returns its ilias user_id.
     *
     * @param login - user_name
     * @return user_id
     */
    public static function getUserIdFromUserName($login) {
        global $ilDB;

        $sql = self::safeSQL('SELECT usr_id FROM usr_data WHERE login=%s', $login);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        if ($row)
            return $row['usr_id'];
    }


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



    /**
     * Parse a list of coma-separated numeric values (ids)
     * into an array. (Works for integers)
     *
     * @param $idString - String that should be parsed.
     * @param $throwException - Throw exception of string does not contain parseable numeric values
     *
     * @return array of parsed values (integer)
     *
     * @throws Exceptions\IdParseProblem - Thrown when string does not contain numeric elements (only if $throwException is true)
     */
    public static function parseIdsFromString($idString, $throwException = false) {
        // Parse string with coma as separator
        $ids    = explode(',', $idString);
        $throws = array();
        foreach($ids as $key => $id) {
            // Can be converted to int?
            if (is_numeric($id))
                $ids[$key] = intval($id);
            // Drop value (and throw exception)
            else {
              if ($throwException)
                $throws[$key] = $id;
              $ids[$key] = null;
            }
        }

        // Filter unverted values
        $ids = array_filter($ids, function($value) { return !is_null($value); });

        // In case an exception needs to be thrown, the message will be build here
        if (count($throws) > 0) {
          $idString     = htmlspecialchars($idString);
          foreach ($throws as $key => $id)
              $throws[$key] = sprintf('%d: \'%s\'', $key + 1, $id);
          $throwString  = implode(', ', $throws);
          $message      = sprintf(self::MSG_PARSE_ISSUE, $throwString, 'String-List: \'' . $idString . '\'');

          // Throw it!
          throw new Exceptions\IdParseProblem($message, self::ID_PARSE_ISSUE);
        }

        // Return ids (array of integers)
        return $ids;
    }


    /**
     * Creates a responseObject from given $data and $status
     *  Should be used whenever someone wants to emulate
     *  $app->success(...) or $app->halt(...) response
     *  without actually transmitting and terminating
     *  said response.
     */
    public static function responseObject($data, $status) {
        // Add a status-code to response object?
        if ($status != null) {
            // Do NOT overwrite status key inside $data
            if (is_array($data))
                $data['status'] = ($data['status']) ?: $status;
            // If data is not empty, construct array with status and original data
            elseif ($data != '')
                $data = array(
                  'status'  => $status,
                  'msg'     => $data
                );
        }

        return $data;
    }
}


/**
 * Helper class that derives from ilInitialisation in order
 * to 'publish' some of its methods that are (currently)
 * required by RESTLib (some routes/models).
 *
 * We aren't extending RESTLib directly for two reasons:
 *  - Keep the RESTLib as clean as possible of any ILIAS code/method
 *    (Reduce dependencies as much as possible)
 *  - PHP does not allow multiple inheritance (IFF we ever really
 *    needed to access another classes protected methods)
 *
 * !!! PLEASE DO NOT USE THIS CLASS OUTSIDE OF RESTLIB !!!
 */
class ilInitialisation_Public extends \ilInitialisation {
    /**
     * @see ilInitialisation::initGlobal($a_name, $a_class, $a_source_file)
     */
    public static function initGlobal_Public($a_name, $a_class, $a_source_file = null) {
        return self::initGlobal($a_name, $a_class, $a_source_file);
    }


    /**
     * @see ilInitialisation::initAccessHandling()
     */
    public static function initAccessHandling_Public() {
        return self::initAccessHandling();
    }

}
