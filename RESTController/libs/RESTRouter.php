<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 
 
/**
 * Extends the Slim-Framework Router with a method to iterate over
 * all routes even if they don't have a name.
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
