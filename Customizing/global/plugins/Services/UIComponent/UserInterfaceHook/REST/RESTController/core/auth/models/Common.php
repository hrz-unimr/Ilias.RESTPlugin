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
 *
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
   *
   *
   * Return:
   *  <String> -
   */
  public static function FetchUserAgentIP() {
    return $_SERVER['REMOTE_ADDR'];
  }


  /**
   * Function: FetchILIASClient()
   *
   *
   * Return:
   *  <String> -
   */
  public static function FetchILIASClient() {
    return CLIENT_ID;
  }


  /**
   * Function: CheckApiKey($apiKey)
   *
   *
   * Parameters:
   *  $apiKey <String> -
   *
   * Return:
   *  <RESTclient> -
   */
  public static function CheckApiKey($apiKey) {
    // Fecth client with given api-key (throws if non existent)
    return Database\RESTclient::fromApiKey($apiKey);
  }


  /**
   * Function: CheckIP($client, $remoteIp)
   *
   *
   * Parameters:
   *  $client <RESTclient> -
   *  $remoteIp <String> -
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
   *
   *
   * Parameters:
   *  $client <RESTclient> -
   *  $apiSecret <String> -
   *  $apiCert <Array[Mixed]> -
   *  $redirectUri <String> -
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
   *
   *
   * Parameters:
   *  $userName <String> -
   *
   * Return:
   *  <Integer> -
   */
  public static function CheckUsername($userName) {
    // This throws for wrong username (case-sensitive!)
    return Libs\RESTilias::getUserId($userName);
  }


  /**
   * Function: CheckUserRestriction($apiKey, $userId)
   *
   *
   * Parameters:
   *  $apiKey <String> -
   *  $userId <Integer> -
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
   *
   *
   * Parameters:
   *  $userName <String> -
   *  $passWord <String> -
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
