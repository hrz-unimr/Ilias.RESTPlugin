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
 * Class: Client
 *  Implementes some utility functions and variables for the /client routes.
 *  Mostly handles only minor preprocessing before delegating the work
 *  to the ui_uihk_rest_client database table implementation.
 */
class Client extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_NOT_DELETED = 'Client could not be deleted.';
  const MSG_NOT_UPDATED = 'Client could not be updated.';
  const MSG_EXISTS      = 'Could not add client, given clientId already exists. Use put for updating instead.';


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
      'id'                          => $request->params('id',                         null),
      'api_key'                     => $request->params('api_key',                    null, true),
      'api_secret'                  => $request->params('api_secret',                 null),
      'cert_serial'                 => $request->params('cert_serial',                null),
      'cert_issuer'                 => $request->params('cert_issuer',                null),
      'cert_subject'                => $request->params('cert_subject',               null),
      'redirect_uri'                => $request->params('redirect_uri',               null),
      'consent_message'             => $request->params('consent_message',            null),
      'client_credentials_userid'   => $request->params('client_credentials_userid',  6),
      'grant_client_credentials'    => $request->params('grant_client_credentials',   false),
      'grant_authorization_code'    => $request->params('grant_authorization_code',   false),
      'grant_implicit'              => $request->params('grant_implicit',             false),
      'grant_resource_owner'        => $request->params('grant_resource_owner',       false),
      'refresh_authorization_code'  => $request->params('refresh_authorization_code', false),
      'refresh_resource_owner'      => $request->params('refresh_resource_owner',     false),
      'grant_bridge'                => $request->params('grant_bridge',               false),
      'ips'                         => $request->params('ips',                        null),
      'users'                       => $request->params('users',                      null),
      'scopes'                      => $request->params('scopes',                     null),
      'description'                 => $request->params('description',                null),
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
      if ($request->hasParam($key)) {
        // fetch parameter and update client
        $param  = $request->params($key);
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
}
