<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// This allows us to use shortcuts instead of full quantifier
// Requires ../Slim/Http/Request
use \RESTController\core\oauth2_v2 as Auth;
use \RESTController\core\oauth2_v2\Tokens as Tokens;


/**
 * Class: RESTRequest
 *  Special implementation of Slim-Rquest to
 *  support params via HEADER as well as JSON
 *  inside the request BODY.
 */
class RESTRequest extends \Slim\Http\Request {
  // Allow to re-use status messages and codes
  const MSG_MISSING         = 'Mandatory parameter missing, \'{{key}}\' not set in header, GET or POST (JSON/x-www-form-urlencoded) parameters.';
  const ID_MISSING          = 'RESTController\\libs\\RESTRequest::ID_MISSING';
  const MSG_PARSE_ISSUE     = 'Could not parse ids \'{{ids}} from \'{{string}}\'.';
  const ID_PARSE_ISSUE      = 'RESTController\\libs\\RESTRequest::ID_PARSE_ISSUE';
  const MSG_NO_TOKEN        = 'Could not find any {{type}} in header, GET or POST (JSON/x-www-form-urlencoded) parameters.';
  const ID_NO_TOKEN         = 'RESTController\\libs\\RESTRequest::ID_NO_TOKEN';

  /**
   * Constructor: RESTRequest($env)
   *  Create a new (singleton) instance of the
   *  special REST implementation for Slim
   *  request parsing.
   *
   * Parameters:
   *  @See \Slim\Http\Request::__construct(...)
   */
  public function __construct(\Slim\Environment $env) {
    // Call parent constrcutor
    parent::__construct($env);

    // Store all available headers
    // For some reason slim fails to do this...
    foreach (getallheaders() as $key => $value)
      $this->headers->set($key, $value);

    // This will store the tokens once fetched
    $this->tokens = array();
  }


  /**
   * Function: params($key, $default, $throw)
   *  Fetches a request parameter from different locations.
   *  In the following order:
   *   - Look inside header
   *   - Look inside HTTP-GET
   *   - Look inside HTTP-POST (url-encoded values only)
   *   - Look inside HTTP-POST (json encoded values only)
   *  If no value for given key is found either the
   *  given default value is return or an exception thrown.
   *
   * Parameters:
   *  $key <String> - [Optional] Key of parameter that should be looked up.
   *                  Returns list of all GET & POST parameters if omited
   *  $default <String> - [Optional] Default value that should be used if non was value
   *                      was found for the given key.
   *  $throw <Boolean> - [Optional] If no value was found for the key, throw an exception
   *                     rather than returning the default value.
   *
   * Return:
   *  <String> - Value attached to the given key (looking inside header, get, url-encoded post, json-encoded post)
   */
  public function params($key = null, $default = null, $throw = false) {
    // Return key-value from header?
    if (isset($key)) {
      $header = $this->headers($key, null);
      if ($header != null)
        return $header;

      $header = $this->headers(ucfirst($key), null);
      if ($header != null)
        return $header;
    }

    // Return key-value from url-encoded POST or GET
    $param = parent::params($key, null);
    if ($param != null)
      return $param;

    // Return key-value from JSON POST
    if (isset($key)) {
      $body = $this->getBody();
      if (is_array($body) && isset($body[$key]))
        return $body[$key];
    }

    // Return default value or throw exception?
    if (!$throw)
      return $default;

    // Throw exception because its enabled
    throw new Exceptions\Parameter(
      self::MSG_MISSING,
      self::ID_MISSING,
      array(
        'key' => $key
      )
    );
  }

  public function hasParam($key) {
    // Return key-value from header?
    if (isset($key)) {
      $header = $this->headers($key, null);
      if ($header != null)
        return true;
    }

    // Return key-value from url-encoded POST or GET
    $param = parent::params($key, null);
    if ($param != null)
      return true;

    // Return key-value from JSON POST
    if (isset($key)) {
      $body = $this->getBody();
      if (is_array($body) && isset($body[$key]))
        return true;
    }

    // Not found
    return false;
  }


  /**
   * Function: getToken($name)
   *  Utility method to fetch Token-Object from given input parameter.
   *  Supported are access-token, refresh-token and authorization-code (token).
   *
   * Parameter:
   *  $name <String> - [Optional] Which type of token should be fetched (Default: Access-Token)
   *                   Supports: null, 'access', 'refresh', 'authorization'
   *  $stringOnly <Boolean> - [Optional] Pass true to return token as string without converting to
   *                          Token object (usefull if for example the DB isn't available yet)
   *
   * Return:
   *  <AccessToken>/<RefreshToken>/<AuthorizationCode> - Fetched token, depending on input parameter
   */
  public function getToken($name = 'access', $stringOnly = false) {
    // Already fetched?
    if (isset($this->tokens[$name]))
      return $this->tokens[$name];

    switch ($name) {
      // Fetch access-token
      default:
      case 'access':
        $type = 'Access-Token';

        // Fetch 'access_token' from header, GET or POST...
        $tokenString = $this->params('access_token');

        // Fetch 'token'  from header, GET or POST...
        if ($tokenString == null)
            $tokenString = $this->params('token');

        // Fetch 'Authorization' from header ONLY!
        if ($tokenString == null) {
          $authHeader = $this->headers('Authorization');

          // Found Authorization header?
          if (is_string($authHeader)) {
              $authArray = explode(' ', $authHeader);

              // Look for bearer-type token
              if (strtolower($authArray[0]) == 'bearer')
                $tokenString = $authArray[1];
          }
        }

        // Found something that could be an access-token?
        if ($tokenString != null) {
          // Only return token as string
          if ($stringOnly)
            return $tokenString;

          // Return token as object
          $settings = Tokens\Settings::load('access');
          $token    = Tokens\Access::fromMixed($settings, $tokenString);

          // Check and return token
          self::checkToken($token, $type);
          $this->tokens['access'] = $token;
          return $token;
        }

        // No token found!
        throw new Exceptions\Parameter(
          self::MSG_NO_TOKEN,
          self::ID_NO_TOKEN,
          array(
            'type' => $type
          )
        );
      break;

      // Fetch refresh-token
      case 'refresh':
        $type = 'Refresh-Token';

        // Fetch 'access_token' from header, GET or POST...
        $tokenString = $this->params('refresh_token');

        // Found something that could be an access-token?
        if ($tokenString != null) {
          // Only return token as string
          if ($stringOnly)
            return $tokenString;

          // Return token as object
          $settings = Tokens\Settings::load('refresh');
          $token    = Tokens\Refresh::fromMixed($settings, $tokenString);

          // Check and return token
          self::checkToken($token, $type);
          $this->tokens['refresh'] = $token;
          return $token;
        }
      break;

      // Fetch authorization-token
      case 'authorization':
        $type = 'Authorization-Code-Token';

        // Fetch 'access_token' from header, GET or POST...
        $tokenString = $this->params('authorization_token');

        // Fetch 'token'  from header, GET or POST...
        if ($tokenString == null)
            $tokenString = $this->params('code');

        // Found something that could be an access-token?
        if ($tokenString != null) {
          // Only return token as string
          if ($stringOnly)
            return $tokenString;

          // Return token as object
          $settings = Tokens\Settings::load('authorization');
          $token    = Tokens\Authorization::fromMixed($settings, $tokenString);

          // Check and return token
          self::checkToken($token, $type);
          $this->tokens['authorization'] = $token;
          return $token;
        }
    }

    // No token found!
    throw new Exceptions\Parameter(
      self::MSG_NO_TOKEN,
      self::ID_NO_TOKEN,
      array(
        'type' => $type
      )
    );
  }


  /**
   * Function: checkToken($token, $type)
   *  Running some common check on the given token, such that
   *  if one was found, if it is valid and not expired, as
   *  well as userId and IP restrtions. This should make using
   *  a token quite safe.
   *  Throws exceptions when something isn't right...
   *
   * Parameters:
   *  $token <BaseToken> - Token object that should be verified
   *  $type <String> - Type of token, mostly used for readable exceptions (Used inside description)
   */
  public static function checkToken($token, $type) {
    // Token must be found
    if (!isset($token))
      throw new Exceptions\Parameter(
        self::MSG_NO_TOKEN,
        self::ID_NO_TOKEN,
        array(
          'type' => $type
        )
      );

    // Token must be valid
    if (!$token->isValid())
      throw new Auth\Exceptions\TokenInvalid(
        Tokens\Base::MSG_INVALID,
        Tokens\Base::ID_INVALID,
        array(
          'type' => $type
        )
      );

    // Token must not be expired
    if ($token->isExpired())
      throw new Auth\Exceptions\TokenInvalid(
        Tokens\Base::MSG_EXPIRED,
        Tokens\Base::ID_EXPIRED,
        array(
          'type' => $type
        )
      );

    // Fetch client (This throws if client does not exist!)
    $apiKey = $token->getApiKey();
    $client = $token->getClient();

    // Fetch client ip and check restriction
    $remoteIp = RESTLib::FetchUserAgentIP();
    if (!$client->isIpAllowed($remoteIp))
      throw new Exceptions\Denied(
        Auth\Common::MSG_RESTRICTED_IP,
        Auth\Common::ID_RESTRICTED_IP,
        array(
          'ip' => $remoteIp
        )
      );

    // Fetch userId and check restriction
    $userId   = $token->getUserId();
    $username = $token->getUserName();
    if (!$client->isUserAllowed($userId))
      throw new Exceptions\Denied(
        Auth\Common::MSG_RESTRICTED_USER,
        Auth\Common::ID_RESTRICTED_USER,
        array(
          'userID'    => $userId,
          'username'  => $username
        )
      );
  }


  /**
   * Function: parseIDList($idString, $throwException)
   *  Parse a string of coma-separated numeric values (ids) into an array of integer values.
   *
   * Parameters:
   *  $idString <String> - String that should be parsed.
   *  $throwException <Boolean> - Throw exception of string does not contain parseable numeric values
   *
   * Return:
   *  <Array[Integer]> - Array of parsed values (integer)
   */
  public static function parseIDList($idString, $throwException = false) {
    // Parse string-list of coma-separated id-values
    $ids = explode(',', $idString);

    // Convert each id to an integer-value (if possible)
    $throws = array();
    foreach($ids as $key => $id) {
      // Can be converted to int?
      if (ctype_digit($id))
        $ids[$key] = intval($id);

      // Drop value (and throw exception)
      else {
        if ($throwException)
          $throws[$key] = $id;
        $ids[$key] = null;
      }
    }

    // Filter null-value from list converted values
    $ids = array_filter($ids, function($value) { return !is_null($value); });

    // Throw an exception?
    if (count($throws) > 0)
      throw new Exceptions\StringList(
        self::MSG_PARSE_ISSUE,
        self::ID_PARSE_ISSUE,
        array(
          'string'  => $idString,
          'ids'     => implode(', ', $throws)
        )
      );

    // Return ids (array of integers)
    return $ids;
  }
}
