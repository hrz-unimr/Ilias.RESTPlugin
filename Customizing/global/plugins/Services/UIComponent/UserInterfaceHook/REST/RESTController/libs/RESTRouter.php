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
   * Returns all registered routes.
   * Unlike getNamedRoutes() this also includes routes
   * without a name.
   *
   * @see getNamedRoutes()
   */
  public function getRoutes() {
    return new \ArrayIterator($this->routes);
  }
}
