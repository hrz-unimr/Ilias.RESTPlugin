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

require_once('./Services/Database/classes/class.ilAuthContainerMDB2.php');


class ReportingModel extends Libs\RESTModel {

    /**
     * Returns a list of active user session sorted by its create date.
     * By this, those user sessions are listed that have the longest lifespan (possible power users).
     * An entry of the list consists of an abbreviation of the SessID, the UserID and timing information: create, last_action and expires.
     * Expires is the expected date of expiration of no action is taken anymore.
     */
    public function getActiveSessions()
    {
        return $this->get_logged_in_users();
    }

    /**
     * Returns a list of user sessions that are not active in the sense, that a session cookie has been set but is not associated with a particular user.
     * Passive session cases:
     *  (i) entered ILIAS site, but not yet entered user credentials.
     *  (ii) anonymous user, e.g. in a public area of ILIAS
     */
    public function getPassiveSessions()
    {
        return $this->get_not_loggedin_users();
    }

    /**
     * Returns a list of user sessions that are expired.
     */
    public function getExpiredSessions()
    {
        return $this->get_obsolete_sessions();
    }

    /**
     * Returns counts of active, passive and expired sessions.
     */
    public function getSessionStatistics()
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
    private function get_logged_in_users()
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
    private function get_not_loggedin_users()
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
    private function get_obsolete_sessions()
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

}
