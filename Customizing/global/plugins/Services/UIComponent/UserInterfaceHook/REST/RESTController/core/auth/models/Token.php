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
   *
   */
  public static function FetchGrantType($request) {
    return $request->params('grant_type', null, true);
  }


  /**
   *
   */
  public static function FetchAuthorizationCodeParameters($request) {
    return array(
      'api_key'       => $request->params('api_key', null, true),
      'api_secret'    => $request->params('api_secret'),
      'redirect_uri'  => $request->params('redirect_uri', null, true)
    );
  }


  /**
   *
   */
  public static function FetchResourceOwnerParameters($request) {
    return array(
      'api_key'     => $request->params('api_key', null, true),
      'api_secret'  => $request->params('api_secret'),
      'username'    => $request->params('username', null, true),
      'password'    => $request->params('password', null, true),
      'scope'       => $request->params('scope')
    );
  }


  /**
   *
   */
  public static function FetchClientCredentialParameters($request) {
    return array(
      'api_key'     => $request->params('api_key', null, true),
      'api_secret'  => $request->params('api_secret'),
      'scope'       => $request->params('scope')
    );
  }


  /**
   *
   */
  public static function FlowAuthorizationCode($grantType, $apiKey, $apiSecret, $apiCert, $authorizationCode, $redirectUri, $remoteIp) {
    // Check if client with api-key exists
    $client = Common::CheckApiKey($apiKey);

    // Check grant-type is valid and enabled for this client
    Common::CheckGrantType($client, $grantType);

    // Check client fullfills ip-restriction
    Common::CheckIP($client, Common::FetchUserAgentIP());

    // Client client is authorized if enabled
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Convert authorization-code into authorization-token (and check correctness of contained values)
    $authorization = Common::CheckAuthorizationCode($authorizationCode, $apiKey, $redirectUri);

    // Check resource-owner fullfills user-restriction
    $iliasClient = $authorization->getIliasClient();
    $userId      = $authorization->getUserId();
    $scope       = $authorization->getScope();
    Common::CheckUserRestriction($apiKey, $userId);

    // Check that authorization-token is still active in DB (throws otherwise)
    $authorizationDB  = Database\RESTauthorization::fromToken($authorizationCode);
    $authorizationDB->delete();

    // Return success-data
    return self::GetAccessToken($client, $userId, $iliasClient, $apiKey, $scope);
  }


  /**
   *
   */
  public static function GetAccessToken($client, $userId, $iliasClient, $apiKey, $scope) {
    // Generate access-token
    $accessSettings     = Tokens\Settings::load('access');
    $access             = Tokens\Access::fromFields($accessSettings, $userId, $iliasClient, $apiKey, $scope);

    // Generate refresh-token (if enabled)
    if ($client->getKey('refresh_authorization_code')) {
      $refreshSettings  = Tokens\Settings::load('refresh');
      $access           = Tokens\Refresh::fromFields($refreshSettings, $userId, $iliasClient, $apiKey, $scope);
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
