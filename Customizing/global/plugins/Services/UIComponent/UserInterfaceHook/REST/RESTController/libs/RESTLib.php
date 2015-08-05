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
    const ID_NO_ADMIN = 'RESTController\libs\RESTLib::ID_NO_ADMIN';

    // Allow to re-use status-strings
    const MSG_NO_ADMIN = 'Access denied. Administrator permissions required.';


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
     * Sets ILIAS user-context to given user.
     * Will load user from token, if userId isn't provided
     * and token is available.
     *
     * Throws exception when using on route without auth-middleware!
     */
    public static function setupUserContext($userId = null) {
        if ($userId == null)
          $userId = self::getUserId();

        self::loadIlUser();
        $ilUser = $GLOBALS['ilUser'];
        $ilUser->setId($userId);
        $ilUser->read();
        self::initAccessHandling();
    }


    /**
     * Fetch current ILIAS-UserName from provided (OAuth2-)token.
     *
     * Throws exception when using on route without auth-middleware!
     */
    public static function getUserName() {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        return $accessToken->getUserName();
    }


    /**
     * Fetch current ILIAS-UserId from provided (OAuth2-)token.
     *
     * Throws exception when using on route without auth-middleware!
     */
    public static function getUserId() {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        return $accessToken->getUserId();
    }


    /**
    * Use this (or  $ilDB-quote(<value>, <type>) directly) to make sure
    * alle variables in an sql-statement are escaped (thus the query is safe).
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
            elseif (is_numeric($value)) // TODO: could be improved
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
     * Shortcut for loading ilObjUser via initGlobal
     */
    public static function loadIlUser() {
        self::initGlobal('ilUser', 'ilObjUser', './Services/User/classes/class.ilObjUser.php');
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


    /**
     * Determines the ILIAS object id (obj_id) given a ref_id
     *
     * @param $ref_id
     * @return mixed
     * @throws \Exception
     */
    static public function getObjIdFromRef($ref_id) {
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
    static public function getRefIdFromObj($obj_id) {
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
    static public function getRefIdsFromObj($obj_id) {
        global $ilDB;

        $sql = self::safeSQL('SELECT ref_id FROM object_reference WHERE object_reference.obj_id = %d', $obj_id);
        $query = $ilDB->query($sql);

        $res = array();
        while($row = $ilDB->fetchAssoc($query))
            $res[] = $row['ref_id'];

        return $res;
    }


    /**
     * Given a user id, this function returns the ilias login name of a user.
     *
     * @param $user_id
     * @return string
     */
    static public function getUserNameFromId($user_id) {
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
    static public function getIdFromUserName($login) {
        global $ilDB;

        $sql = self::safeSQL('SELECT usr_id FROM usr_data WHERE login=%s', $login);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        if ($row)
            return $row['usr_id'];
    }


    /**
     * Provides object properties as stored in table object_data.
     *
     * @param $obj_id
     * @param $fields array of strings; to query all fields please specify 'array('*')'
     * @return mixed
     */
    public static  function getObjectData($obj_id, $fields) {
        global $ilDB;

        // TODO: remove sprintf after safeSQL is fixed
        $sql = self::safeSQL('SELECT '. implode(', ', $fields) .' FROM object_data WHERE object_data.obj_id = %d', $obj_id);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        return $row;
    }


    /**
     * Reads top 1 read event which occurred on the object.
     *
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getLatestReadEventTimestamp($obj_id) {
        global $ilDB;

        $sql = self::safeSQL('SELECT last_access FROM read_event WHERE obj_id = %d ORDER BY last_access DESC LIMIT 1', $obj_id);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        return $row['last_access'];
    }
    /* <REMOVE THIS COMMENT>
     * Todo:
     *  This is only used in [extensions\admin\models\RepositoryAdminModel.php].
     * Suggestion:
     *  Move this method there?
     */


    /**
     * Reads top-k read events which occurred on the object.
     *
     * Tries to deliver a list with max -k items
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getTopKReadEventTimestamp($obj_id, $k) {
        global $ilDB;

        $sql = self::safeSQL('SELECT last_access FROM read_event WHERE obj_id = %d ORDER BY last_access DESC LIMIT %d', $obj_id, $k);
        $query = $ilDB->query($sql);
        $list = array();
        $cnt = 0;
        while ($row = $ilDB->fetchAssoc($query)){
            $list[] = $row['last_access'];
            $cnt = $cnt + 1;
            if ($cnt == $k) break;
        }

        return $list;
    }
    /* <REMOVE THIS COMMENT>
     * Todo:
     *  This is only used in [extensions\admin\models\RepositoryAdminModel.php].
     * Suggestion:
     *  Move this method there?
     */

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
        $user_name = RESTLib::getUserNameFromId($user_id);

        header_remove('Set-Cookie');
        \ilUtil::setCookie("ilClientId", CLIENT_ID);

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

        ilInitialisation_Public::setSessionHandler(); // will put an entry in usr_session table

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
