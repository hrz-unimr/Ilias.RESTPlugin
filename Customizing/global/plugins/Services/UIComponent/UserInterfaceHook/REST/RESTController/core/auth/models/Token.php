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
  const MSG_AUTHORIZATION_EXPIRED   = 'The Authorization-Code token has expired.';
  const ID_AUTHORIZATION_EXPIRED    = 'RESTController\\core\\auth::ID_AUTHORIZATION_EXPIRED';
  const MSG_AUTHORIZATION_MISTMATCH = 'The Authorization-Code token content does not match the request parameters.';
  const ID_AUTHORIZATION_MISTMATCH  = 'RESTController\\core\\auth::ID_AUTHORIZATION_MISTMATCH';
  const MSG_GRANT_TYPE              = 'Invalid grant_type \'{{grant_type}}\', must be one of ' .
                                      '\'authorization_code\' for Authorization-Code, ' .
                                      '\'password\' for Resource-Owner Credentials or ' .
                                      '\'client_credentials\' for Client-Credentials';
  const ID_GRANT_TYPE               = 'RESTController\\core\\auth::ID_GRANT_TYPE';


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
    if (!in_array($type, array('authorization_code', 'password', 'client_credentials')))
      throw new Exceptions\ResponseType(
        self::MSG_GRANT_TYPE,
        self::ID_GRANT_TYPE,
        array(
          'response_type' => $param['response_type']
        )
      );

    // Without a given client, only check grant_type is supported
    if (!isset($client)) {
      if ($type == 'authorization_code' && $client->getKey('grant_authorization_code') != true)
        throw new Exception\Denied(
          Common::MSG_AUTHORIZATION_CODE_DISABLED,
          Common::ID_AUTHORIZATION_CODE_DISABLED
        );
      if ($type == 'password' && $client->getKey('grant_resource_owner') != true)
        throw new Exception\Denied(
          Common::MSG_RESOURCE_OWNER_DISABLED,
          Common::ID_RESOURCE_OWNER_DISABLED
        );
      if ($type == 'client_credentials' && $client->getKey('grant_client_credentials') != true)
        throw new Exception\Denied(
          Common::MSG_CLIENT_CREDENTIALS_DISABLED,
          Common::ID_CLIENT_CREDENTIALS_DISABLED
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
    // Convert authorization-code (string) to authorization-code (Token)
    $settings       = Tokens\Settings::load('authorization');
    $authorization  = Tokens\Authorization::fromMixed($settings, $authorizationCode);

    // Check the authorization-code has not expired
    if ($authorization->isExpired())
      throw new Exception\Denied(
        self::MSG_AUTHORIZATION_EXPIRED,
        self::ID_AUTHORIZATION_EXPIRED
      );

    // Compare authorization-code values with those given as parameters
    if (
      $iliasClient  != $authorization->getIliasClient() ||
      $apiKey       != $authorization->getApiKey() ||
      $redirectUri  != $authorization->getMisc()
    )
      throw new Exception\Denied(
        self::MSG_AUTHORIZATION_MISTMATCH,
        self::ID_AUTHORIZATION_MISTMATCH
      );

    return $authorization;
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
    self::CheckGrantType($client, $grantType);

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
   * Function: FlowResourceOwnerCredentials()
   *  Handles the overall grant flow for the token endpoint for the Resource-Owner Credentials grant type
   *
   * Parameters:
   *  <> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowResourceOwnerCredentials() {
    die('This is a stub');
  }


  /**
   * Function: FlowClientCredentials()
   *  Handles the overall grant flow for the token endpoint for the Client Credentials grant type
   *
   * Parameters:
   *  <> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowClientCredentials() {
    die('This is a stub');
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
      $refreshSettings  = Tokens\Settings::load('refresh');
      $refresh          = Tokens\Refresh::fromFields($refreshSettings, $userId, $iliasClient, $apiKey, $scope);
    }

    // Return success-data
    return array(
      'access_token'  => $access->getTokenString(),
      'refresh_token' => (isset($refresh)) ? $refresh->getTokenString() : null,
      'expires_in'    => $access->getRemainingTime(),
      'token_type'    => 'Bearer',
      'scope'         => $scope
    );
  }
}
