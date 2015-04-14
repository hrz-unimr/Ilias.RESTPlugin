<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 
 
require_once("./Services/Init/classes/class.ilInitialisation.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once("./Services/AccessControl/classes/class.ilRbacReview.php");


class ilInitialisation_Public extends ilInitialisation {
    public static function initGlobal_Public($a_name, $a_class, $a_source_file = null) {
        return self::initGlobal($a_name, $a_class, $a_source_file);
    }
    
    public static function initAccessHandling_Public() {
        return self::initAccessHandling();
    }
    
    public static function initSettings_Public() {
        return self::initSettings();
    }
}


/*
 *
 */
class RESTLib {
    /**
     * Initialize global instance
     *
     * @param string $a_name
     * @param string $a_class
     * @param string $a_source_file
     */
    public static function initGlobal($a_name, $a_class, $a_source_file = null) {
        return ilInitialisation_Public::initGlobal_Public($a_name, $a_class, $a_source_file);
    }

    
    /**
     * $ilAccess and $rbac... initialisation
     */
    public static function initAccessHandling() {
        return ilInitialisation_Public::initAccessHandling_Public();
    }

    
    /**
     * Init Settings
     * This function is needed to accomplish authentication.
     */
    public static function initSettings() {
        return ilInitialisation_Public::initSettings_Public();
    }

    
    /**
     * Checks if a user with a given login name owns the administration role.
     * @param $login
     * @return mixed
     */
    public static function isAdminByUsername($login) {
        $a_id = ilObjUser::searchUsers($login, 1, true);

        if (count($a_id) > 0) 
            return self::isAdmin($a_id[0]);
        return false;
    }

    
    /**
     * Checks if a user with a usr_id owns the administration role.
     * @param $usr_id
     * @return bool
     */
    public static function isAdmin($usr_id) {
        $rbacreview = new ilRbacReview();
        $is_admin = $rbacreview->isAssigned($usr_id,2);
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
        
        $query="SELECT obj_id FROM object_reference WHERE object_reference.ref_id=".$ref_id;
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
        $query="SELECT ref_id FROM object_reference WHERE object_reference.obj_id=".$obj_id;
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
        
        $res = array();
        $query="SELECT ref_id FROM object_reference WHERE object_reference.obj_id=".$obj_id;
        $set = $ilDB->query($query);

        while($row = $ilDB->fetchAssoc($set))
            $res[] = $row['ref_id'];
        
        return $res;
    }

    
    /**
     * Given a user id, this function returns the ilias login name of a user.
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
        return "User unknown";
    }
    

    /**
     * Given a user name, this function returns its ilias user_id.
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
        
        $fields = implode(',',$aFields);
        $query = "SELECT ".$fields." FROM object_data WHERE object_data.obj_id=".$obj_id;
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        
        return $row;
    }
    
    
    /** [RESTController\extensions\admin\models\RepositoryAdminModel.php]
     * Reads top 1 read event which occured on the object.
     *
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getLatestReadEventTimestamp($obj_id) {
        global $ilDB;

        $query = sprintf(' SELECT last_access FROM read_event '.
            'WHERE obj_id = %s '.
            'ORDER BY last_access DESC LIMIT 1',
            $ilDB->quote($obj_id,'integer'));
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);

        return $row['last_access'];
    }

    
    /** [RESTController\extensions\admin\models\RepositoryAdminModel.php]
     * Reads top-k read events which occured on the object.
     *
     * Tries to deliver a list with max -k items
     * @param $obj_id int the object id.
     * @return timestamp
     */
    public static function getTopKReadEventTimestamp($obj_id, $k) {
        global $ilDB;

        $query = sprintf(' SELECT last_access FROM read_event '.
            'WHERE obj_id = %s '.
            'ORDER BY last_access DESC LIMIT %s',
            $ilDB->quote($obj_id,'integer'),$k);
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
}
