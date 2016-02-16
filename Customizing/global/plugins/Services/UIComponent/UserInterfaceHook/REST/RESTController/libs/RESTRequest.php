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
use \RESTController\libs\Exceptions  as Exceptions;
use \RESTController\core\auth\Tokens as Tokens;


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
  const MSG_NO_ACCESS_TOKEN = 'Could not find any access-token in Authorizaton-Header and header, GET or POST (JSON/x-www-form-urlencoded) parameters.';
  const ID_NO_ACCESS_TOKEN  = 'RESTController\\libs\\RESTRequest::ID_NO_ACCESS_TOKEN';
  const MSG_NO_TOKEN        = 'Could not find any {{token}} in header, GET or POST (JSON/x-www-form-urlencoded) parameters.';
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
  public function getToken($name = 'access', $stringOnly = false, $throw = true) {
    switch ($name) {
      // Fetch access-token
      default:
      case 'access':
        // Fetch 'access_token' from header, GET or POST...
        $tokenString = $this->params('access_token');

        // Fetch 'token'  from header, GET or POST...
        if ($tokenString == null)
            $tokenString = $this->params('token');

        // Fetch 'Authorization' from header ONLY!
        if ($tokenString == null) {
          $authHeader = $this->headers('Authorization');

          // Found Authorization header?
          if ($authHeader) {
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
          return Tokens\Access::fromMixed($settings, $tokenString);
        }

        // Not returned by now means no token was found
        if ($throw)
          throw new Exceptions\Parameter(
            self::MSG_NO_ACCESS_TOKEN,
            self::ID_NO_ACCESS_TOKEN
          );

      // Fetch refresh-token
      case 'refresh':
        // Fetch 'access_token' from header, GET or POST...
        $tokenString = $this->params('refresh_token');

        // Found something that could be an access-token?
        if ($tokenString != null) {
          // Only return token as string
          if ($stringOnly)
            return $tokenString;

          // Return token as object
          $settings = Tokens\Settings::load('refresh');
          return Tokens\Refresh::fromMixed($settings, $tokenString);
        }

        // Not returned by now means no token was found
        if ($throw)
          throw new Exceptions\Parameter(
            self::MSG_NO_TOKEN,
            self::ID_NO_TOKEN,
            array(
              'token' => 'refresh'
            )
          );

      // Fetch authorization-token
      case 'authorization':
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
          return Tokens\Authorization::fromMixed($settings, $tokenString);
        }

        // Not returned by now means no token was found
        if ($throw)
          throw new Exceptions\Parameter(
            self::MSG_NO_TOKEN,
            self::ID_NO_TOKEN,
            array(
              'token' => 'authorization'
            )
          );
    }
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
