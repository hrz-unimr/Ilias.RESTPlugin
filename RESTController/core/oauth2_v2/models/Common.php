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
 * Class: Common
 *  Common functionality user by both the Authorize and Token Endpoints.
 *  This mostly includes functionality already implemented by other classes
 *  but with additional excaptions attached to them.
 */
class Common extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_RESTRICTED_IP               = 'This client (api-key) is not allowed to be used from IP: {{ip}}';
  const ID_RESTRICTED_IP                = 'RESTController\\core\\auth\\Common::ID_RESTRICTED_IP';
  const MSG_RESTRICTED_USER             = 'Resource-Owner \'{{username}}\' is not allowed to use this client (api-key).';
  const ID_RESTRICTED_USER              = 'RESTController\\core\\auth\\Common::ID_RESTRICTED_USER';
  const MSG_WRONG_OWNER_CREDENTIALS     = 'Resource-Owner ({{username}}) could not be authenticated given his username and password.';
  const ID_WRONG_OWNER_CREDENTIALS      = 'RESTController\\core\\auth\\Common::ID_WRONG_OWNER_CREDENTIALS';
  const MSG_AUTHORIZATION_CODE_DISABLED = 'Authorization-Code grant is disabled for this client (api-key).';
  const ID_AUTHORIZATION_CODE_DISABLED  = 'RESTController\\core\\auth\\Common::ID_AUTHORIZATION_CODE_DISABLED';
  const MSG_UNAUTHORIZED_CLIENT         = 'Client is required to authorize using his client-secret or his client-certificate.';
  const ID_UNAUTHORIZED_CLIENT          = 'RESTController\\core\\auth\\Common::ID_UNAUTHORIZED_CLIENT';
  const MSG_BAD_SCOPE                   = 'Requested scope is not covered by the clients allowed scope.';
  const ID_BAD_SCOPE                    = 'RESTController\\core\\auth\\Common::MSG_BAD_SCOPE';
  const MSG_INVALID_CLIENT              = 'There is no client with api-key: {{api_key}}';
  const ID_INVALID_CLIENT               = 'RESTController\\core\\auth\\Common::ID_INVALID_CLIENT';
  const MSG_REVOKED                     = '{{types}} has been revoked or expired from database.';
  const ID_REVOKED                      = 'RESTController\\core\\auth\\Common::ID_REVOKED';


  /**
   * Function: DatabaseCleanup()
   *  Clears expired Authorization-Code and Access-Tokens from database.
   */
  public static function DatabaseCleanup() {
    // Delete expired tokens
    Database\RESTauthorization::deleteByWhere('expires < NOW()');
    Database\RESTaccess::deleteByWhere('expires < NOW()');
  }


  /**
   * Function: FetchRedirectUri($client, $redirectUri)
   *  Returns the original redirect_uri given as parameter if non null or
   *  fetches the clients stored redirect_uri from the database.
   *  Throws an exception is both (stored and parameter) are null.
   *
   * Parameters:
   *  $client <RESTclient> - Client object to fetch redirect_uri from if non was given
   *  $redirectUri <String> - The redirect_uri that was given as request parameter
   *
   * Return:
   *  <String> - Original redirect_uri or the value stored inside the database if no request parameter was given
   */
  public static function FetchRedirectUri($client, $redirectUri) {
    // Fetch redirect_uri from client db-entry if non was given
    if (!isset($redirectUri)) {
      // Fetch redirect_uri from client db-entry
      $redirectUri = $client->getKey('redirect_uri');

      // If no redirect_uri was given and non is attached to the client, exit!
      if (!(isset($redirectUri) && $redirectUri != false))
        throw new Exceptions\InvalidRequest(
          Libs\RESTRequest::MSG_MISSING,
          Libs\RESTRequest::ID_MISSING,
          array(
            'key' => 'redirect_uri'
          )
        );
    }

    // Fetch (updated) redirect_uri
    return $redirectUri;
  }


  /**
   * Function: CheckApiKey($apiKey)
   *  Checks if a client with given api-key exists and returns it.
   *  Throws an exception if no client exists!
   *
   * Parameters:
   *  $apiKey <String> - API-Key to check/return
   *
   * Return:
   *  <RESTclient> - Fetches client from database
   */
  public static function CheckApiKey($apiKey) {
    try {
      // Fecth client with given api-key (throws if non existent)
      return Database\RESTclient::fromApiKey($apiKey);
    }
    catch (Libs\Exceptions\Database $e) {
      throw new Exceptions\InvalidRequest(
        self::MSG_INVALID_CLIENT,
        self::ID_INVALID_CLIENT,
        array(
          'api_key' => $apiKey
        )
      );
    }
  }


  /**
   * Function: CheckIP($client, $remoteIp)
   *  Check if the resource-owners user-agent is allowed to use this client
   *  with his current ip address. Throws exception if ip is not allowed.
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
      throw new Exceptions\UnauthorizedClient(
        self::MSG_UNAUTHORIZED_CLIENT,
        self::ID_UNAUTHORIZED_CLIENT
      );

  }


  /**
   * Function: CheckUsername($userName)
   *  Checks if a user with a given name exists inside the current ilias-client.
   *  Throws an exception if no match is found.
   *
   * Note: the name check is case-sensitive
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
   *  Checks if the given resource-owner is allowed to use the client
   *  with given api-key. Throws an exception when use is not allowed to use client.
   *
   * Parameters:
   *  $apiKey <String> - API-Key of client to check user-restriction for
   *  $userId <Integer> - User-id that should be checked against given client
   */
  public static function CheckUserRestriction($client, $userId) {
    // Check user with given id exists in database (throws exception on problem)
    $username = Libs\RESTilias::getUserName($userId);

    // Check user restriction
    if (!$client->isUserAllowed($userId))
      throw new Exceptions\Denied(
        self::MSG_RESTRICTED_USER,
        self::ID_RESTRICTED_USER,
        array(
          'userID'    => $userId,
          'username'  => $username
        )
      );
  }


  /**
   * Function: CheckResourceOwner($userName, $passWord)
   *  Checks if the given resource-owner credentials (ILIAS username and password)
   *  are valid and throws an exception in case they are not valid.
   *
   * Parameters:
   *  $userName <String> - Username of resource-owner to authenticate
   *  $passWord <String> - Password for username of resource-owner required for authentification
   */
  public static function CheckResourceOwner($userName, $passWord) {
    // Check wether the resource owner credentials are valid
    if (!Libs\RESTilias::authenticate($userName, $passWord))
      throw new Exceptions\Credentials(
        self::MSG_WRONG_OWNER_CREDENTIALS,
        self::ID_WRONG_OWNER_CREDENTIALS,
        array(
          'username' => $userName,
          'password' => $passWord,
        )
      );
  }


  /**
   * Function: CheckScope($client, $scope)
   *  Checks if the given scope is allowed for the given client.
   *  Throws an exception if this is not the case.
   *
   * Parameters:
   *  $client <RESTclient> - RESTclient who's scope is used to check requested scope
   *  $scope <String> - Requested scope (string or string-list)
   */
  public static function CheckScope($client, $scope) {
    if (!$client->isScopeAllowed($scope))
      throw new Exceptions\Denied(
        self::MSG_BAD_SCOPE,
        self::ID_BAD_SCOPE,
        array(
          'requested' => $scope,
          'allowed'   => $client->getKey('scopes')
        )
      );
  }


  /**
   * Function: GetAccessToken($apiKey, $userId, $iliasClient, $scope, $withRefresh)
   *  Utility function used to create the Access-Token response, containing the access-
   *  and if enabled also the refresh-token, the expiration time note, type of token
   *  as well as scope note. (Note because the important values are stored inside the tokens themself!)
   *
   * Parameters:
   *  $apiKey <String> - Client used to generate the tokens (will be attached to tokens)
   *  $userId <Integer> - User-Id (inside ILIAS) of the resource-owner
   *  $iliasClient <String> - Current ILIAS client-id (will be attached to the tokens)
   *  $scope <String> - Requested scope for the generated tokens (will be attached to tokens)
   *  $withRefresh <Boolean> - [Optional] Wether to generate a refresh-token (Default: false)
   *
   * Return:
   *  <Array[Mixed]> - Formated data that can be send to the client as Access-Token response
   */
  public static function GetResponse($apiKey, $userId, $iliasClient, $scope, $withRefresh = false) {
    // Generate access-token
    $access = self::GetAccessToken($apiKey, $userId, $iliasClient, $scope);

    // Cleanup database
    self::DatabaseCleanup();

    // Generate refresh-token (if enabled)
    if ($withRefresh == true)
      $refresh = self::GetRefreshToken($apiKey, $userId, $iliasClient, $scope);

    // Return success-data
    return array(
      'access_token'  => $access->getTokenString(),
      'refresh_token' => (isset($refresh)) ? $refresh->getTokenString() : $withRefresh,
      'expires_in'    => $access->getRemainingTime(),
      'token_type'    => 'bearer',
      'scope'         => (isset($scope) && strlen($scope) > 0) ? $scope : null
    );
  }


  /**
   * Function: GetAccessToken($apiKey, $userId, $iliasClient, $scope)
   *  Generate access-token and store in database.
   *
   * Parameters:
   *  $apiKey <String> - Client used to generate the tokens (will be attached to tokens)
   *  $userId <Integer> - User-Id (inside ILIAS) of the resource-owner
   *  $iliasClient <String> - Current ILIAS client-id (will be attached to the tokens)
   *  $scope <String> - Requested scope for the generated tokens (will be attached to tokens)
   *  $withRefresh <Boolean> - [Optional] Wether to generate a refresh-token (Default: false)
   *
   * Return:
   *  <AccessToken> - Generated Access-Token
   */
  public static function GetAccessToken($apiKey, $userId, $iliasClient, $scope) {
    // Load access-token settings
    $settings  = Tokens\Settings::load('access');
    $access    = Tokens\Access::fromFields($settings, $userId, $iliasClient, $apiKey, $scope);

    // Inset token into DB (if enabled)
    $accessDB = Database\RESTaccess::fromRow(array(
      'hash'    => $access->getUniqueHash(),
      'token'   => $access->getTokenString(),
      'expires' => date("Y-m-d H:i:s", time() + $access->getRemainingTime())
    ));
    $accessDB->insert();

    // Return new access-token
    return $access;
  }


  /**
   * Function: GetRefreshToken($apiKey, $userId, $iliasClient, $scope)
   *  Returns existing refresh-token from in database or generate a new one and store in database.
   *
   * Parameters:
   *  $apiKey <String> - Client used to generate the tokens (will be attached to tokens)
   *  $userId <Integer> - User-Id (inside ILIAS) of the resource-owner
   *  $iliasClient <String> - Current ILIAS client-id (will be attached to the tokens)
   *  $scope <String> - Requested scope for the generated tokens (will be attached to tokens)
   *  $withRefresh <Boolean> - [Optional] Wether to generate a refresh-token (Default: false)
   *
   * Return:
   *  <RefreshToken> - Generated Refresh-Token
   */
  public static function GetRefreshToken($apiKey, $userId, $iliasClient, $scope) {
	global $ilDB;
    // Load refresh-token settings
    $settings  = Tokens\Settings::load('refresh');
    $refresh   = Tokens\Refresh::fromFields($settings, $userId, $iliasClient, $apiKey, $scope);
    $hash      = $refresh->getUniqueHash();

    // Used to catch if no existing refresh-key was found...

	/* try {
      // Check wether a refresh-token was already generated (throws on failure)
      $refreshDB = Database\RESTrefresh::fromHash($hash);
      $refreshDB->refreshed();
      $refreshDB->delete("hash LIKE ".$ilDB->quote($hash, "text"));
      // Use existing refresh-token instead
//      $token    = $refreshDB->getKey('token');
//      $refresh  = Tokens\Refresh::fromMixed($settings, $token);
    }
    catch (Libs\Exceptions\Database $e) {
      
    }*/

// Store newly generated refresh-token in database
	  $time       = date("Y-m-d H:i:s");
	  $refreshDB  = Database\RESTrefresh::fromRow(array(
		  'hash'          => $hash,
		  'token'         => $refresh->getTokenString(),
		  'last_refresh'  => $time,
		  'created'       => $time,
		  'refreshes'     => 0
	  ));
	  $refreshDB->insert();

    // Return existing or new refresh-token
    return $refresh;
  }
}
