<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// Requires ../Slim/Router.php


/**
 * Class: RESTRouter
 *  Extends the Slim-Framework Router with a method to iterate over
 *  all routes, even if they don't have a name.
 */
class RESTRouter extends \Slim\Router {
  /**
   * Constructor
   * @param array $settings
   */
  public function __construct() {
    // Call parent constructor
    parent::__construct();

    // Supported formats
    $this->formats = array('json', 'xml');
  }


  /**
   * Returns all registered routes.
   * Unlike getNamedRoutes() this also includes routes
   * without a name.
   *
   * @see getNamedRoutes()
   */
  public function getRoutes() {
    return new \ArrayIterator($this->routes);
  }


  /**
   * Return route objects that match the given HTTP method and URI
   * @param  string               $httpMethod   The HTTP method to match against
   * @param  string               $resourceUri  The resource URI to match against
   * @param  bool                 $reload       Should matching routes be re-parsed?
   * @return array[\Slim\Route]
   */
  public function getMatchedRoutes($httpMethod, $resourceUri, $reload = false) {
    # Fetch default matched routes
    $routes = parent::getMatchedRoutes($httpMethod, $resourceUri, $reload);
    if (count($routes) > 0)
      return $routes;

    # Try to return matched routes without ".<format>" postfix
    foreach ($this->formats as $id => $format) {
      # Prepare postfix check
      $ending       = sprintf('.%s', $format);
      $length       = strlen($ending);
      $postfix      = substr($resourceUri,   -$length);
      $strippedUri  = substr($resourceUri, 0, -$length);

      # Check if format-request postfix exists
      if (strcmp($postfix, $ending) === 0) {
        $routes = parent::getMatchedRoutes($httpMethod, $strippedUri, true);
        if (count($routes) > 0)
          return $routes;
      }
    }

    # Return empty list if no route was found
    return array();
  }


  /**
   * Try to extract requested output-format
   * from ending of the current route.
   */
  public function getResponseFormat($resourceUri) {
    // Early exit
    if (!isset($resourceUri))
      return null;

    # Try to return matched routes without ".<format>" postfix
    foreach ($this->formats as $id => $format) {
      # Prepare to extract postfix
      $length = strlen($format);
      $ending = sprintf('.%s', $format);

      # Check if postfix matches a format
      if (strlen($resourceUri) > $length + 1 && strcmp(substr($resourceUri, -($length + 1)), $ending) === 0)
        return $format;
    }
  }
}
