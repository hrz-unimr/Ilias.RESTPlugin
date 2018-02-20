<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;



class ReportingModel extends Libs\RESTModel {

    /**
     * Returns a list of active user session sorted by its create date.
     * By this, those user sessions are listed that have the longest lifespan (possible power users).
     * An entry of the list consists of an abbreviation of the SessID, the UserID and timing information: create, last_action and expires.
     * Expires is the expected date of expiration of no action is taken anymore.
     */
    public static function GetActiveSessions()
    {
        return ReportingModel::GetLoggedInUsers();
    }

    /**
     * Returns a list of user sessions that are not active in the sense, that a session cookie has been set but is not associated with a particular user.
     * Passive session cases:
     *  (i) entered ILIAS site, but not yet entered user credentials.
     *  (ii) anonymous user, e.g. in a public area of ILIAS
     */
    public static function GetPassiveSessions()
    {
        return ReportingModel::GetNotLoggedInUsers();
    }

    /**
     * Returns a list of user sessions that are expired.
     */
    public static function GetExpiredSessions()
    {
        return ReportingModel::GetObsoleteSessions();
    }

    /**
     * Returns counts of active, passive and expired sessions.
     */
    public static function GetSessionStatistics()
    {
        $result = array();
        global $ilDB;

        $sql = "SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)>NOW() AND user_id>0 ORDER BY createtime";
        $set = $ilDB->query($sql);
        $nRows = $ilDB->numRows($set);
        $result['active'] = $nRows;

        $sql = "SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)>NOW() AND user_id=13 ORDER BY createtime";
        $set = $ilDB->query($sql);
        $nRows = $ilDB->numRows($set);
        $result['public_area'] = $nRows;

        $sql="SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)>NOW() AND user_id=0 ORDER BY createtime";
        $set = $ilDB->query($sql);
        $nRows = $ilDB->numRows($set);
        $result['passive'] = $nRows;

        $sql="SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)<NOW() ORDER BY createtime";
        $set = $ilDB->query($sql);
        $nRows = $ilDB->numRows($set);
        $result['expired'] = $nRows;
        return $result;
    }

    /**
     * SQL query for "logged-in-users".
     * @return array
     */
    private static function GetLoggedInUsers()
    {
        global $ilDB;
        $sql = Libs\RESTDatabase::safeSQL("SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)>NOW() AND user_id>0 ORDER BY createtime");
        $result = $ilDB->query($sql);
        $a_data=array();
        while (($row = $ilDB->fetchAssoc($result))!=false){
            $sess_id=$row['session_id'];
            $entry=array('session_id' => $sess_id, 'user_id' => $row['user_id'], 'create' => $row['createtime'], 'lastaction' => $row['ctime'],'expires'=>$row['expires']);
            $a_data[]=$entry;
        }
        return $a_data;
    }

    /**
     * SQL query for "NOT logged-in-users"
     * @return array
     */
    private static function GetNotLoggedInUsers()
    {
        global $ilDB;
        $sql="SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)>NOW() AND user_id=0 ORDER BY createtime";
        $result = $ilDB->query($sql);
        $a_data=array();
        while (($row = $ilDB->fetchAssoc($result))!=false){
            $sess_id=$row['session_id'];
            $entry=array('session_id' => $sess_id, 'user_id' => $row['user_id'], 'create' => $row['createtime'], 'lastaction' => $row['ctime'],'expires'=>$row['expires']);
            $a_data[]=$entry;
        }
        return $a_data;
    }

    /**
     * SQL query for "expired sessions".
     * @return array
     */
    private static function GetObsoleteSessions()
    {
        global $ilDB;
        $sql="SELECT session_id, user_id, createtime, ctime, expires FROM usr_session WHERE FROM_UNIXTIME(expires)<NOW() ORDER BY createtime";
        $result = $ilDB->query($sql);
        $a_data=array();
        while (($row = $ilDB->fetchAssoc($result))!=false){
            $sess_id=$row['session_id'];
            $entry=array('session_id' => $sess_id, 'user_id' => $row['user_id'], 'create' => $row['createtime'], 'lastaction' => $row['ctime'],'expires'=>$row['expires']);
            $a_data[]=$entry;
        }
        return $a_data;
    }

    /**
     * Returns the number of ilias sessions that have been created within the last 60 - minutes.
     * @return array
     */
    public static function GetSessionsHourly()
    {
        global $ilDB;
        $a_data=array();

        $sql="SELECT count(*) as logged_in_hourly FROM usr_data WHERE UNIX_TIMESTAMP( last_login ) > ( UNIX_TIMESTAMP( ) -3600 ) AND UNIX_TIMESTAMP( last_login ) < UNIX_TIMESTAMP( )";
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data['logged_in_hourly'] = $row['logged_in_hourly'];
        }

        $sql = "SELECT count(*) as access_hourly FROM ut_online WHERE access_time > ( UNIX_TIMESTAMP( ) -3600 ) AND access_time < UNIX_TIMESTAMP( )";
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data['access_hourly'] = $row['access_hourly'];
        }

        $sql = "Select count(user_id) as active_user_count from usr_session where from_unixtime(ctime+60*60) > now()";
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data['active_user_count'] = $row['active_user_count'];
        }


        $sql = "Select count(distinct user_id) as active_user_count_distinct from usr_session where from_unixtime(ctime+60*60) > now()";
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data['active_user_count_distinct'] = $row['active_user_count_distinct'];
        }

        return $a_data;
    }

    /**
     * Returns the number of ilias sessions that have been created within the last 24 hours.
     * @return array
     */
    public static function GetSessionsDaily()
    {
        global $ilDB;
        $a_data=array();

        $sql="SELECT count(*) as access_daily FROM ut_online WHERE access_time > ( UNIX_TIMESTAMP( ) - 86400 ) AND access_time < UNIX_TIMESTAMP( );";
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data['access_daily'] = $row['access_daily'];
        }

        $sql = "SELECT count(*) as logged_in_today FROM usr_data WHERE UNIX_TIMESTAMP( last_login ) > ( UNIX_TIMESTAMP( ) -86400 ) AND UNIX_TIMESTAMP( last_login ) < UNIX_TIMESTAMP( )";
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data['logged_in_today'] = $row['logged_in_today'];
        }

        return $a_data;
    }

    /**
     * Returns the current absolte numbers of ilias repository objects.
     * @return array
     */
    public static function GetObjectStatistics()
    {
        global $ilDB;
        $a_data=array();

        $sql="SELECT type, count(*) AS freq FROM object_data group by type UNION
SELECT id, count(type) FROM il_object_def LEFT JOIN object_data ON object_data.type=il_object_def.id GROUP BY id ORDER BY type ASC";
        $result = $ilDB->query($sql);
        while (($row = $ilDB->fetchAssoc($result))!=false) {
            $a_data[] = $row;
        }

        return $a_data;
    }

    /**
     * Returns access statistics on repository objects from the last 24 hours.
     *
     * In addition to the access statistics of an object also its
     * current obj_id , its current title and its position within the
     * repository hierarchy are provided.
     */
    public static function GetRepositoryStatistics()
    {
        global $ilDB;
        $a_data=array();
        $ts_now=time();
        $ts_yesterday=$ts_now-60*60*24;
        $sql="SELECT obj_id, COUNT(*) AS qty FROM `read_event` WHERE last_access >= ".$ts_yesterday." AND last_access <= ".$ts_now." GROUP BY obj_id ORDER BY qty  DESC";
        $result = $ilDB->query($sql);
        while (($row = $ilDB->fetchAssoc($result))!=false){
            $a_row=array();

            $obj_id =  $row['obj_id'];
            $a_row['obj_id'] = $obj_id;
            $a_row['qty'] = $row['qty'];
            $temp = ReportingModel::GetBasicObjectInfo($obj_id);
            $title=$temp[0];
            $type=$temp[1];
            $a_row['title'] = $title;
            $a_row['type'] = $type;
            $a_row['hierarchy'] = ReportingModel::GetHierarchyInfo(Libs\RESTilias::getRefId($obj_id));

            $a_data[] = $a_row;
        }

        return $a_data;
    }

    /**
     * Helper function for get_repository_statistics().
     * This function returns the title and the type of a repository object.
     *
     * @param $obj_id
     * @return array
     */
    private static function GetBasicObjectInfo($obj_id)
    {
        global $ilDB;
        $sql="SELECT object_data.title,object_data.type FROM object_data WHERE obj_id=".$obj_id;
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            return array($row['title'],$row['type']);
        }
        return array('title'=>'','type'=>'');
    }

    /**
     * Helper function for get_repository_statistics.
     * This function creates a string which explains the location of the object within the repository hierarchy.
     *
     * @param $ref_id
     * @return string
     */
    private static function GetHierarchyInfo($ref_id){
        global $ilDB;
        $a_ref_ids=array();

        $parent=$ref_id;
        while($parent>1){
            $parent = ReportingModel::GetNextParent($parent);
            if ($parent > 1){
                $a_ref_ids[]=(int)$parent;
            }
        }

        $hierarch_str="";
        $levels=count($a_ref_ids);
        for ($i=0;$i<$levels;$i++){
            $r_id=$a_ref_ids[$i];
            $sql="SELECT object_data.title FROM object_reference LEFT JOIN object_data ON object_data.obj_id=object_reference.obj_id WHERE object_reference.ref_id=".$r_id;
            $result = $ilDB->query($sql);
            $row = $ilDB->fetchAssoc($result);
            $title=$row['title'];
            $hierarch_str.=$title;
            if ($i<$levels-1){
                $hierarch_str.="%%";
            }
        }
        return $hierarch_str;
    }

    /**
     * Helper function for GetHierarchyInfo
     * @param $rid
     * @return string
     */
    private static function GetNextParent($rid){
        global $ilDB;
        $sql = "SELECT parent FROM tree WHERE child=".$rid;
        $result = $ilDB->query($sql);
        if (($row = $ilDB->fetchAssoc($result))!=false) {
            return $row['parent'];
        }
        return "";
    }

}
