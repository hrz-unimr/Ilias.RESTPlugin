<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 
 
/**
 * Router
 */
class RESTRouter extends \Slim\Router {
    public function getRoutes() {
        return new \ArrayIterator($this->routes);
    }
}
