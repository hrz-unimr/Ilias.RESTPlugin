<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


/**
 * Class: Permission
 *  Implementes some utility functions and variables for the /permission routes.
 *  Mostly handles only minor preprocessing before delegating the work
 *  to the ui_uihk_rest_perm database table implementation.
 */
class Permission extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_NOT_DELETED = 'Permission could not be deleted.';
  const MSG_EXISTS      = 'Could not add permission, duplicate entry found.';


  /**
   * Function: InsertPermission($request)
   *  Adds a new permission for a given oauth2 client to the database. Handles fetching request parameters
   *  and doing some preprocessing and then delegates the rest to the database-table class.
   *
   * Parameters:
   *  request <RESTRequest> - Restrequest to parse the parameters from
   */
  public static function InsertPermission($clientId, $request) {
    // Fetch request-parameters (into table-row format)
    $row        = array(
      'api_id'  => intval($clientId),
      'id'      => $request->params('id',       null),
      'pattern' => $request->params('pattern',  null, true),
      'verb'    => $request->params('verb',     null, true),
    );

    // Construct new table from given row/request-parameter
    $permission = Database\RESTpermission::fromRow($row);
    $id         = $row['id'];

    // Check for duplicate entry
    if ($permission->exists('api_id = {{api_id}} AND pattern = {{patern}} AND verb = {{verb}}'))
      return false;

    // Check if permissionId was given and this permission already exists?
    if ($id == null || !Database\RESTpermission::existsByPrimary($id)) {
      // Insert (and possibly generate new permissionId [its the primaryKey])
      $permission->insert($id == null);
      return $permission->getKey('id');
    }

    // Failed! (Permission with given id existed)
    return false;
  }
}
