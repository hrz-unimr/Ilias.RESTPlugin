<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;
 
 
// Thi include ILIAS init, ILIAS user, ILIAS role management code
require_once("./Services/Init/classes/class.ilInitialisation.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once("./Services/AccessControl/classes/class.ilRbacReview.php");
// Requires <$ilDB>


/*
 * This class provides some common utility functions
 * that should be usefull for many models/routes, such
 * as "loading" ILIAS classes, working with ILIAS users
 * converting between different id (ref, obj).
 */
class RESTLib {
    /**
     * @see ilInitialisation::initGlobal($a_name, $a_class, $a_source_file)
     */
    public static function initGlobal($a_name, $a_class, $a_source_file = null) {
        return ilInitialisation_Public::initGlobal_Public($a_name, $a_class, $a_source_file);
    }
    /* <REMOVE THIS COMMENT>
     * Todo: 
     *  Remaining "artifact" of code-refactoring. This is really only used to load the following classes:
     *   ilObjDataCache, objDefinition, ilSetting, ilAppEventHandler, rbacreview, rbacadmin
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
     * Shortcut for loading ilObjUser via initGlobal
     */
    public static function loadIlUser() {
        self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
    }

    
    /**
     * Checks if a user with a given login name owns the administration role.
     *
     * @param $user_name
     * @return mixed
     */
    public static function isAdminByUsername($user_name) {
        $a_id = \ilObjUser::searchUsers($user_name, 1, true);

        if (count($a_id) > 0) 
            return self::isAdmin($a_id[0]);
        return false;
    }

    
    /**
     * Checks if a user with a usr_id owns the administration role.
     *
     * @param $usr_id
     * @return bool
     */
    public static function isAdmin($usr_id) {
        $rbacreview = new \ilRbacReview();
        $is_admin = $rbacreview->isAssigned($usr_id, 2);
        
        return $is_admin;
    }

    /**
     * Determines the ILIAS object id (obj_id) given a ref_id
     *
     * @param $ref_id
     * @return mixed
     */
    static public function refid_to_objid($ref_id) {
        global $ilDB;
        
        $query = "SELECT obj_id FROM object_reference WHERE object_reference.ref_id=".$ref_id;
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);

        return $row['obj_id'];
    }

    
    /**
     * Determines the first (among potential many) ref_id's that are associated with
     * an ILIAS object identified by an obj_id.
     *
     * @param $obj_id
     * @return mixed
     */
    static public function objid_to_refid($obj_id) {
        global $ilDB;
        
        $query = "SELECT ref_id FROM object_reference WHERE object_reference.obj_id=".$obj_id;
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);
        
        return $row['ref_id'];
    }

    
    /**
     * Determines all ref_ids that are associated with a particular ILIAS object
     * identified by its obj_id.
     *
     * @param $obj_id
     * @return array
     */
    static public function objid_to_refids($obj_id) {
        global $ilDB;
        
        $query = "SELECT ref_id FROM object_reference WHERE object_reference.obj_id=".$obj_id;
        $set = $ilDB->query($query);

        $res = array();
        while($row = $ilDB->fetchAssoc($set))
            $res[] = $row['ref_id'];
        
        return $res;
    }

    
    /**
     * Given a user id, this function returns the ilias login name of a user.
     *
     * @param $user_id
     * @return string
     */
    static public function userIdtoLogin($user_id) {
        global $ilDB;
        
        $query = "SELECT login FROM usr_data WHERE usr_id=\"".$user_id."\"";
        $set = $ilDB->query($query);
        $ret = $ilDB->fetchAssoc($set);
        
        if ($ret) 
            return $ret['login'];
    }
    

    /**
     * Given a user name, this function returns its ilias user_id.
     *
     * @param login - user_name
     * @return user_id
     */
    static public function loginToUserId($login) {
        global $ilDB;
        
        $query = "SELECT usr_id FROM usr_data WHERE login=\"".$login."\"";
        $set = $ilDB->query($query);
        $ret = $ilDB->fetchAssoc($set);
        
        if ($ret) 
            return $ret['usr_id'];
    }
    
    
    /**
     * Provides object properties as stored in table object_data.
     *
     * @param $obj_id
     * @param $aFields array of strings; to query all fields please specify "array('*')"
     * @return mixed
     */
    public static  function getObjectData($obj_id, $aFields) {
        global $ilDB;
        
        $fields = implode(',', $aFields);
        $query = "SELECT ".$fields." FROM object_data WHERE object_data.obj_id=".$obj_id;
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        
        return $row;
    }
    
    
    /**
     * Reads top 1 read event which occured on the object.
     *
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getLatestReadEventTimestamp($obj_id) {
        global $ilDB;

        $query = sprintf("
            SELECT last_access FROM read_event 
            WHERE obj_id = %s 
            ORDER BY last_access DESC LIMIT 1
            ", $ilDB->quote($obj_id,'integer')
        );
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);

        return $row['last_access'];
    }
    /* <REMOVE THIS COMMENT>
     * Todo: 
     *  This is only used in [extensions\admin\models\RepositoryAdminModel.php]. 
     * Suggestion:
     *  Move this method there?
     */

    
    /** 
     * Reads top-k read events which occured on the object.
     *
     * Tries to deliver a list with max -k items
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getTopKReadEventTimestamp($obj_id, $k) {
        global $ilDB;

        $query = sprintf("
            SELECT last_access FROM read_event 
            WHERE obj_id = %s 
            ORDER BY last_access DESC LIMIT %s
            ", $ilDB->quote($obj_id,'integer'), $k
        );
        $res = $ilDB->query($query);
        $list = array();
        $cnt = 0;
        while ($row = $ilDB->fetchAssoc($res)){
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
}


/**
 * Helper class that derives from ilInitialisation in order
 * to "publish" some of its methods that are (currently) 
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
