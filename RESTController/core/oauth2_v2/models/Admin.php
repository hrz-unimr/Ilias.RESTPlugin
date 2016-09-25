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
 * Class: Admin
 *  Implements some utility functions and variables for the /Admin routes.
 *  Mostly handles only minor preprocessing before delegating the work
 *  to the ui_uihk_rest_client, ui_uihk_rest_perm, ui_uihk_rest_config
 *  database table implementation.
 */
class Admin extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_CLIENT_NOT_DELETED  = 'Client could not be deleted.';
  const MSG_CLIENT_NOT_UPDATED  = 'Client could not be updated.';
  const MSG_CLIENT_EXISTS       = 'Could not add client, given clientId already exists. Use put for updating instead.';
  const MSG_PERM_NOT_DELETED    = 'Permission could not be deleted.';
  const MSG_PERM_EXISTS         = 'Could not add permission, duplicate entry found.';


  /**
   * Function: InsertClient($request)
   *  Adds a new oauth2 client to the database. Handles fetching request parameters
   *  and doing some preprocessing and then delegates the rest to the database-table class.
   *
   * Parameters:
   *  request <RESTRequest> - Restrequest to parse the parameters from
   */
  public static function InsertClient($request) {
    // Fetch request-parameters (into table-row format)
    $row = array(
      'id'                          => $request->getParameter('id',                         null),
      'api_key'                     => $request->getParameter('api_key',                    null, true),
      'api_secret'                  => $request->getParameter('api_secret',                 null),
      'cert_serial'                 => $request->getParameter('cert_serial',                null),
      'cert_issuer'                 => $request->getParameter('cert_issuer',                null),
      'cert_subject'                => $request->getParameter('cert_subject',               null),
      'redirect_uri'                => $request->getParameter('redirect_uri',               null),
      'consent_message'             => $request->getParameter('consent_message',            null),
      'client_credentials_userid'   => $request->getParameter('client_credentials_userid',  6),
      'grant_client_credentials'    => $request->getParameter('grant_client_credentials',   false),
      'grant_authorization_code'    => $request->getParameter('grant_authorization_code',   false),
      'grant_implicit'              => $request->getParameter('grant_implicit',             false),
      'grant_resource_owner'        => $request->getParameter('grant_resource_owner',       false),
      'refresh_authorization_code'  => $request->getParameter('refresh_authorization_code', false),
      'refresh_resource_owner'      => $request->getParameter('refresh_resource_owner',     false),
      'grant_bridge'                => $request->getParameter('grant_bridge',               false),
      'ips'                         => $request->getParameter('ips',                        null),
      'users'                       => $request->getParameter('users',                      null),
      'scopes'                      => $request->getParameter('scopes',                     null),
      'description'                 => $request->getParameter('description',                null),
    );

    // Construct new table from given row/request-parameter
    $client = Database\RESTclient::fromRow($row);
    $id     = $row['id'];

    // Check if clientId was given and this client already exists?
    if ($id == null || !Database\RESTclient::existsByPrimary($id)) {
      // Insert (and possibly generate new clientId [its the primaryKey])
      $client->insert($id == null);
      return $client->getKey('id');
    }

    // Failed! (Client with given id existed)
    return false;
  }


  /**
   * Function: UpdateClient($clientId, $request)
   *  Updates an existing oauth2 client in the database. Handles fetching request parameters
   *  and doing some preprocessing and then delegates the rest to the database-table class.
   *
   * Parameters:
   *  clientId <Number> - ClientId of oauth2 client that should be updated
   *  request <RESTRequest> - Restrequest to parse the parameters from
   */
  public static function UpdateClient($clientId, $request) {
    // Fetch given client (by clientId) from database
    // Note: Throws IFF client does not exist!
    $client   = Database\RESTclient::fromPrimary($clientId);

    // Possible list of request parameters used for updating
    $update = false;
    $keys   = array(
      'api_key',
      'api_secret',
      'cert_serial',
      'cert_issuer',
      'cert_subject',
      'redirect_uri',
      'consent_message',
      'client_credentials_userid',
      'grant_client_credentials',
      'grant_authorization_code',
      'grant_implicit',
      'grant_resource_owner',
      'refresh_authorization_code',
      'refresh_resource_owner',
      'grant_bridge',
      'ips',
      'users',
      'scopes',
      'description'
    );

    // Update above client keys with request parameters (if parameters are given)
    foreach ($keys as $key)
      if ($request->hasParameter($key)) {
        // fetch parameter and update client
        $param  = $request->getParameter($key);
        $client->setKey($key, $param);

        // Remember that an update needs to be synced to DB
        $update = true;
      }

    // Finally push all updated keys to the database
    if ($update) {
      $client->update();
      return true;
    }

    // Failed! (No parameter to update)
    return false;
  }


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
      'id'      => $request->getParameter('id',       null),
      'pattern' => $request->getParameter('pattern',  null, true),
      'verb'    => $request->getParameter('verb',     null, true),
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
