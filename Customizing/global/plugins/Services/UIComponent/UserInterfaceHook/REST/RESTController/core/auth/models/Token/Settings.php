<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Token;


/**
 * Class: (Token-) Settings
 *  This object stored internal settings for managing tokens.
 *  Most importantly this stores the internal salt value
 *  used to encode all tokens. This value should never be leaked
 *  since this would allow any 3rd. party to generate valid tokens
 *  without any restrictions for your endpoints.
 */
class Settings {
  // Internally stores the salt and time-to-live value
  protected $salt;
  protected $ttl;


  /**
   * Constructor:
   *
   *
   * Parameters:
   *  $salt <String> - Salt-String used during token generation/hashing.
   *  $ttl <Integer> - Default time-to-live for a token
   */
  public function __construct($salt, $ttl) {
    // A custom salt needs to be set, ALWAYS!
    if (!$salt)
      throw new \Exception('Token-Settings require a valid salt-value.');

    // Time-To-Live may have a fallback value
    if (!$ttl)
      $ttl = 30;

    // Store values
    $this->salt = $salt;
    $this->ttl = $ttl;
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
