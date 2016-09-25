<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\database;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Class: RESTclient (Database-Table)
 *  Abstraction for 'ui_uihk_rest_client' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTclient extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_client';
  protected static $tableKeys   = array(
    'id'                          => 'integer',
    'api_key'                     => 'text',
    'api_secret'                  => 'text',
    'cert_serial'                 => 'text',
    'cert_issuer'                 => 'text',
    'cert_subject'                => 'text',
    'redirect_uri'                => 'text',
    'ips'                         => 'text',
    'users'                       => 'text',
    'scopes'                      => 'text',
    'consent_message'             => 'text',
    'client_credentials_userid'   => 'integer',
    'grant_client_credentials'    => 'integer',
    'grant_authorization_code'    => 'integer',
    'grant_implicit'              => 'integer',
    'grant_resource_owner'        => 'integer',
    'refresh_authorization_code'  => 'integer',
    'refresh_resource_owner'      => 'integer',
    'grant_bridge'                => 'text',
    'description'                 => 'text'
  );


  /**
   * Function: fromApiKey($apiKey)
   *  Creates a new instance of RESTclient representing the table-entry with given aki-key.
   *
   * Parameters:
   *  $apiKey <String> - Api-Key who's database entry should be returned
   *
   * Return:
   *  <RESTclient> - A new instance of RESTclient representing the table-entry with given aki-key
   */
  public static function fromApiKey($apiKey) {
    // Generate a (save) where clause for the api-key ($apiKey can be malformed!)
    $where  = sprintf('api_key = %s', self::quote($apiKey, 'text'));

    // Fetch matching object
    return self::fromWhere($where);
  }


  /**
   * Function: getKey($key)
   *  @See RESTDatabase->getKey(...)
   */
  public function getKey($key) {
    // Fetch internal value from parent
    $value = parent::getKey($key);

    // Convert internal value when publshing
    // Note: Make sure to 'revert' those changes in setKey(...)!
    switch ($key) {
      // Convert string/boolean values
      case 'consent_message':
      case 'redirect_uri':
      case 'api_secret':
      case 'cert_serial':
      case 'cert_issuer':
      case 'cert_subject':
      case 'redirect_uri':
      case 'ips':
      case 'users':
      case 'scopes':
      case 'consent_message':
      case 'grant_bridge':
        return ($value == null) ? false : $value;

      default:
        return $value;
    }
  }


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // Parse input based on key
    switch ($key) {
      // Convert string/boolean values
      case 'consent_message':
      case 'redirect_uri':
      case 'api_secret':
      case 'cert_serial':
      case 'cert_issuer':
      case 'cert_subject':
      case 'redirect_uri':
      case 'ips':
      case 'users':
      case 'scopes':
      case 'consent_message':
      case 'grant_bridge':
        $value = ($value === false) ? null : $value;
        break;

      // Convert int values
      case 'client_credentials_userid':
        $value = intval($value);
        break;

      // Convert (empty) string value
      case 'api_key':
      case 'description':
        $value = ($value == null) ? '' : $value;
        break;

      // Convert boolean values
      case 'grant_client_credentials':
      case 'grant_authorization_code':
      case 'grant_implicit':
      case 'grant_resource_owner':
      case 'refresh_authorization_code':
      case 'refresh_resource_owner':
        $value = ($value == '1' || $value == 'true' || $value == true);
        break;

      // No default behaviour
      default:
    }

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }


  /**
   * Function: checkCredentials($givenSecret, $givenCert, $givenRedirect)
   *  Checks wether the client has given correct credentials.
   *  IFF the clients api_secret or any cert_* is not null (in the database), it has to match
   *  the given parameters, otherwise client-authorization fails.
   *  (Empty/NULL fields will be ignored)
   *
   * Parameters:
   *  $givenSecret <String> - Secret that was given (eg. was parameter) for client-authorization
   *  $givenCert <Array[String]> - Client-Certificate data that was given for client-authorization
   *  $givenRedirect <String> - [Optional] Compare given redirect_uri with clients stored value for client-authorization
   *                            (Use false to ignore)
   *
   * Return:
   *  <Boolean> - True if the client was authorized successfully
   */
  public function checkCredentials($givenSecret = null, $givenCert = null, $givenRedirect = false) {
    // Delegate actual checks...
    if ($givenSecret   !== false && !$this->checkClientSecret($givenSecret))
      return false;
    if ($givenCert     !== false && !$this->checkClientCertificate($givenCert))
      return false;
    if ($givenRedirect !== false && !$this->checkClientRedirect($givenRedirect))
      return false;
    return true;
  }


  /**
   * Function: checkClientSecret($givenSecret)
   *  Checks if the given client-secret matches with the stored database value.
   *  If no db-value is set, the client is treatet as 'public' (in regards to
   *  secret-based authorization), otherwise he is a confidential-client.
   *
   * Parameters:
   *  $givenSecret <String> - Client secret that should be checked (eg. fetched as parameter)
   *
   * Return:
   *  <Boolean> - True of client secret was checked to be correct
   */
  protected function checkClientSecret($givenSecret = null) {
    // Compare client-secret, if one was set for this client
    $secret = $this->getKey('api_secret');
    return Libs\RESTLib::CheckSimpleRestriction($secret, $givenSecret);
  }


  /**
   * Function: checkClientRedirect($givenRedirect)
   *  Checks if the given client redirect_uri matches with the stored database value.
   *  (This is only required for confidential clients if no secret can be given)
   *  If no db-value is set, the client is treatet as 'public' (in regards to
   *  redirect-based authorization), otherwise he is a confidential-client.
   *
   * Parameters:
   *  $givenRedirect <String> - Client redirect_uri that should be checked (eg. fetched as parameter)
   *                            Leave blank or pass false to disable this check.
   *
   * Return:
   *  <Boolean> - True of clients redirect_uri was checked to be correct
   */
  protected function checkClientRedirect($givenRedirect = false) {
    // Compare clients redirect_uri, if one was set for this client
    $redirect = $this->getKey('redirect_uri');
    return Libs\RESTLib::CheckSimpleRestriction($redirect, $givenRedirect);
  }


  /**
   * Function: checkClientCertificate($givenCert)
   *  Checks if the given clients certificate matches with the stored database values.
   *  If no db-value is set, the client is treatet as 'public' (in regards to
   *  certificate-based authorization), otherwise he is a confidential-client.
   *
   *  Note that all db-entries are treatet as regular expressions in order to support
   *  multiple client-certificates without making additional db lookups.
   *
   * Parameters:
   *  $givenCert <String> - Client certificate (pre-parsed array) that should be checked (see RESTclients::getClientCertificate())
   *
   * Return:
   *  <Boolean> - True of client's certificate was checked to be correct (matching in all active fields)
   */
  protected function checkClientCertificate($givenCert = null) {
    // Fetch all certificate checks
    $cert_serial  = $this->getKey('cert_serial');
    $cert_issuer  = $this->getKey('cert_issuer');
    $cert_subject = $this->getKey('cert_subject');

    // No certificate check was enabled...
    if ($cert_serial == null && $cert_issuer == null && $cert_subject == null)
      return true;

    // Not a valid client-certificate
    if (!isset($givenCert) || $givenCert['verify'] != 'SUCCESS' || !isset($givenCert['expires']) || $givenCert['ttl'] <= 0)
      return false;

    // Check serial, issuer and subject fields
    if (!Libs\RESTLib::CheckComplexRestriction($cert_serial,  $givenCert['serial']))  return false;
    if (!Libs\RESTLib::CheckComplexRestriction($cert_issuer,  $givenCert['issuer']))  return false;
    if (!Libs\RESTLib::CheckComplexRestriction($cert_subject, $givenCert['subject'])) return false;

    // Not returned by now -> all fields match or are disabled...
    return true;
  }


  /**
   * Function: isIpAllowed($ip)
   *  If no value is given for the 'ips' key, there is no ip
   *  restriction, otherwise the given ip has to match the
   *  (regex-match) with the database entry for the 'ips' key
   *
   * Parameters:
   *  $ip <String> - IP that should be checked for restrictions
   *
   * Return:
   *  <Boolean> - True if the given ip is allowed to use this key
   */
  public function isIpAllowed($ip) {
    // For security
    if (filter_var($ip, FILTER_VALIDATE_IP) === false)
      return false;

    // Fetch allowed-ips regex
    $allowed = $this->getKey('ips');

    // Delegate to restriction-check
    return Libs\RESTLib::CheckComplexRestriction($allowed, $ip);
  }


  /**
   * Function: isUserAllowed($userId)
   *  First checks if there exists any entries for this client.
   *  If not, no restriction is active, otherwise only users with
   *  their user-id (and given api-key / api-key id) who have an entry are allowed.
   *
   * Parameters:
   *  $userId <Integer> - User-Id to check if the user is allowed to use this api-key
   *
   * Return:
   *  <Boolean> - True if the user is allowed to use the given api-key / api-key id, false otherwise
   */
  public function isUserAllowed($userId) {
    // For security...
    if (!ctype_digit($userId)) {
      return false;
    }


    // Fetch list (regex) of allowed users
    $users = $this->getKey('users');

    // Delegate to restriction-check
    return Libs\RESTLib::CheckComplexRestriction($users, $userId);
  }


  /**
   * Function: isScopeAllowed($scope)
   *  Checks wether the requested scope is allowed to be used by the
   *  this client. Only returns true if all requested scopes are allowed for this client.
   *
   * Parameters:
   *  $scope <String>/<Array[String]> - Requested scope (or list of requested scopes)
   *
   * Return:
   *  <Boolean> - True if all given scopes are allowed for this client, false otherwise
   */
  public function isScopeAllowed($scope) {
    // Fetch list (regex) of allowed scopes
    $allowed = $this->getKey('scopes');

    // Extract requested scopes
    $scopes = explode(' ', $scope);

    // Delegate to restriction-check
    return Libs\RESTLib::CheckComplexRestriction($allowed, $scopes, ' ');
  }

  
  /**
   * Function: isBridgeAllowed($direction)
   *  Checks if the ILIAS <-> oAuth2 bridge is allowed for this client in the requested direction.
   *
   * Parameters:
   *  $direction <String> - Requested direction of ILIAS <-> oAuth2 bridge. Use:
   *                         'fromILIAS' to checks wether a valid ILIAS session may be exchanged for a new access-token
   *                         'fromOAuth2' to checks wether an access-token may be exchanged for a new a ILIAS session
   *                         'both' to checks wether both of the above are allowed
   *
   * Return:
   *  <Boolean> - True if the ILIAS <-> oAuth2 bridge is allowed for this client in the requested direction
   */
  public function isBridgeAllowed($direction) {
    // Convert all to lower-case
    $direction  = strtolower($direction);
    $allowed    = strtolower($this->getKey('grant_bridge'));
    // Bridge is disabled, no need for further comparisons...
    if ($allowed === false)
      return false;
    // Check depending on requested bridge direction
    switch ($direction) {
      // Requesting bridge from ILIAS
      case 'ilias':
      case 'fromilias':
        // Direction from (I)LIAS or (B)OTH enabled
        return $allowed == 'i' || $allowed = 'b';
      // Requesting bridge from oAuth2
      case 'oauth2':
      case 'oauth':
      case 'fromoauth2':
      case 'fromoauth':
        // Direction from (o)Auth2 or (B)OTH enabled
        return $allowed == 'o' || $allowed = 'b';
      // Requesting bridge in both directions
      case 'both':
        return $allowed = 'b';
    }
    // Fallback: Everything else disabled by default
    return false;
  }
}
