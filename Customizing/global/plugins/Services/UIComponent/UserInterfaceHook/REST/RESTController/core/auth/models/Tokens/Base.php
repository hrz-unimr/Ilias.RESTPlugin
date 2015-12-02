<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Tokens;


/**
 * Class: Base (-Token)
 *  (Convieved) Abstract class for all Tokens.
 *  In this case for Generic (Access- & Refresh-) as well as Bearer-Tokens.
 */
class Base {
  // Allow to re-use status messages and codes
  const MSG_INVALID_FIELDS  = 'Token contains invalid field: %s';
  const ID_INVALID_FIELDS   = 'RESTController\\core\\auth\\Base::ID_INVALID_FIELDS';
  const MSG_INVALID_SIZE    = 'Token must contain exactly %d elements.';
  const ID_INVALID_SIZE     = 'RESTController\\core\\auth\\Base::ID_INVALID_SIZE';


  // Stored the actuall token-data (as array)
  protected $tokenArray;

  // All keys the token is allowed to contain
  // A token is only valid if it contains ONLY keys from this variable.
  protected static $fields;


  /**
   * Constructor:
   *  Creates a new 'base' token.
   */
  protected function __construct() { }


  /**
   * Function: setToken($tokenArray)
   *  Allows to update current token with data from
   *  token given as array.
   *  Try to use setEntry($field, $value) where possible instead.
   *
   * Parameters:
   *  $tokenArray <Array[Mixed]> - Array of string (key & value) elements representing a valid token
   */
  public function setToken($tokenArray) {
    // Make sure token is valid (size)
    if (is_array($tokenArray) && count($tokenArray) == count(static::$fields)) {
      // Make sure token is valid (keys)
      foreach ($tokenArray as $key => $value)
        if (!in_array($key, static::$fields))
          throw new Exceptions\TokenInvalid(sprintf(self::MSG_INVALID_FIELDS, $key), self::ID_INVALID_FIELDS);

      // Update token
      $this->tokenArray = $tokenArray;
    }

    // Otherwise throw an exception
    else
      throw new Exceptions\TokenInvalid(sprintf(self::MSG_INVALID_SIZE, count(static::$fields)), self::ID_INVALID_SIZE);
  }


  /**
   * Function: getTokenArray()
   *  Returns the current token object as array.
   *  Try to use getEntry($field) where possible instead.
   *
   * Return:
   *  <Array[Mixed]> - Internal token-data (use with care!)
   */
  public function getTokenArray() {
    return $this->tokenArray;
  }


  /**
   * Function: getEntry($field)
   *  Returns the internal token-data stored in given $field (key).
   *
   * Parameters:
   *  $field <String> - Which key of the internal token-data should be returned
   *
   * Return:
   *  <String> - Internal token-data for this key
   */
  public function getEntry($field) {
    // Only return valid fields/keys
    $field = strtolower($field);
    if (in_array($field, static::$fields))
      return $this->tokenArray[$field];
  }


  /**
   * Function: setEntry($field, $value)
   *  Update the internal token-data stored in given $field (key)
   *  with given $value.
   *
   * Parameters:
   *  $field <String> - Which key of the internal token-data should be updated
   *  $field <Mixed> - What should be stored as new token-data for given key
   */
  public function setEntry($field, $value) {
    // Only update valid fields/keys
    $field = strtolower($field);
    if (in_array($field, static::$fields))
      $this->tokenArray[$field] = $value;
  }
}
