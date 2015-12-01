<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\Exceptions as Exceptions;


/**
 * Class: RESTRequest
 *  Special implementation of Slim-Rquest to
 *  support params via HEADER as well as JSON
 *  inside the request BODY.
 */
class RESTRequest extends \Slim\Http\Request {
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
    throw new Exceptions\MissingParameter('Mandatory data is missing, parameter \'%paramName%\' not set.', $key);
  }
}
