<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs     as Libs;
use \RESTController\database as Database;


/**
 * Class: Misc
 *  Handles misc buisness logic, not part of any oauth2 rfc.
 */
class Misc extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_NO_TOKEN          = 'No token was given, supported are refresh-tokens and access-tokens.';
  const ID_NO_TOKEN           = 'RESTController\\core\\auth\\Misc::ID_NO_TOKEN';
  const MSG_BRIDGE_DISABLED   = 'The ILIAS-oAuth2 bridge is diabled (in this direction).';
  const ID_BRIDGE_DISABLED    = 'RESTController\\core\\auth\\Misc::ID_BRIDGE_DISABLED';
  const MSG_INVALID_SESSION   = 'The given data does not match any valid active ILIAS-Session.';
  const ID_INVALID_SESSION    = 'RESTController\\core\\auth\\Misc::ID_INVALID_SESSION';
  const MSG_TOKEN_MISMATCH    = 'Parameters do not match content of given access- or refresh-token.';
  const ID_TOKEN_MISMATCH     = 'RESTController\\core\\auth\\Misc::ID_TOKEN_MISMATCH';


  /**
   *
   */
  public static function FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $scope, $userId) {
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
   * Function: GetToken($accessCode, $refreshCode)
   *  Creates internal access- and refresh-token representations for the inputs (strings)
   *  given and generates information-data about them.
   *
   * Parameters:
   *  $accessCode <String> - String representing an access-token
   *  $refreshCode <String> - String representing an refresh-token
   *
   * Return:
   *  <Array[Mixed]> - Array containing information about token
   */
  public static function FlowTokenInfo($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $tokenCode) {
    // Load access-token settings
    $token  = Tokens\Base::factory($tokenCode);
    $userId = $token->getUserId();

    // Check wether api-keys match
    if ($apiKey != $access->getApiKey() || $iliasCLient != $access->getIliasClient())
      throw new Exceptions\InvalidRequest(
        self::MSG_TOKEN_MISMATCH,
        self::ID_TOKEN_MISMATCH
      );

    // Invoke common checks for all flows (throws on error)
    Common::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

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
   * Function: DeleteAccessToken($accessCode)
   *  Deletes the given access-token (string) from the database
   *  thus invalidating it.
   *
   * Parameters:
   *  $accessCode <String> - String representation of access-token to be deleted
   */
  public static function FlowDeleteToken($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $tokenCode) {
    // Load access-token settings
    $token  = Tokens\Base::factory($tokenCode);
    $userId = $token->getUserId();

    // Invoke common checks for all flows (throws on error)
    Common::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Check wether api-keys match
    if ($apiKey != $access->getApiKey() || $iliasCLient != $access->getIliasClient())
      throw new Exceptions\InvalidRequest(
        self::MSG_TOKEN_MISMATCH,
        self::ID_TOKEN_MISMATCH
      );

    try {
      switch ($this->getClass()) {
        // Delete access-token from DB
        case 'access':
          // Fetch DB entry for given access-token and delete it
          $accessDB = Database\RESTaccess::fromToken($tokenCode);
          $accessDB->delete();
          break;

        // Delete refresh-token from DB
        case 'access':
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
   *
   */
  public static function FlowFromILIAS($apiKey, $apiSecret, $apiCert, $userId, $token, $sessionID, $iliasClient, $remoteIp, $scope) {
    // Invoke common checks for all flows (throws on error)
    $client = self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Check requested scope...
    Common::CheckScope($client, $scope);

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

    // Generate access-token
    return Common::GetResponse($apiKey, $userId, $iliasClient, $scope, false);
  }


  /**
   *
   */
  public static function FlowFromOAUTH($apiKey, $apiSecret, $apiCert, $accessCode, $iliasClient, $remoteIp) {
    // Convert access-token (string) to access-token (Object) (throws on error)
    $settings = Tokens\Settings::load('access');
    $access   = Tokens\Access::fromMixed($settings, $accessCode);

    // Invoke common checks for all flows (throws on error)
    $userId = $access->getUserId();
    $client = self::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Check wether api-keys match
    if ($apiKey != $access->getApiKey() || $iliasCLient != $access->getIliasClient())
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
    if (self::getApp()->AccessTokenDB())
      $accessDB  = Database\RESTaccess::fromToken($accessCode);

    // Generate new session (and return cookie data)
    return Libs\RESTilias::createSession($userId);
  }


  /**
   *
   */
  public static function FlowDeleteSession($apiKey, $apiSecret, $apiCert, $remoteIp, $userId, $token, $sessionID) {
    // Invoke common checks for all flows (throws on error)
    Common::FlowAll($apiKey, $apiSecret, $apiCert, $remoteIp, $userId);

    // Destroy given ILIAS session
    Libs\RESTilias::deleteSession($userId, $token, $sessionID);
  }
}
