<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\roles_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


class RolesModel {
    public function getAllRoles($user, $data){
        global $rbacsystem, $rbacreview;
        // get all roles of system role folder
        // TODO: c/p aus users/bulkImport
        // TODO: do it here or in route?

        $userId  = Auth\Util::getAccessToken()->getUserId();

        Libs\RESTilias::loadIlUser($userId);
        Libs\RESTilias::initAccessHandling();


        if(!$rbacsystem->checkAccess('read',ROLE_FOLDER_ID))
            throw new \Exception("No access to list roles");

        $roles = $rbacreview->getAssignableRoles(false, true);

        $result = array();
        if(count($data) != 0){
            foreach($roles as $role) {
                $match = true;

                foreach($data as $key => $value)
                    if (!isset($role[$key]) || $role[$key] != $value) {
                        $match = false;
                        break;
                    }

                if($match == true)
                    $result[] = $role;
            }
        }
        else
            $result = $roles;

        return $result;
    }
}
