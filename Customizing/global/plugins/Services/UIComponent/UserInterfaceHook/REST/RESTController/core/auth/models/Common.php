<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Class: Common
 *  Common functionality user by both the Authorize and Token Endpoints.
 *  This mostly includes functionality already implemented by other classes
 *  but with additional excaptions attached to them.
 */
class Common extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_RESTRICTED_IP               = 'This client (api-key) is not allowed to be used from {{ip}} IP-Address.';
  const ID_RESTRICTED_IP                = 'RESTController\\core\\auth\\Authorize::ID_RESTRICTED_IP';
  const MSG_RESTRICTED_USER             = 'Resource-Owner \'{{username}}\' is not allowed to use this client (api-key).';
  const ID_RESTRICTED_USER              = 'RESTController\\core\\auth\\Authorize::ID_RESTRICTED_USER';
  const MSG_WRONG_OWNER_CREDENTIALS     = 'Resource-Owner credentials (Username & Password) could not be authenticated.';
  const ID_WRONG_OWNER_CREDENTIALS      = 'RESTController\\core\\auth\\Authorize::ID_WRONG_OWNER_CREDENTIALS';
  const MSG_AUTHORIZATION_CODE_DISABLED = 'Authorization-Code grant is disabled for this client (api-key).';
  const ID_AUTHORIZATION_CODE_DISABLED  = 'RESTController\\core\\auth\\Authorize::ID_AUTHORIZATION_CODE_DISABLED';
  const MSG_IMPLICIT_DISABLED           = 'Implicit grant is disabled for this client (api-key).';
  const ID_IMPLICIT_DISABLED            = 'RESTController\\core\\auth\\Authorize::ID_IMPLICIT_DISABLED';
  const MSG_RESOURCE_OWNER_DISABLED     = 'Resource-Owner grant is disabled for this client (api-key).';
  const ID_RESOURCE_OWNER_DISABLED      = 'RESTController\\core\\auth\\Authorize::ID_RESOURCE_OWNER_DISABLED';
  const MSG_CLIENT_CREDENTIALS_DISABLED = 'Client-Credentials grant is disabled for this client (api-key).';
  const ID_CLIENT_CREDENTIALS_DISABLED  = 'RESTController\\core\\auth\\Authorize::ID_CLIENT_CREDENTIALS_DISABLED';


  /**
   * Function: getClientCertificate()
   *  Utility method to nicely fetch client-certificate (ssl) data from
   *  gfobal namespace and preformat it...
   *
   * Return:
   *  <Array[String]> - See below...
   */
  public static function FetchClientCertificate() {
    // Build a more readable ssl client-certificate array...
    return array(
      verify  => $_SERVER['SSL_CLIENT_VERIFY'],
      serial  => $_SERVER['SSL_CLIENT_M_SERIAL'],
      issuer  => $_SERVER['SSL_CLIENT_I_DN'],
      subject => $_SERVER['SSL_CLIENT_S_DN'],
      expires => $_SERVER['SSL_CLIENT_V_END'],
      ttl     => $_SERVER['SSL_CLIENT_V_REMAIN']
    );
  }


  /**
   * Function: FetchUserAgentIP()
   *  Return IP-Address of resource-owner user-agent.
   *  For Reverse-Proxied servers the workers require a module such as mod_rpaf
   *  that makes sure $_SERVER['REMOTE_ADDR'] does not contain the reverse-proxy
   *  but the user-agents ip.
   *
   * Return:
   *  <String> - IP-Address of resource-owner user-agent
   */
  public static function FetchUserAgentIP() {
    return $_SERVER['REMOTE_ADDR'];
  }


  /**
   * Function: FetchILIASClient()
   *  Returns the current ILIAS Client-ID. This cannot be changed
   *  and can only be controlled by setting $_GET['ilias_client_id']
   *  (see restplugin.php) or via $_COOKIE['client_id'] (See ilInitialize)
   *
   * Return:
   *  <String> - ILIAS Client-ID (fixed)
   */
  public static function FetchILIASClient() {
    return CLIENT_ID;
  }


  /**
   * Function: CheckApiKey($apiKey)
   *  Checks wether a client with given api-key exists and returns it.
   *  Throws an exception when no client exists!
   *
   * Parameters:
   *  $apiKey <String> - API-Key to check/return
   *
   * Return:
   *  <RESTclient> - Fetches client from database
   */
  public static function CheckApiKey($apiKey) {
    // Fecth client with given api-key (throws if non existent)
    return Database\RESTclient::fromApiKey($apiKey);
  }


  /**
   * Function: CheckIP($client, $remoteIp)
   *  Check wether the resource-owners user-agent is allowed to use this client
   *  with his current ip adress. Throws exception if ip is not allowed.
   *
   * Parameters:
   *  $client <RESTclient> - RESTclient object to use for ip-checking
   *  $remoteIp <String> - IP that should be checked against given client
   */
  public static function CheckIP($client, $remoteIp) {
    // Check ip-restriction
    // Note: If a (reverse-) proxy server is used, all workers need to set REMOTE_ADDR
    //       for example an apache worker (behind an nginx loadbalancer) by using mod_rpaf.
    if (!$client->isIpAllowed($remoteIp))
      throw new Exceptions\Denied(
        self::MSG_RESTRICTED_IP,
        self::ID_RESTRICTED_IP,
        array(
          'ip' => $remoteIp
        )
      );
  }


  /**
   * Function: CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri)
   *  Check the given client-credentials (api-secret, client-certificate, redirect-uri) against
   *  those stored for the client. Throws an exception if client could not be authorized.
   *
   * Parameters:
   *  $client <RESTclient> - RESTclient object to use for client-credentials check
   *  $apiSecret <String> - Client secret given as request-parameter (needs to match clients stored secret if enabled)
   *  $apiCert <Array[Mixed]> - Client certificate given as request-parameter (needs to match clients stored certificate fields if enabled)
   *  $redirectUri <String> - Client redirect-uri given as request-parameter (needs to match clients stored redirect-uri if enabled)
   */
  public static function CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri) {
    // Check wether the client needs to be and can be authorized
    if (!$client->checkCredentials($apiSecret, $apiCert, $redirectUri))
      throw new Exceptions\Denied(
        self::MSG_UNAUTHORIZED_CLIENT,
        self::ID_UNAUTHORIZED_CLIENT
      );
  }


  /**
   * Function: CheckUsername($userName)
   *  Checks wether a user with a given name exists inside the current ilias-client,
   *  check is case-sensitive. Throws an exception if no match is found.
   *
   * Parameters:
   *  $userName <String> - Username that should be checked
   *
   * Return:
   *  <Integer> - Returns user-id if user with given name was found
   */
  public static function CheckUsername($userName) {
    // This throws for wrong username (case-sensitive!)
    return Libs\RESTilias::getUserId($userName);
  }


  /**
   * Function: CheckUserRestriction($apiKey, $userId)
   *  Checks wether the given resource-owner is allowed to use the client
   *  with given api-key. Throws an exception when use is not allowed to use client.
   *
   * Parameters:
   *  $apiKey <String> - API-Key of client to check user-restriction for
   *  $userId <Integer> - User-id that should be checked against given client
   */
  public static function CheckUserRestriction($apiKey, $userId) {
    // Check user restriction
    if (!Database\RESTuser::isUserAllowedByKey($apiKey, $userId))
      throw new Exceptions\Denied(
        self::MSG_RESTRICTED_USER,
        self::ID_RESTRICTED_USER,
        array(
          'userID'    => $userId
        )
      );
  }


  /**
   * Function: CheckResourceOwner($userName, $passWord)
   *  Checks wether given resource-owner credentials (ILIAS username and password)
   *  are valid and throws exception if credentials are not valid.
   *
   * Parameters:
   *  $userName <String> - Username of resource-owner to authenticate
   *  $passWord <String> - Password for username of resource-owner required for authentification
   */
  public static function CheckResourceOwner($userName, $passWord) {
    // Check wether the resource owner credentials are valid
    if (!Libs\RESTilias::authenticate($userName, $passWord))
      throw new Exception\Credentials(
        self::MSG_WRONG_OWNER_CREDENTIALS,
        self::ID_WRONG_OWNER_CREDENTIALS
      );
  }
}
