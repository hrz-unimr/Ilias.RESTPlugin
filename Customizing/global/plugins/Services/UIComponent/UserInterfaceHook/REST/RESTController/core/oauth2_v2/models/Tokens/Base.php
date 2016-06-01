<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2\Tokens;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;
use \RESTController\core\oauth2_v2\Exceptions as Exceptions;


/**
 * Class: Base (-Token)
 *  (Convieved) Abstract class for common access- and refresh-token code.
 */
class Base {
  // Allow to re-use status messages and codes
  const MSG_EXPIRED         = 'Token has expired (Type: {{type}}).';
  const ID_EXPIRED          = 'RESTController\\core\\auth\\Base::ID_EXPIRED';
  const MSG_INVALID_SIZE    = 'Token contains invalid number of fields. (Required: {{required}} / Given: {{given}})';
  const ID_INVALID_SIZE     = 'RESTController\\core\\auth\\Base::ID_INVALID_SIZE';
  const MSG_INVALID         = 'Token is invalid.';
  const ID_INVALID          = 'RESTController\\core\\auth\\Base::ID_INVALID';
  const MSG_UNKNOWN_CLASS   = 'Cannot detect type of token with class: {{class}}';
  const ID_UNKNOWN_CLASS    = 'RESTController\\core\\auth\\Base::ID_UNKNOWN_CLASS';


  // Stores the settings attached to this token (salt and default TTL)
  protected $tokenSettings;

  // Stored the actuall token-data (as array)
  protected $tokenArray;

  // List of fields (keys) for this kind of token
  protected static $fields = array(
    'user_id',        // Attached ILIAS User-Id
    'ilias_client',   // Attached ILIAS Client-Id
    'api_key',        // Oauth2 Client-Id
    'class',          // Internal type of token (Authorization-Code, Access-Token, Refresh-Token, etc.)
    'scope',          // Scope that is attached to the token
    'ttl',            // Time-To-Live (aka expiration time) of token
    'misc',           // Additional misc data
    's',              // Random-String for (re-)hashing security
    'h'               // Hash of full token, used as checksum / security-messure
  );
  protected static $class   = 'base';  // Will be used to validate type of token
  protected static $entropy = 25;         // 'Pseudo-Entropy' (aka size) of random token content

  // Store username in addition to user-id (only looked-up once)
  protected $username = null;


  /**
   * Constructor:
   *  Creates a new 'base' token.
   *
   * Parameters:
   *  $tokenSettings <Settings> - Internal settings of this token
   */
  protected function __construct($tokenSettings) {
    $this->tokenSettings  = $tokenSettings;
    $this->client         = null;
  }


  /**
   * Function: fromMixed($tokenSettings, $tokenArray)
   *  Generates a Base-Token from given input parameters.
   *  Expects settings-object and token-data as array.
   *
   * Parameters:
   *  $tokenSettings <Settings> - Internal settings of this token
   *  $tokenArray <Array[Mixed]> - Array of string (key & value) elements representing a valid token
   *
   * Return:
   *  <BaseToken> - Generated Base-Token
   */
  public static function fromMixed($tokenSettings, $tokenArray) {
    // Generate new token from token-data as array
    $baseToken = new static($tokenSettings);
    $baseToken->setToken($tokenArray);

    // Return new object
    return $baseToken;
  }


  /**
   * Function: fromFields($tokenSettings, $user_id, $ilias_client, $api_key, $type, $misc, $lifetime)
   *  Generates a Base-Token from given input parameters.
   *  Expects settings-object and token-data as additional parameters.
   *
   * Parameters:
   *  $tokenSettings <Settings> - Internal settings of this token
   *  $user_id <String> - User-Id that should be attached to the token
   *  $ilias_client <String> - ILIAS Client-Id that should be attached to the token
   *  $api_key <String> - API-Key that should be attached to the token
   *  $scope <String> - Scope that should be attached to token
   *  $misc <String> - Misc data that should be attached to token
   *  $lifetime <Integer> - Lifetime that should be attached to token (get invalid after expiration)
   *
   * Return:
   *  <BaseToken> - Generated Base-Token
   */
  public static function fromFields($tokenSettings, $user_id, $ilias_client = null, $api_key, $scope = null, $misc = null, $lifetime = null) {
    // Generate new token from token-data as parameters
    $baseToken  = new static($tokenSettings);
    $tokenArray = $baseToken->generateTokenArray($user_id, $ilias_client, $api_key, $scope, $misc, $lifetime);
    $baseToken->setToken($tokenArray);

    // Return new object
    return $baseToken;
  }


  /**
   * Function: factory($tokenArray)
   *  Factory method to create the correct token (Authorization,Access, Refresh)
   *  from the given token-array using the token-arrays class value as type indication.
   *
   * Parameters:
   *  $tokenArray <Array[String]> - Array which will be used to generate a new token object
   *
   * Returns:
   *  <AccessToken>/<RefreshToken>/<AuthorizationToken> - Token generated from the given token-array
   */
  public static function factory($tokenArray) {
    // Extract token array
    if (!is_array($tokenArray))
      $tokenArray = \RESTController\core\oauth2_v2\Tokens\Base::deserializeToken($tokenArray);

    // Detect type of token...
    switch($tokenArray['class']) {
      // Create new rfresh-token
      case 'refresh':
        $settings = \RESTController\core\oauth2_v2\Tokens\Settings::load('refresh');
        return \RESTController\core\oauth2_v2\Tokens\Refresh::fromMixed($settings, $tokenArray);

      // Create new access-token
      case 'access':
        $settings = \RESTController\core\oauth2_v2\Tokens\Settings::load('access');
        return \RESTController\core\oauth2_v2\Tokens\Access::fromMixed($settings, $tokenArray);


      // Create new access-token
      case 'authorization':
        $settings = \RESTController\core\oauth2_v2\Tokens\Settings::load('authorization');
        return \RESTController\core\oauth2_v2\Tokens\Authorization::fromMixed($settings, $tokenArray);

      // Fallback
      default:
        throw new Exceptions\TokenInvalid(
          self::MSG_UNKNOWN_CLASS,
          self::ID_UNKNOWN_CLASS,
          array(
            'class' => $tokenArray['class']
          )
        );
    }
  }


  /**
   * Function: setToken($tokenMixed)
   *  Updates internal token-array with data from token
   *  represented by $tokenMix.
   *
   * Parameters:
   *  $tokenMixed <String>/<Array[Mixed]> - Valid token-string or token-array
   */
  public function setToken($tokenMixed) {
    // Convert input to array data
    if (is_string($tokenMixed)) $tokenArray = self::deserializeToken($tokenMixed);
    else                        $tokenArray = $tokenMixed;

    // Check validity of input
    if (!$this->isValidTokenArray($tokenArray))
      throw new Exceptions\TokenInvalid(self::MSG_INVALID, self::ID_INVALID);

    // Update token
    $this->tokenArray = $tokenArray;
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
   * Function: getTokenString()
   *  Returns this token in string format.
   *
   * Return:
   *  <String> - String representing this token
   */
  public function getTokenString() {
    return self::serializeToken($this->tokenArray);
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
   *  with given $value. Additonally makes sure the token stays valid
   *  by updating its hash.
   *
   * Parameters:
   *  $field <String> - Which key of the internal token-data should be updated
   *  $field <Mixed> - What should be stored as new token-data for given key
   */
  public function setEntry($field, $value) {
    // Chaching hash-value is not allowed
    if (strtolower($field) != 'h') {
      // Only update valid fields/keys
      $field = strtolower($field);
      if (in_array($field, static::$fields)) {
        // Update entry...
        $this->tokenArray[$field] = $value;

        // Update token hash
        $this->tokenArray['h'] = $this->generateHash($this->tokenArray);
      }
    }

    // Reset cached username!
    if ($field == 'user_id')
      $this->username = null;
  }


  /**
   * Getter-Functions:
   *  getUserId() - Return stored user-id
   *  getUserName() - Returns username attached to stored user-id
   *  getIliasClient() - Return stored ilias client-id
   *  getApiKey() - Return stored api-key
   *  getScope() - Returns scope attached to this token
   *  getClass() - Returns the interal class of this token
   */
  public function getUserId() {
    return $this->tokenArray['user_id'];
  }
  public function getUserName() {
    // Fetch username once
    if (!$this->username)
      $this->username = Libs\RESTilias::getUserName($this->tokenArray['user_id']);

    // Afterwars simply return stored value
    return $this->username;
  }
  public function getIliasClient() {
    return $this->tokenArray['ilias_client'];
  }
  public function getApiKey() {
    return $this->tokenArray['api_key'];
  }
  public function getScope() {
    return $this->tokenArray['scope'];
  }
  public function getMisc() {
    return $this->tokenArray['misc'];
  }
  public function getClass() {
    return static::$class;
  }


  /**
   * Function: getClient()
   *  Returns the attached RESTclient database table object and stores
   *  it for potential future reference.
   *
   * Return:
   *  <RESTclient> - The oauth2 client which is attached to this tokens api-key
   */
  public function getClient() {
    // fetch current client object
    if (is_null($this->client))
      $this->client = Database\RESTclient::fromApiKey($this->tokenArray['api_key']);

    // Return current client object
    return $this->client;
  }


  /**
   * Function: hasScope($scope)
   *  Return true if this tokens scope contains all scopes given by the input parameter.
   *  Note: The allowed scope of the token is either a list of space-delimited strings or
   *        a regex who gets matched against the requested scope.
   *
   * Parameters:
   *  $scope <String/Array[String]> - List of scopes to check (ALL need be be contained)
   *
   * Return:
   *  <Boolean> - True if ALL scopes in $scope are allowed by the tokens scope
   */
  public function hasScope($requested) {
    // Fetch list/regex of allowed scope
    $scope = $this->tokenArray['scope'];

    // Delegate to restriction-check
    return RESTLib::CheckComplexRestriction($scope, $requested, ' ');
  }


  /**
   * Function: isValid()
   *  Checks wether token data contained in this token (and thus the token itsself) is
   *  valid, by checking wether the hash (which can only be generated using the tokens
   *  internal salt) corresponds to the calcluated hash of the internal token-array.
   * Note: An expired token may still be valid, or the other way around
   *       a valid token might still be expired.
   *
   * Return:
   *  <Boolean> - True if token seems to be valid, false otherwise
   */
  public function isValid() {
    return $this->isValidTokenArray($this->tokenArray);
  }


  /**
   * Function: isExpired()
   *  Checks wether the token is expired given the internal ttl-date
   *  AND if the token is actually valid (first).
   *
   * Return:
   *  <Boolean> - True if token is valid and not expired yet
   */
  public function isExpired() {
    return !($this->isValid() && intval($this->tokenArray['ttl']) > time());
  }


  /**
   * Function: getRemainingTime()
   *  Returns the remaining time (in seconds) until this token
   *  will be retired.
   *
   * Return:
   *  <Integer> - Remaining time in seconds until tthis oken expires
   */
  public function getRemainingTime() {
    return (!$this->isExpired()) ? intval($this->tokenArray['ttl']) - time() : 0;
  }


  /**
   * Function: refresh($ttl)
   *  Resets the TTL of this token to either the given duration
   *  or the tokens default TTL.
   *  This is different from setEntry('ttl', $ttl) in that it also
   *  regenrates the tokens random-string ('s'-field) component.
   *
   * Parameters:
   *  $ttl <Integer> - Remaining duration this token should have.
   */
  public function refresh($ttl = null) {
    // Only valid tokens can be refreshed
    if (!$this->isValid())
      return null;

    // Extract original data
    $user_id      = $this->tokenArray['user_id'];
    $ilias_client = $this->tokenArray['ilias_client'];
    $api_key      = $this->tokenArray['api_key'];
    $scope        = $this->tokenArray['scope'];
    $misc         = $this->tokenArray['misc'];

    // Update $this from given data, but with reset ttl (null -> default-ttl)
    $token = $this->generateTokenArray($user_id, $ilias_client, $api_key, $scope, $misc, $ttl);
    $this->setToken($token);
  }


  /**
   * Function: generateTokenArray($user_id, $ilias_client, $api_key, $type, $misc, $lifetime)
   *  Generates a token-array for the given input parameters, for internal use only.
   *
   * Parameters:
   *  $user_id <String> - User-Id that should be attached to the token
   *  $ilias_client <String> - ILIAS Client-Id that should be attached to the token
   *  $api_key <String> - API-Key that should be attached to the token
   *  $type <String> - Type that should be attached to token
   *  $scope <String> - Scope that should be attached to token
   *  $misc <String> - Misc data that should be attached to token
   *  $lifetime <Integer> - Lifetime that should be attached to token (get invalid after expiration)
   *
   * Return:
   *  <Array[Mixed]> - Generated token-array (Eg. for internal storage)
   */
  protected function generateTokenArray($user_id, $ilias_client = null, $api_key, $scope = null, $misc = null, $lifetime = null) {
    // Apply default values
    if ($ilias_client == null) $ilias_client  = CLIENT_ID;
    if ($scope        == null) $scope         = '';
    if ($misc         == null) $misc          = '';
    if ($lifetime     == null) $lifetime      = $this->tokenSettings->getTTL();

    // Generate random string to make re-hashing token "difficult"
    $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, static::$entropy);

    // Generate token-array
    $tokenArray = array(
      'user_id'       => $user_id,
      'ilias_client'  => $ilias_client,
      'api_key'       => $api_key,
      'class'         => static::$class,
      'scope'         => $scope,
      'misc'          => $misc,
      'ttl'           => strval(time() + ($lifetime * 60)),
      's'             => $randomStr
    );

    // Generate hash for token
    $tokenArray['h'] = $this->generateHash($tokenArray);

    // Return created token-array
    return $tokenArray;
  }


  /**
   * Function: getUniqueHash()
   *  Calculates a simple md5 hash of all customizable keys
   *  of the underlying token. Usefull to compare tokens
   *  via one string value. (eg. Database lookup)
   *
   * Return:
   *  <String> - MD5 hash of userId, iliasClient, apiKey, scope and misc data of this token
   */
  public function getUniqueHash() {
    // Concat all 'custom-keys' to string
    $hashStr = sprintf(
      '%s-%s-%s-%s-%s',
      $this->tokenArray['user_id'],
      $this->tokenArray['ilias_client'],
      $this->tokenArray['api_key'],
      $this->tokenArray['scope'],
      $this->tokenArray['misc']
    );

    // Generate md5 hash used to uniquely identify this token
    return md5($hashStr);
  }



  /**
   * Function: generateHash($tokenArray)
   *  Generates a unique, non-reverseable hash that can only
   *  be generated knowing the secret salt.
   *
   * Parameters:
   *  $tokenArray <Array[Mixed]> - Token-array for which to generate hash
   *
   * Return:
   *  <String> - Hash generated for this token
   */
  protected function generateHash($tokenArray) {
    // Concat all token-array keys to string
    $hashStr = sprintf(
      '%s-%s-%s-%s-%s-%s-%s-%s-%s',
      $this->tokenSettings->getSalt(),
      $tokenArray['user_id'],
      $tokenArray['ilias_client'],
      $tokenArray['api_key'],
      $tokenArray['class'],
      $tokenArray['scope'],
      $tokenArray['misc'],
      $tokenArray['ttl'],
      $tokenArray['s']
    );

    // Add additional salt and generate non-invertable hash
    return hash('sha256', $hashStr);
  }


  /**
   * Function: isValidTokenArray($tokenArray)
   *  Utility-Function used to check correctness of data in token-array.
   *  It compares the hash stored inside token-array with the hash generated
   *  from token-array.
   *
   * Parameters:
   *  $tokenArray <Array[Mixed]> - Token-array which should be checked
   *
   * Return:
   *  <Boolean> - True if token is valid (or at least consistent)
   */
  protected function isValidTokenArray($tokenArray) {
    // Check internal-class, number of fields and has hash (which also means, contains all fields)!
    return (
      $tokenArray['class']  == static::$class       &&
      count($tokenArray)    == count(self::$fields) &&
      $tokenArray["h"]      == $this->generateHash($tokenArray)
    );
  }


  /**
   * Function: serializeToken($tokenArray)
   *  Converts the token-array into a token-string.
   *  Both represent the same token object, but have different use-cases.
   *   array - internal storage
   *   string - data transmission
   *
   * Parameters:
   *  $tokenArray <Array[Mixed]> - Token-array which should be converted to string
   *
   * Return:
   *  <String> - Converted token-array
   */
  public static function serializeToken($tokenArray) {
    // Concat all token-array keys to string
    $tokenStr = sprintf(
      '%s,%s,%s,%s,%s,%s,%s,%s,%s',
      str_replace(',', '', $tokenArray['user_id']),
      str_replace(',', '', $tokenArray['ilias_client']),
      str_replace(',', '', $tokenArray['api_key']),
      str_replace(',', '', $tokenArray['class']),
      str_replace(',', '', $tokenArray['scope']),
      str_replace(',', '', $tokenArray['misc']),
      str_replace(',', '', $tokenArray['ttl']),
      str_replace(',', '', $tokenArray['s']),
      str_replace(',', '', $tokenArray['h'])
    );

    // Return serialized token-array
    return urlencode(base64_encode($tokenStr));
  }


  /**
   * Function: deserializeToken($tokenString)
   *  Converts the token-string into a token-array.
   *  Both represent the same token object, but have different use-cases.
   *   array - internal storage
   *   string - data transmission
   *
   * Parameters:
   *  $tokenString <String> - Token-string that should be converted to an array
   *
   * Return:
   *  <Array[Mixed]> - Converted token-string
   */
  public static function deserializeToken($tokenString) {
    // Deserialize token-string
    $tokenPartArray = explode(',', base64_decode(urldecode($tokenString)));

    // Reconstruct token-array from exploded string
    if (count($tokenPartArray) == count(self::$fields)) {
      return array(
        'user_id'       =>  $tokenPartArray[0],
        'ilias_client'  =>  $tokenPartArray[1],
        'api_key'       =>  $tokenPartArray[2],
        'class'         =>  $tokenPartArray[3],
        'scope'         =>  $tokenPartArray[4],
        'misc'          =>  $tokenPartArray[5],
        'ttl'           =>  $tokenPartArray[6],
        's'             =>  $tokenPartArray[7],
        'h'             =>  $tokenPartArray[8]
      );
    }

    // Not good...
    else
      throw new Exceptions\TokenInvalid(
        self::MSG_INVALID_SIZE,
        self::ID_INVALID_SIZE,
        array(
          'given'     => count($tokenPartArray),
          'required'  => count(self::$fields)
        )
      );
  }
}
