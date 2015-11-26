<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Tokens;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\auth\Exceptions as Exceptions;


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
  const ID_NO_SALT  = 'RESTController\core\auth\Tokens\Settings::ID_NO_SALT';
  const MSG_NO_SALT = 'Token-Settings require a valid salt.';


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
