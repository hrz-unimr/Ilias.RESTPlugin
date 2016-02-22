<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2\Tokens;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\oauth2_v2\Exceptions  as Exceptions;
use \RESTController\database              as Database;

/**
 * Class: (Token-) Settings
 *  This object stored internal settings for managing tokens.
 *  Most importantly this stores the internal salt value
 *  used to encode all tokens. This value should never be leaked
 *  since this would allow any 3rd. party to generate valid tokens
 *  without any restrictions for your endpoints.
 */
class Settings {
  // Allow to re-use status messages and codes
  const MSG_NO_SALT = 'Token-Settings require a valid salt.';
  const ID_NO_SALT  = 'RESTController\\core\\auth\\Tokens\\Settings::ID_NO_SALT';
  const MSG_NO_DB   = 'Could not load settings for {{token}} from database.';
  const ID_NO_DB    = 'RESTController\\core\\auth\\Tokens\\Settings::ID_NO_DB';


  // Internally stores the salt and time-to-live value
  protected $salt;
  protected $ttl;


  /**
   * Constructor:
   *  Create a new TokenSettings object with settings provided as parameters
   *
   * Parameters:
   *  $salt <String> - Salt-String used during token generation/hashing.
   *  $ttl <Integer> - [Optional] Default time-to-live for a token
   */
  public function __construct($salt, $ttl = 30) {
    // A custom salt needs to be set, ALWAYS!
    if (!$salt)
      throw new Exceptions\TokenSettings(self::MSG_NO_SALT, self::ID_NO_SALT);

    // Store values
    $this->salt = $salt;
    $this->ttl  = $ttl;
  }


  /**
   * Function: load($type)
   *  Creates a new TokenSettings object containing SALT and TTL
   *  for given type of token.
   *
   * Parameters:
   *  $type <String> - String name of token (-type) to load settings for
   *
   * Return:
   *  <TokenSettings> - Newly created TokenSettingsobject created from database settings
   */
  public static function load($type) {
    // Fetch token-setting values from database
    switch ($type) {
      // Short-(Lived-)Token
      case 'short':
        $settings  = Database\RESTconfig::fetchSettings(array(
          'short_token_ttl',
          'salt'
        ));
        $settings['ttl'] = $settings['short_token_ttl'];
        break;

      // Access-Token
      case 'access':
        $settings  = Database\RESTconfig::fetchSettings(array(
          'access_token_ttl',
          'salt'
        ));
        $settings['ttl'] = $settings['access_token_ttl'];
        break;

      // Refresh-Token
      case 'refresh':
        $settings  = Database\RESTconfig::fetchSettings(array(
          'refresh_token_ttl',
          'salt'
        ));
        $settings['ttl'] = $settings['refresh_token_ttl'];
        break;

      // Authorization-Token
      case 'authorization':
        $settings  = Database\RESTconfig::fetchSettings(array(
          'authorization_token_ttl',
          'salt'
        ));
        $settings['ttl'] = $settings['authorization_token_ttl'];
        break;
    }

    // Make sure required values where fetched
    if (isset($settings['salt']) && isset($settings['ttl']))
      return new self($settings['salt'], $settings['ttl']);

    // When we have not returned by now throw exception about missing db-entry
    throw new Exceptions\TokenSettings(
      self::MSG_NO_DB,
      self::ID_NO_DB,
      array(
        'token' => $type
      )
    );
  }


  /**
   * Getter-Functions:
   *  getSalt() - Returns stored salt
   *  getTTL() - Returns stored time-to-live
   */
  public function getSalt() {
    return $this->salt;
  }
  public function getTTL() {
    return $this->ttl;
  }
}
