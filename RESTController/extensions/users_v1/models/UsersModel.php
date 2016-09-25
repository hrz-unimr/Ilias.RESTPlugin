<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\users_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('./Services/User/classes/class.ilObjUser.php');
require_once('./Services/AccessControl/classes/class.ilRbacReview.php');


class UsersModel extends Libs\RESTModel
{
    /**
     * This methods provides an array of all users and their properties specified by $fields.
     *
     * @param $fields
     * @return array
     */
    public function getAllUsers($fields)
    {
        $list = \ilObjUser::_getAllUserData($fields,1); // note: the field usr_id is always included.
        return $list;
    }

    /**
     * Gets basic information about a user, such as firstname, lastname login and email.
     *
     * @param $id
     * @return array
     */
    public function getBasicUserData($id)
    {
        include_once('./Services/Calendar/classes/class.ilDate.php');
        $usrObj = \ilObjectFactory::getInstanceByObjId($id);
        $usr_data = array();
        if (is_null($usrObj) == false) {
            $usr_data['firstname'] = $usrObj->firstname;
            $usr_data['lastname'] = $usrObj->lastname;
            $usr_data['login'] = $usrObj->login;
            $usr_data['email'] = $usrObj->email;
            $usr_data['usr_id'] = $id;
        }
        return $usr_data;
    }

    /**
     * Deletes a users.
     * @param $id
     * @return mixed
     */
    public function deleteUser($id)
    {
        Libs\RESTilias::initAccessHandling();
        $usrObj = \ilObjectFactory::getInstanceByObjId($id, false);
        if ($usrObj == false) {
            return false;
        }
        $success = $usrObj->delete();
        return $success;
    }

    /**
     * Adds a user specified by $user_data. Automatically accepts the terms of use.
     * TODO: Throw Exception when adding user failed!
     *
     * @param $user_data
     * @return int
     */
    function addUser($user_data)
    {
        $new_user =& new \ilObjUser();

        if(strlen($user_data['passwd']) != 32)
        {
            $user_data['passwd_type'] = IL_PASSWD_PLAIN;
        }
        else
        {
            $user_data['passwd_type'] = IL_PASSWD_MD5;
        }

        $user_data['time_limit_unlimited'] = 1;
        $new_user->assignData($user_data);
        // Need this for entry in object_data
        $new_user->setTitle($new_user->getFullname());
        $new_user->setDescription($new_user->getEmail());
        // optional: import_id
        /*if ($user_data['import_id'] != '')
        {
            $new_user->setImportId($user_data['import_id']);
        }
        */

        // If agreement is given. Set user agreement accepted.
        if($user_data['accepted_agreement'])
        {
            $new_user->writeAccepted();
        }


        // Assign user prefs
        /* $new_user->setLanguage($user_data['user_language']);
         $new_user->setPref('style',$user_data['user_style']);
         $new_user->setPref('skin',$user_data['user_skin']);
         $new_user->setPref('hits_per_page',$ilSetting->get('hits_per_page'));
         $new_user->setPref('show_users_online',$ilSetting->get('show_users_online'));
         $new_user->writePrefs();
         */

        $new_user->setLastPasswordChangeToNow();
        $new_user->create();
        $new_user->setActive(true);
        $new_user->saveAsNew();

        // Assign 'User' role per default
        Libs\RESTilias::initAccessHandling();
        global $rbacadmin, $rbacreview;
        $user_role_array = $rbacreview->getRolesByFilter($rbacreview::FILTER_ALL, 0, 'User');
        $user_role_id = $user_role_array[0]['obj_id'];
        // Alternatives:
        // SELECT obj_id FROM object_data JOIN rbac_fa ON obj_id = rol_id WHERE title = 'User' AND assign = 'y'
        // Or just use '4'
        $rbacadmin->assignUser(4,$new_user->getId());
        return $new_user->getId();
    }

    /**
     * Updates the fields of a user.
     * @param $usr_id
     * @param $fieldname
     * @param $newval
     */
    public function updateUser($usr_id, $fieldname, $newval)
    {
        $usrObj = \ilObjectFactory::getInstanceByObjId($usr_id);

        switch ($fieldname) {
            case 'firstname':
            case 'lastname':
            case 'email':
                $usrObj->$fieldname = $newval;
                break;
            case 'active':
                if ($newval == '1'){
                    $usrObj->setActive(true);
                }else{
                    $usrObj->setActive(false);
                }
                break;
            default:break;
        }

  //      $usrObj->setEmail($newval);
        $usrObj->update();
    }

    /**
     * Bulk import of users, using XML representation.
     * Works similar to the web interface: first, the import data is validated
     * (e.g. duplicate users). If successful, the data is imported.
     * Otherwise, ILIAS' error log is returned.
     */
    public function bulkImport($xmlData, &$resp)
    {

        require_once('./Services/User/classes/class.ilUserImportParser.php');
        require_once('./Services/Authentication/classes/class.ilAuthUtils.php');

       // Fetch authorized user
       $userId  = Auth\Util::getAccessToken()->getUserId();

        // TODO: do it here or in route?
        Libs\RESTilias::loadIlUser($userId);
        Libs\RESTilias::initAccessHandling();


        $parser = new \ilUserImportParser();
        // TODO/Problem: can't pass mode in constructor if no file is given
        $parser->mode = IL_VERIFY;
        $parser->setXMLContent($xmlData);
        $parser->startParsing();

        if ($parser->isSuccess()) {
            $parser = new \ilUserImportParser();
            $parser->setXMLContent($xmlData);
            $parser->startParsing();
        }
        else
            throw new \Exception('Could not parse import-data.');
    }


    /**
     * Checks if a user with a given login name owns the administration role.
     * @param $login
     * @return mixed
     */
    public function isAdminByUsername($login)
    {
        $a_id = \ilObjUser::searchUsers($login, 1, true);

        if (count($a_id) > 0) {
            return $this->isAdmin($a_id[0]);
        } else {
            return false;
        }

    }

    /**
     * Checks if a user with a usr_id owns the administration role.
     * @param $usr_id
     * @return bool
     */
    public function isAdmin($usr_id)
    {
        $rbacreview = new \ilRbacReview();
        $is_admin = $rbacreview->isAssigned($usr_id,2);
        return $is_admin;
    }

    /**
     * Searches for users with llap auth mode for which the query ($ext_name) matches with ext_account.
     *
     * @param $ext_name
     * @return bool|object
     */
    public function findExtLdapUser($ext_name)
    {
        global $ilDB;
        $sql = Libs\RESTDatabase::safeSQL('SELECT * FROM usr_data WHERE ext_account = %s AND auth_mode = \'ldap\'', $ext_name);
        $query = $ilDB->query($sql);

        if ($usr = $ilDB->fetchAssoc($query))
        {
            return $usr;
        }
        return false;
    }

    /**
     * Searches for users with llap auth mode for which the query ($ext_name) matches with ext_account.
     *
     * @param $ext_name
     * @return bool|object
     */
    public function findExtLdapUsers()
    {
        global $ilDB;
        $sql = Libs\RESTDatabase::safeSQL('SELECT * FROM usr_data WHERE auth_mode = \'ldap\'');
        $query = $ilDB->query($sql);

        if ($usr = $ilDB->fetchAssoc($query))
        {
            return $usr;
        }
        return false;
    }
}
