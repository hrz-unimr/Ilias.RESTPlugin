<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs     as Libs;
use \RESTController\database as Database;


/**
 * Class: Misc
 *  Handles misc buisness logic, not part of any oauth2 rfc.
 */
class Misc extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_BRIDGE_DISABLED   = 'The ILIAS-oAuth2 bridge is diabled (in this direction).';
  const ID_BRIDGE_DISABLED    = 'RESTController\\core\\auth\\Misc::ID_BRIDGE_DISABLED';
  const MSG_INVALID_SESSION   = 'The given data does not match any valid active ILIAS-Session.';
  const ID_INVALID_SESSION    = 'RESTController\\core\\auth\\Misc::ID_INVALID_SESSION';
  const MSG_TOKEN_MISMATCH    = 'Parameters do not match content of given access- or refresh-token.';
  const ID_TOKEN_MISMATCH     = 'RESTController\\core\\auth\\Misc::ID_TOKEN_MISMATCH';


  /**
   * Function: FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $scope, $userId)
   *  Handles checks that are required on all routes, such as existing api-key,
   *  ip-restriction, client-credentials and user-restrictions.
   *
   * Parameters:
   *  apiKey <String> -  API-Key that was is used to request authorization
   *  apiSecret <String> - Secret that was given (eg. was parameter) for client-authorization
   *  apiCert <Array<String>> - Client certificate (pre-parsed array) required for client-authorization (see RESTclients::getClientCertificate())
   *  remoteIp <String> - Secret that was given (eg. was parameter) for client-authorization
   *  userId <Number> - UserId that was is requesting authorization
   *
   * Return:
   *  <RESTclient> - The RESTClient-Instance represented by the given api-key
   */
  public static function FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId) {
    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, $remoteIp);

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, false);

    // Check resource-owner fullfills user-restriction (throws on problem)
    Common::CheckUserRestriction($client, $userId);

    // Return reference to fetched RESTclient entry
    return $client;
  }


  /**
   * Function: GetToken($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $tokenCode)
   *  Creates internal access- and refresh-token representations for the inputs (strings)
   *  given and generates information-data about them.
   *
   * Parameters:
   *  apiKey <String> - API-Key used for accessing the route requesting this information
   *  apiSecret <String> - API-Secret required for client-authorization
   *  apiCert <Array<String>> - Client certificate (pre-parsed array) required for client-authorization (see RESTclients::getClientCertificate())
   *  iliasClient <String> - ILIAS ClientId used for accessing the route requesting this information
   *  remoteIp <String> - Remote-IP of application  accessing the route requesting this information
   *  tokenCode <String> - String-Representation of access- or resfresh-token to return information about
   *
   * Return:
   *  <Array[Mixed]> - Array containing information about token
   */
  public static function FlowTokenInfo($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $tokenCode) {
    // Load access-token settings
    $token  = Tokens\Base::factory($tokenCode);
    $userId = $token->getUserId();

    // Check wether api-keys match
    if ($apiKey != $token->getApiKey() || $iliasClient != $token->getIliasClient())
      throw new Exceptions\InvalidRequest(
        self::MSG_TOKEN_MISMATCH,
        self::ID_TOKEN_MISMATCH
      );

    // Invoke common checks for all flows (throws on error)
    self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Store TTL (since it might change over time)
    $ttl = $token->getRemainingTime();

    // Build token-info data
    return array(
      'user_id'       => $token->getUserId(),
      'user_name'     => $token->getUserName(),
      'ilias_client'  => $token->getIliasClient(),
      'api_key'       => $token->getApiKey(),
      'scope'         => $token->getScope(),
      'misc'          => $token->getMisc(),
      'expires'       => ($ttl > 0) ? date("Y-m-d H:i:s", time() + $ttl) : null,
      'ttl'           => $ttl
    );
  }


  /**
   * Function: DeleteAccessToken($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $tokenCode)
   *  Deletes the given access-token (string) from the database
   *  thus invalidating it.
   *
   * Parameters:
   *  apiKey <String> - API-Key used for accessing the route requesting token removal
   *  apiSecret <String> - API-Secret required for client-authorization
   *  apiCert <Array<String>> - Client certificate (pre-parsed array) required for client-authorization (see RESTclients::getClientCertificate())
   *  iliasClient <String> - ILIAS ClientId used for accessing the routetoken removal
   *  remoteIp <String> - Remote-IP of application  accessing the routetoken removal
   *  tokenCode <String> - String-Representation of access- or resfresh-token shat should be deleted
   */
  public static function FlowDeleteToken($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $tokenCode) {
    // Load access-token settings
    $token  = Tokens\Base::factory($tokenCode);
    $userId = $token->getUserId();

    // Invoke common checks for all flows (throws on error)
    self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Check wether api-keys match
    if ($apiKey != $token->getApiKey() || $iliasClient != $token->getIliasClient())
      throw new Exceptions\InvalidRequest(
        self::MSG_TOKEN_MISMATCH,
        self::ID_TOKEN_MISMATCH
      );

    try {
      switch ($token->getClass()) {
        // Delete access-token from DB
        case 'access':
          // Fetch DB entry for given access-token and delete it
          $accessDB = Database\RESTaccess::fromToken($tokenCode);
          $accessDB->delete();
          break;

        // Delete refresh-token from DB
        case 'refresh':
          // Fetch DB entry for given access-token and delete it
          $accessDB = Database\RESTrefresh::fromToken($tokenCode);
          $accessDB->delete();
          break;
      }
    }
    // We ignore any error (such that non-existant access-token)
    catch(Libs\Exceptions\Database $e) { }
  }


  /**
   * Function: FlowFromILIAS($apiKey, $apiSecret, $apiCert, $userId, $token, $sessionID, $iliasClient, $remoteIp, $scope)
   *  Generates a new OAuth2 access-token from a valid ILIAS session.
   *  Note: The Oauth client (via its API-Key) must be allowed to use this bridge)
   *
   * Parameters:
   *  apiKey <String> - API-Key used for accessing the route requesting token removal
   *  apiSecret <String> - API-Secret required for client-authorization
   *  apiCert <Array<String>> - Client certificate (pre-parsed array) required for client-authorization (see RESTclients::getClientCertificate())
   *  iliasClient <String> - ILIAS ClientId used for accessing the routetoken removal
   *  remoteIp <String> - Remote-IP of application  accessing the routetoken removal
   *  userId <Number> - UserId that was is requesting authorization
   *  scope <String> - Requested oauth2 scope for generated token
   */
  public static function FlowFromILIAS($apiKey, $apiSecret, $apiCert, $userId, $token, $sessionID, $iliasClient, $remoteIp, $scope) {
    // Invoke common checks for all flows (throws on error)
    $client = self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Check requested scope...
   /* Common::CheckScope($client, $scope);

    // Check wether (this direction of) the ilias-birdge is enabled
    if (!$client->isBridgeAllowed('FromILIAS'))
      throw new Exceptions\Denied(
        self::MSG_BRIDGE_DISABLED,
        self::ID_BRIDGE_DISABLED
      );

    // Check session
    if (!Libs\RESTilias::checkSession($userId, $token, $sessionID))
      throw new Exceptions\Credentials(
        self::MSG_INVALID_SESSION,
        self::ID_INVALID_SESSION,
        array(
          'user_id'   => $userId,
          'token'     => $token,
          'session'   => $sessionID
        )
      );
*/
    // Generate access-token
    return Common::GetResponse($apiKey, $userId, $iliasClient, $scope, false);
  }


  /**
   * Function: FlowFromOAUTH($apiKey, $apiSecret, $apiCert, $accessCode, $iliasClient, $remoteIp)
   *  Generates a valid ILIAS session from a given access-token and returns the cookies required by
   *  the browser to use the generated session.
   *  Note: The Oauth client (via its API-Key and access-token) must be allowed to use this bridge)
   *
   * Parameters:
   *  apiKey <String> - API-Key used for accessing the route requesting token removal
   *  apiSecret <String> - API-Secret required for client-authorization
   *  apiCert <Array<String>> - Client certificate (pre-parsed array) required for client-authorization (see RESTclients::getClientCertificate())
   *  iliasClient <String> - ILIAS ClientId used for accessing the routetoken removal
   *  remoteIp <String> - Remote-IP of application  accessing the routetoken removal
   *  $accessCode <String> - Strin representation of access-token which should be exchanged for an ILIAS session
   */
  public static function FlowFromOAUTH($apiKey, $apiSecret, $apiCert, $accessCode, $iliasClient, $remoteIp) {
    // Convert access-token (string) to access-token (Object) (throws on error)
    $settings = Tokens\Settings::load('access');
    $access   = Tokens\Access::fromMixed($settings, $accessCode);

    // Invoke common checks for all flows (throws on error)
    $userId = $access->getUserId();
    $client = self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Check wether api-keys match
    if ($apiKey != $access->getApiKey() || $iliasClient != $access->getIliasClient())
      throw new Exceptions\InvalidRequest(
        self::MSG_TOKEN_MISMATCH,
        self::ID_TOKEN_MISMATCH
      );

    // Check wether (this direction of) the ilias-birdge is enabled
    if (!$client->isBridgeAllowed('FromOAUTH'))
      throw new Exceptions\Denied(
        self::MSG_BRIDGE_DISABLED,
        self::ID_BRIDGE_DISABLED
      );

    // Check wether access-token exists in DB, if enabled (throws on error)
    $accessDB  = Database\RESTaccess::fromToken($accessCode);

    // Generate new session (and return cookie data)
    return Libs\RESTilias::createSession($userId);
  }


  /**
   * Function: FlowDeleteSession($apiKey, $apiSecret, $apiCert, $remoteIp, $userId, $token, $sessionID)
   *  Terminates an ILIAS session for the given user, with given php sessionId and ILIAS session token.
   *  Deletes the database entry, thus invaliding given session.
   *
   * Parameters:
   *  apiKey <String> - API-Key used for accessing the route requesting token removal
   *  apiSecret <String> - API-Secret required for client-authorization
   *  apiCert <Array<String>> - Client certificate (pre-parsed array) required for client-authorization (see RESTclients::getClientCertificate())
   *  iliasClient <String> - ILIAS ClientId used for accessing the routetoken removal
   *  remoteIp <String> - Remote-IP of application  accessing the routetoken removal
   *  userId <Number> - UserId whos session should be deleted (See Libs\RESTilias::deleteSession())
   *  sessionID <String> - PHP session if that should be deleted  (See Libs\RESTilias::deleteSession())
   *  token <String> - ILIAS session token that should be deleted  (See Libs\RESTilias::deleteSession())
   */
  public static function FlowDeleteSession($apiKey, $apiSecret, $apiCert, $remoteIp, $userId, $token, $sessionID) {
    // Invoke common checks for all flows (throws on error)
    self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Destroy given ILIAS session
    Libs\RESTilias::deleteSession($userId, $token, $sessionID);
  }
}
