<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\users_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs            as Libs;
use \RESTController\libs\Exceptions as LibExceptions;

// Include required ILIAS classes
require_once('Services/User/classes/class.ilObjUser.php');
require_once('Services/Authentication/classes/class.ilAuthUtils.php');


/**
 * Class: User
 *  Contains non administative user management routes
 */
class User extends Libs\RESTModel {
  /**
   * Function: SearchUser(search, login, external, authmode, role, parent)
   *  Search and return user-ids that match search criteria.
   *
   * Parameters:
   *  search <String> [Optional] This string is partially searched in login, firstname, lastname, and email
   *  login <String> [Optional] This searches user-id by exact login match
   *  external <String> [Optional] This searches user-id  by exact external-account name (requires authmode)
   *  authmode <String> [Optional] This searches user-id if external-account with given auth-mode
   *  role <Numeric> [Optional] This searches user-id by their role (role-id)
   *  parent <Numeric> [Optional] Searches (local) user-ids by their parent category or org-unit
   *
   * Returns:
   *  <Array[Int]> User-ids that match search-criteria
   */
  public static function SearchUser($search = null, $login = null, $external = null, $authmode = null, $role = null, $parent = null) {
    $ids = array();

    // Search firstname, lastname, login or email based
    if ($search && strlen($search) > 0)
      $ids = array_merge($ids, \ilObjUser::searchUsers(addslashes($search), 1, true));

    // Search exact login
    if ($login && strlen($login) > 0)
      $ids[] = \ilObjUser::_loginExists($login);

    // Search an external-account
    if ($external && $authmode && strlen($external) > 0 && strlen($authmode) > 0) {
      $login = \ilObjUser::_checkExternalAuthAccount($authmode, $external);
      if ($login)
        $ids[] = \ilObjUser::_lookupId($login);
    }

    // Search by role
    if ($role && strlen($role) > 0) {
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $users = \ilObjUser::_getUsersForRole($role);
      $ids   = array_merge($ids, array_map(function($user) { return $user['usr_id']; }, $users));
    }

    // Search by local category or org-unit
    if ($parent) {
      $users = \ilObjUser::_getUsersForFolder($parent, -1);
      $ids   = array_merge($ids, array_map(function($user) { return $user['usr_id']; }, $users));
    }

    // Convert all user-ids to numeric values...
    return array_map('intval', $ids);
  }
}
