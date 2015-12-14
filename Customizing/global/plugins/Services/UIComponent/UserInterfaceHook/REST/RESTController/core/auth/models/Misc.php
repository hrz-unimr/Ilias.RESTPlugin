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
 * Class: Misc
 *  Handles misc buisness logic, not part of any oauth2 rfc.
 */
class Misc extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_NO_TOKEN = 'No token was given, supported are refresh-tokens and access-tokens.';
  const ID_NO_TOKEN  = 'RESTController\\core\\auth\\Misc::ID_NO_TOKEN';


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
  public static function GetToken($accessCode = null, $refreshCode = null) {
    // Stores generated information about tokens
    $info = array();

    // Generate access-token from string
    if (isset($accessCode)) {
      // Load access-token settings
      $settings = Tokens\Settings::load('access');
      $token    = Tokens\Access::fromMixed($settings, $accessCode);
      $info['access_token'] = self::GetTokenInfo($token);
    }

    // Generate refresh-token from string
    if (isset($refreshCode)) {
      // Load access-token settings
      $settings = Tokens\Settings::load('refresh');
      $token    = Tokens\Refresh::fromMixed($settings, $refreshCode);
      $info['refresh_token'] = self::GetTokenInfo($token);
    }

    // Return generated information
    return $info;
  }


  /**
   * Function: GetTokenInfo($token)
   *  Utility function that actually compiles usefull information using a given *-token object.
   *
   * Parameters:
   *  $token <BaseToken> - Tokens about which information should be collected
   *
   * Return:
   *  <Array[Mixed]> - Array containing information about token, see below for details
   */
  public static function GetTokenInfo($token) {
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
}
