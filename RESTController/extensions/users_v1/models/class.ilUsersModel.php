<?php
require_once "./Services/User/classes/class.ilObjUser.php";
require_once "./Services/AccessControl/classes/class.ilRbacReview.php";

class ilUsersModel
{
    /**
     * This methods provides an array of all users and their properties specified by $fields.
     *
     * @param $fields
     * @return array
     */
    public function getAllUsers($fields)
    {
        $list = ilObjUser::_getAllUserData($fields,1); // note: the field usr_id is always included.
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
        $usrObj = ilObjectFactory::getInstanceByObjId($id);
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
        $usrObj = ilObjectFactory::getInstanceByObjId($id);
        $success = $usrObj->delete();
        return $success;
    }

    /**
     * Adds a user specified by $user_data. Automatically accepts the terms of use.
     *
     * @param $user_data
     * @return int
     */
    function addUser($user_data)
    {
        $new_user =& new ilObjUser();

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
        /*if ($user_data["import_id"] != "")
        {
            $new_user->setImportId($user_data["import_id"]);
        }
        */

        // If agreement is given. Set user agreement accepted.
        if($user_data['accepted_agreement'])
        {
            $new_user->writeAccepted();
        }

        // Assign role
        //$rbacadmin->assignUser($global_role_id,$new_user->getId());

        // Assign user prefs
        /* $new_user->setLanguage($user_data['user_language']);
         $new_user->setPref('style',$user_data['user_style']);
         $new_user->setPref('skin',$user_data['user_skin']);
         $new_user->setPref('hits_per_page',$ilSetting->get('hits_per_page'));
         $new_user->setPref('show_users_online',$ilSetting->get('show_users_online'));
         $new_user->writePrefs();
         */

        $new_user->create();
        $new_user->saveAsNew();
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
        $usrObj = ilObjectFactory::getInstanceByObjId($usr_id);
        switch ($fieldname) {
            case 'firstname':
            case 'lastname':
            case 'email':
                $usrObj->$fieldname = $newval;
                break;
            case 'active':
                if ($newval == "1"){
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

        require_once "./Services/User/classes/class.ilUserImportParser.php";
        require_once "./Services/Authentication/classes/class.ilAuthUtils.php";
    	$parser = new ilUserImportParser();
        // TODO/Problem: can't pass mode in constructor if no file is given
        // $parser->mode = IL_VERIFY;
    	$parser->setXMLContent($xmlData);
        // Permissions are only checked if IL_VERIFY is given, but from this context,
        // $ilAccess is null
        // resulting in
        // PHP Fatal error:  Call to a member function checkAccess() on a non-object in /opt/ilias/shared/ilias5_beta/Services/User/classes/class.ilUserImportParser.php on line 2033
        $parser->startParsing();

        if ($parser->isSuccess()) {
//	        $parser = new ilUserImportParser();
//	        $parser->setXMLContent($xmlData);
//            $parser->startParsing();

            $resp->setData("num_users", $parser->getUserCount());
            $resp->setMessage("Import successful");
            $resp->setCode(200);
        } else {
            $resp->setData("ILIAS_log", $parser->getProtocol());
            $resp->setMessage("Import failed, nothing done").
            $resp->setCode(400);
        }


    }


    /**
     * Checks if a user with a given login name owns the administration role.
     * @param $login
     * @return mixed
     */
    public function isAdminByUsername($login)
    {
        $a_id = ilObjUser::searchUsers($login, 1, true);

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
        $rbacreview = new ilRbacReview();
        $is_admin = $rbacreview->isAssigned($usr_id,2);
        return $is_admin;
    }
}
