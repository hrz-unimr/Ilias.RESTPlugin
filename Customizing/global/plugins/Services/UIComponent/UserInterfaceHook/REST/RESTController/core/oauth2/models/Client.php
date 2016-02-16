<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


/**
 * Class: Client
 *
 */
class Client extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_NOT_DELETED = 'Client could not be deleted.';
  const MSG_NOT_UPDATED = 'Client could not be updated.';


  /**
   * Function: UpdateClient($clientId, $request)
   *
   *
   * Parameters:
   *
   */
  public static function UpdateClient($clientId, $request) {
    $client   = Database\RESTclient::fromPrimary($clientId);
    $update   = false;
    $keys     = array(
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
    foreach ($keys as $key)
      if ($request->hasParam($key)) {
        $update = true;
        $param  = $request->params($key);
        $client->setKey($key, $param);
      }

    if ($update) {
      $client->update();
      return true;
    }

    return false;
  }
}
