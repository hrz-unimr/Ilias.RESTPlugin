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
use \RESTController\database as Database;


/**
 * Class: Authorize
 *  This class handles input, buisness-logic and request-parsing for the
 *   - Autorization-Code Grant during steps (D) and (E)
 *   - Resource Owner Credentials Grant during steps (B) and (C)
 *   - Client Credentials Grant during steps (A) and (B)
 *
 *  See https://tools.ietf.org/html/rfc6749#section-4 for more information.
 */
class Token extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_AUTHORIZATION_EXPIRED       = 'The Authorization-Code token has expired.';
  const ID_AUTHORIZATION_EXPIRED        = 'RESTController\\core\\auth::ID_AUTHORIZATION_EXPIRED';
  const MSG_AUTHORIZATION_MISTMATCH     = 'The Authorization-Code token content does not match the request parameters.';
  const ID_AUTHORIZATION_MISTMATCH      = 'RESTController\\core\\auth::ID_AUTHORIZATION_MISTMATCH';
  const MSG_GRANT_TYPE                  = 'Invalid grant_type \'{{grant_type}}\', must be one of ' .
                                          '\'authorization_code\' for Authorization-Code, ' .
                                          '\'password\' for Resource-Owner Credentials,' .
                                          '\'client_credentials\' for Client-Credentials or'.
                                          '\'refresh_token\' for exchaning a Refresh-Token.';
  const ID_GRANT_TYPE                   = 'RESTController\\core\\auth::ID_GRANT_TYPE';
  const MSG_RESOURCE_OWNER_DISABLED     = 'Resource-Owner grant is disabled for this client (api-key).';
  const ID_RESOURCE_OWNER_DISABLED      = 'RESTController\\core\\auth\\Authorize::ID_RESOURCE_OWNER_DISABLED';
  const MSG_CLIENT_CREDENTIALS_DISABLED = 'Client-Credentials grant is disabled for this client (api-key).';
  const ID_CLIENT_CREDENTIALS_DISABLED  = 'RESTController\\core\\auth\\Authorize::ID_CLIENT_CREDENTIALS_DISABLED';
  const MSG_REFRESH_DISABLED            = 'All Grant-Types with Refresh-Tokens are disabled for this client (api-key).';
  const ID_REFRESH_DISABLED             = 'RESTController\\core\\auth\\Authorize::ID_REFRESH_DISABLED';
  const MSG_REFRESH_EXPIRED             = 'The given Refresh-Token has expired.';
  const ID_REFRESH_EXPIRED              = 'RESTController\\core\\auth\\Authorize::ID_REFRESH_EXPIRED';
  const MSG_REFRESH_MISTMATCH           = 'The Refresh-Token content does not match with the given parameters.';
  const ID_REFRESH_MISTMATCH            = 'RESTController\\core\\auth\\Authorize::ID_REFRESH_MISTMATCH';
  const MSG_REFRESH_SCOPE               = 'The requested scope is not within the scope of the given Refresh-Token.';
  const ID_REFRESH_SCOPE                = 'RESTController\\core\\auth\\Authorize::ID_REFRESH_SCOPE';


  /**
   * Function: CheckGrantType($client, $type)
   *  Check wether the given request grant_type is supported and enabled for the given client
   *  throws an exception if one of the above is false.
   *
   * Parameters:
   *  $client <RESTclient> - Client object used to check wether given grant_type is enabled
   *  $type <String> - The grant_type that was given as request parameter
   */
  public static function CheckGrantType($client = null, $type) {
    if (!in_array($type, array('authorization_code', 'password', 'client_credentials', 'refresh_token')))
      throw new Exceptions\ResponseType(
        self::MSG_GRANT_TYPE,
        self::ID_GRANT_TYPE,
        array(
          'grant_type' => $type
        )
      );

    // Without a given client, only check grant_type is supported
    if (isset($client)) {
      // Authorization-Code is disabled?
      if ($type == 'authorization_code' && $client->getKey('grant_authorization_code') != true)
        throw new Exceptions\Denied(
          Common::MSG_AUTHORIZATION_CODE_DISABLED,
          Common::ID_AUTHORIZATION_CODE_DISABLED
        );

      // Resource-Owner Credentials is disabled?
      if ($type == 'password' && $client->getKey('grant_resource_owner') != true)
        throw new Exceptions\Denied(
          self::MSG_RESOURCE_OWNER_DISABLED,
          self::ID_RESOURCE_OWNER_DISABLED
        );

      // Client Credentials is disabled?
      if ($type == 'client_credentials' && $client->getKey('grant_client_credentials') != true)
        throw new Exceptions\Denied(
          self::MSG_CLIENT_CREDENTIALS_DISABLED,
          self::ID_CLIENT_CREDENTIALS_DISABLED
        );

      // No settings with refresh-token support is enabled?
      if (
        $type == 'refresh_token' &&
        (
          // All grant-types which support refresh-tokens are disabled
          $client->getKey('grant_authorization_code') == false && $client->getKey('grant_resource_owner') == false
          ||
          // Refresh tokens for all supported grant types are disabled
          $client->getKey('refresh_authorization_code') == false && $client->getKey('refresh_resource_owner') == false
        )
      )
        throw new Exceptions\Denied(
          self::MSG_REFRESH_DISABLED,
          self::ID_REFRESH_DISABLED
        );
    }
  }


  /**
   * Function: CheckAuthorizationCode($authorizationCode, $apiKey, $redirectUri)
   *  Validates the given authorization-code, making sure that it is neither expired,
   *  nor contains different values that those given as parameters (aka API-Key, redirect_uri).
   *  Throws an exception if one of the above is false.
   *
   * Parameters:
   *  $authorizationCode <String> - The Authorization-Code that was given as request parameter
   *  $apiKey <String> - The API-Key that was given as request parameter
   *  $redirectUri <String> - The redirect_uri that was given as request parameter
   *  $iliasClient <String> - The current ilias client
   *
   * Return:
   *  <AuthorizationToken> - The given Authorization-Code converted to a Token-Object
   */
  public static function CheckAuthorizationCode($authorizationCode, $apiKey, $redirectUri, $iliasClient) {
    // Convert authorization-code (string) to authorization-code (Token-Object)
    $settings       = Tokens\Settings::load('authorization');
    $authorization  = Tokens\Authorization::fromMixed($settings, $authorizationCode);

    // Check the authorization-code has not expired
    if ($authorization->isExpired()) {
      // Cleanup database
      Authorize::DatabaseCleanup();

      // Throw exception
      throw new Exceptions\Denied(
        self::MSG_AUTHORIZATION_EXPIRED,
        self::ID_AUTHORIZATION_EXPIRED
      );
    }

    // Compare authorization-code values with those given as parameters
    if (
      $iliasClient  != $authorization->getIliasClient() ||
      $apiKey       != $authorization->getApiKey() ||
      $redirectUri  != $authorization->getMisc()
    )
      throw new Exceptions\Denied(
        self::MSG_AUTHORIZATION_MISTMATCH,
        self::ID_AUTHORIZATION_MISTMATCH
      );

    // Return authorization-code (object)
    return $authorization;
  }


  /**
   * Function: CheckRefreshToken($refreshCode, $apiKey, $iliasClient, $scope)
   *
   *
   * Parameters:
   *  $refreshCode <> -
   *  $apiKey <> -
   *  $iliasClient <> -
   *  $scope <> -
   *
   * Return:
   *  <RefreshToken> -
   */
  public static function CheckRefreshToken($refreshCode, $apiKey, $iliasClient, $scope) {
    // Convert refresh-token (string) to refresh-token (Token-Object)
    $settings = Tokens\Settings::load('refresh');
    $refresh  = Tokens\Refresh::fromMixed($settings, $refreshCode);

    // Check the refresh-token has not expired
    if ($refresh->isExpired())
      throw new Exceptions\Denied(
        self::MSG_REFRESH_EXPIRED,
        self::ID_REFRESH_EXPIRED
      );

    // Compare refresh-token values with those given as parameters
    if (
      $iliasClient  != $refresh->getIliasClient() ||
      $apiKey       != $refresh->getApiKey()
    )
      throw new Exceptions\Denied(
        self::MSG_REFRESH_MISTMATCH,
        self::ID_REFRESH_MISTMATCH
      );

    // Compare refresh-token values with those given as parameters (scope is covered?)
    if (isset($scope) && !$refresh->hasScope($scope))
      throw new Exceptions\Denied(
        self::MSG_REFRESH_SCOPE,
        self::ID_REFRESH_SCOPE
      );

    // Return refresh-token (object)
    return $refresh;
  }


  /**
   * Function: FlowAuthorizationCode()
   *  Handles the overall grant flow for the token endpoint for the Authorization-Code grant type.
   *
   * Parameters:
   *  $grantType <String> - The grant_type that was given as request parameter
   *  $apiSecret <String> - The client secret used to authorize the given client
   *  $apiCert <Array[Mixed]> - The client-certificate used to authorize the given client
   *  $authorizationCode <String> - The Authorization-Code that was given as request parameter
   *  $apiKey <String> - The API-Key that was given as request parameter
   *  $redirectUri <String> - The redirect_uri that was given as request parameter
   *  $iliasClient <String> - The current ilias client
   *  $remoteIp <String> - The ip-address of the user-agent used by the resource-owner
   *
   * Return:
   *  <Array[Mixed]> - Data containing access- (and possibly refresh-) token upon successfull grant flow
   */
  public static function FlowAuthorizationCode($grantType, $apiKey, $apiSecret, $apiCert, $authorizationCode, $redirectUri, $iliasClient, $remoteIp) {
    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check grant-type is valid and enabled for this client (throws on problem)
    $type = ($grantType == 'authorization_code') ? $grantType : null;
    self::CheckGrantType($client, $type);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, $remoteIp);

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Convert authorization-code into authorization-token (and check correctness of contained values) (throws on problem)
    $authorization = self::CheckAuthorizationCode($authorizationCode, $apiKey, $redirectUri, $iliasClient);

    // Check resource-owner fullfills user-restriction (throws on problem)
    $iliasClient = $authorization->getIliasClient();
    $userId      = $authorization->getUserId();
    $scope       = $authorization->getScope();
    Common::CheckUserRestriction($apiKey, $userId);

    // Check that authorization-token is still active in DB (throws otherwise) (throws on problem)
    $authorizationDB  = Database\RESTauthorization::fromToken($authorizationCode);
    $authorizationDB->delete();

    // Return success-data
    return self::GetAccessToken($grantType, $client, $userId, $iliasClient, $apiKey, $scope);
  }


  /**
   * Function: FlowResourceOwnerCredentials($grantType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $scope)
   *  Handles the overall grant flow for the token endpoint for the Resource-Owner Credentials grant type
   *
   * Parameters:
   *  <> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowResourceOwnerCredentials($grantType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $scope) {
    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check grant-type is valid and enabled for this client (throws on problem)
    $type = ($grantType == 'password') ? $grantType : null;
    self::CheckGrantType($client, $type);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, $remoteIp);

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Check username and authorize RO
    $userId = Common::CheckUsername($userName);
    Common::CheckResourceOwner($userName, $passWord);

    // Check resource-owner fullfills user-restriction (throws on problem)
    Common::CheckUserRestriction($apiKey, $userId);

    // Return success-data
    return self::GetAccessToken($grantType, $client, $userId, $iliasClient, $apiKey, $scope);
  }


  /**
   * Function: FlowClientCredentials($grantType, $apiKey, $apiSecret, $apiCert, $iliasClient, $scope, $remoteIp)
   *  Handles the overall grant flow for the token endpoint for the Client Credentials grant type
   *
   * Parameters:
   *  <> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowClientCredentials($grantType, $apiKey, $apiSecret, $apiCert, $iliasClient, $scope, $remoteIp) {
    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check grant-type is valid and enabled for this client (throws on problem)
    $type = ($grantType == 'client_credentials') ? $grantType : null;
    self::CheckGrantType($client, $type);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, $remoteIp);

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Return success-data
    $userId = $client->getKey('client_credentials_userid');
    return self::GetAccessToken($grantType, $client, $userId, $iliasClient, $apiKey, $scope);
  }


  /**
   * Function: FlowRefreshToken($grantType, $apiKey, $apiSecret, $apiCert, $refreshCode, $iliasClient, $scope, $remoteIp)
   *  Handles the overall grant flow for the token endpoint for the exchange of refresh-token for a new access-token.
   *
   * Parameters:
   *  <> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowRefreshToken($grantType, $apiKey, $apiSecret, $apiCert, $refreshCode, $iliasClient, $scope, $remoteIp) {
    // Convert refresh-token (string) into refresh-token (object) (throws on problem or mismatched entries)
    $refresh = self::CheckRefreshToken($refreshCode, $apiKey, $iliasClient, $scope);

    // Use refresh-token scope if non was given
    if (!isset($scope))
      $scope = $refresh->getScope();

    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check grant-type is valid and enabled for this client (throws on problem)
    $type = ($grantType == 'refresh_token') ? $grantType : null;
    self::CheckGrantType($client, $type);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, $remoteIp);

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Check resource-owner fullfills user-restriction (throws on problem)
    $userId = $refresh->getUserId();
    Common::CheckUserRestriction($apiKey, $userId);

    // Check that refresh-token is still active in DB (throws otherwise) and update DB entries (timestamp, #refreshs)
    $refreshDB  = Database\RESTrefresh::fromToken($refreshCode);
    $refreshDB->refreshed();

    // Return success-data
    $data                   = self::GetAccessToken($grantType, $client, $userId, $iliasClient, $apiKey, $scope);
    $data['refresh_token']  = urlencode($refreshCode);
    return $data;
  }


  /**
   * Function: GetAccessToken($client, $userId, $iliasClient, $apiKey, $scope)
   *  Utility function used to create the Access-Token response, containing the access-
   *  and if enabled also the refresh-token, the expiration time note, type of token
   *  as well as scope note. (Note because the important values are stored inside the tokens themself!)
   *
   * Parameters:
   *  $grantType <String> - Grant-Type used to request the access-token
   *  $client <RESTclient> - Stored client-settings (required to query wether refresh-tokens are enabled)
   *  $userId <Integer> - User-Id (inside ILIAS) of the resource-owner
   *  $iliasClient <String> - Current ILIAS client-id (will be attached to the tokens)
   *  $apiKey <String> - Client used to generate the tokens (will be attached to tokens)
   *  $scope <String> - Requested scope for the generated tokens (will be attached to tokens)
   *
   * Return:
   *  <Array[Mixed]> - Formated data that can be send to the client as Access-Token response
   */
  public static function GetAccessToken($grantType, $client, $userId, $iliasClient, $apiKey, $scope) {
    // Generate access-token
    $accessSettings     = Tokens\Settings::load('access');
    $access             = Tokens\Access::fromFields($accessSettings, $userId, $iliasClient, $apiKey, $scope);

    // Generate refresh-token (if enabled)
    if (
      $grantType == 'authorization_code' && $client->getKey('refresh_authorization_code') ||
      $grantType == 'password' && $client->getKey('refresh_resource_owner')
    ) {
      // Load refresh-settings
      $refreshSettings = Tokens\Settings::load('refresh');

      // Extract primary-key of given client
      $apiId = $client->getKey('id');

      // Used to catch if no existing refresh-key was found...
      try {
        // Check wether a refresh-token was already generated
        $refreshDB = Database\RESTrefresh::fromIDs($userId, $apiId);
        $refreshDB->refreshed();

        // Return existing refresh-token
        $refreshCode = $refreshDB->getKey('token');
        $refresh     = Tokens\Refresh::fromMixed($refreshSettings, $refreshCode);

        // Need to update scope? (This has the side-effect of invalidating other)
        if (!$refresh->hasScope($scope)) {
          // Update refresh-token
          $refresh->setScope($scope);
          $refreshCode = $refresh->getTokenString();

          // Update DB with updated refresh-token
          $refreshDB->setKey('token', $refreshCode);
          $refreshDB->update();
        }
      }
      catch (Libs\Exceptions\Database $e) {
        // Create a new refresh-token
        $refresh      = Tokens\Refresh::fromFields($refreshSettings, $userId, $iliasClient, $apiKey, $scope);
        $refreshCode  = $refresh->getTokenString();

        // Store token in database
        $time = date("Y-m-d H:i:s");
        $refreshDB = Database\RESTrefresh::fromRow(array(
          'user_id'       => $userId,
          'api_id'        => $apiId,
          'token'         => $refreshCode,
          'last_refresh'  => $time,
          'created'       => $time,
          'refreshes'     => 0
        ));
        $refreshDB->insert();
      }
    }

    // Return success-data
    return array(
      'access_token'  => $access->getTokenString(),
      'refresh_token' => (isset($refreshCode)) ? $refreshCode : null,
      'expires_in'    => $access->getRemainingTime(),
      'token_type'    => 'Bearer',
      'scope'         => (isset($scope) && strlen($scope) > 0) ? $scope : null
    );
  }
}
