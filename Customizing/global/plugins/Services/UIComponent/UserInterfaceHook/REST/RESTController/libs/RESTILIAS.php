<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// This allows us to use shortcuts instead of full quantifier
// Requires <$ilDB>
use \RESTController\core\auth as Auth;


/**
 * This class provides some common utility functions
 * that should be usefull for many models/routes, such
 * as 'loading' ILIAS classes, working with ILIAS users
 * converting between different id (ref, obj).
 */
class RESTILIAS {
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
          $accessToken  = Auth\Util::getAccessToken();
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
     * Returns the path to the RESTPlugin folder
     */
    public static function getPluginDir() {
      global $ilPluginAdmin;
      return $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'REST')->getDirectory();
    }


    /**
     * Checks if a user with a given login name owns the administration role.
     *
     * @param $user_name
     * @return mixed
     */
    public static function isAdminByUserName($user_name) {
        if ($user_name) {
            require_once('./Services/User/classes/class.ilObjUser.php');
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
            require_once('./Services/AccessControl/classes/class.ilRbacReview.php');
            $rbacreview = new \ilRbacReview();
            $is_admin = $rbacreview->isAssigned($usr_id, 2);

            return $is_admin;
        }
        return false;
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
require_once('./Services/Init/classes/class.ilInitialisation.php');
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
