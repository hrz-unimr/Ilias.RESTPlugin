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

    // This will store the tokens/parameters once fetched
    $this->tokens     = array();
  }


  /**
   * Function: getFormat()
   *  Returns the internally useable format of the
   *  request object. Used to deduct the response
   *  format if not explicitly set/requested.
   */
  public function getFormat() {
    // Fetch content-type
    $format = $this->getContentType();

    // Return to format string-literal
    switch ($format) {
      case 'application/json':
      case 'text/json':
        return 'json';
      case 'application/xml':
      case 'text/xml':
        return 'xml';
    }
  }


  /**
   * Function: readParameters()
   *  Read parameters in the following order
   *   body (xml, json)
   *   body (form)
   *   get
   *   header
   *
   * Return:
   *  <Array> containing a list (union/merge) of all parameters
   */
  protected function readParameters() {
    // Fetch all parameters
    $body   = $this->getBody();
    $post   = $this->post();
    $get    = $this->get();
    $header = $this->getallheaders();

    // Build union with all parameters
    // (XML/JSON-Data, Form-Data, Get-Data, Header-Data in this order)
    $result = array();
    if (isset($body) && is_array($body))
      $result += $body;
    if (isset($post) && is_array($post))
      $result += $post;
    if (isset($get) && is_array($get))
      $result += $get;
    if (isset($header) && is_array($header))
      $result += $header;

      // Return result
    return $result;
  }


  /**
   * Function: getParameter($key, $default, $throw)
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
  public function getParameter($key = null, $default = null, $throw = false) {
    // Read parameter
    $parameters = $this->readParameters();

    // CHeck of key is requested
    if (isset($key)) {
      // Check if the requested key exists
      if (array_key_exists($key, $parameters))
        return $parameters[$key];

      // Throw exception when enabled
      elseif ($throw)
        throw new Exceptions\Parameter(
          self::MSG_MISSING,
          self::ID_MISSING,
          array(
            'key' => $key
          )
        );

      // Return the default value
      else
        return $default;
    }

    // Return complete list of parameters
    return $parameters;
  }


  /**
   * Function: hasParameter($key)
   *  Checks if the given parameter exists.
   *
   * Parameters:
   *  $key <String> - [Optional] Key of parameter that should be looked up.
   *                  Returns list of all GET & POST parameters if omited
   *
   * Return:
   *  True wether the given request-parameter exists.
   */
  public function hasParameter($key) {
    return array_key_exists($key, $this->readParameters());
  }


  /**
   * Function: getallheaders()
   *  This behaves more in line with RFC 2616 and returns all header
   *  fields as lower-case keys (since they should be treated as case-insensitive),
   *  keeping dashes (-) but ignoring any underscores (_) since
   *  most webservers drop headers with underscore (silently). (See Apache/Nginx)
   */
  protected function getallheaders() {
    // Try to uses php's fancy build in (case-insensitive) function...
    if (function_exists('getallheaders')) {
      $headers = getallheaders();
      return array_change_key_case($headers);
    }
    // Be a man and and fetch headers yourself!
    else {
      // Fetch headers from $_SERVER superglobal
      $headers = array();
      foreach ($_SERVER as $name => $value)
        if (substr($name, 0, 5) == 'HTTP_') {
          $key = substr($name, 5);
          $key = strtolower($key);
          $key = str_replace('_', '-', $key);
          $headers[$key] = $value;
        }

      // Return extracted headers
      return $headers;
    }
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

        // Fetch all parameters and convert keys to lower-case
        $headers    = $this->getallheaders();
        $parameters = array_change_key_case($this->getParameter());

        // Fetch token from 'authorization' field (but from headers first)
        if (array_key_exists('authorization', $headers)) {
          $tokenString = $headers['authorization'];
          if (is_string($tokenString) && stripos($tokenString, 'Bearer ') !== false)
            $tokenString = substr($tokenString, 7);
        }
        // Fetch token from 'access_token' field (from headers, get or body)
        elseif (array_key_exists('access_token', $parameters))
          $tokenString = $parameters['access_token'];
        // Fetch token from 'authorization' field (from headers, get or body)
        elseif (array_key_exists('authorization', $parameters)) {
          $tokenString = $parameters['authorization'];
          if (is_string($tokenString) && stripos($tokenString, 'Bearer ') !== false)
            $tokenString = substr($tokenString, 7);
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
        $tokenString = $this->getParameter('refresh_token');

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
        $tokenString = $this->getParameter('authorization_token');

        // Fetch 'token'  from header, GET or POST...
        if ($tokenString == null)
            $tokenString = $this->getParameter('code');

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
    $remoteIp  = $_SERVER['REMOTE_ADDR'];
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
