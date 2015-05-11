<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\routes;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Lib;


/**
 * This model handels all data that is required
 * by the Routes-Routes.
 */
class Routes extends Lib\RESTModel {
    /**
     * Given a set of slim-routes, this method
     * parses into an array together with
     *  - URI
     *  - VERB
     *  - Auth-Middleware
     * for each route.
     */
    public function parseRoutes($routes) {
        // Build up response data
        $resultRoutes = array();
        foreach($routes as $route) {
            // Format/Get data
            $multiVerbs = $route->getHttpMethods();
            $verb = $multiVerbs[0];
            $middle = $route->getMiddleware();

            // Pack data
            $resultRoutes[] = array(
                'pattern' => $route->getPattern(),
                'verb' => $verb,
                'middleware' => (isset($middle[0]) ? $middle[0] : "none")
            );
        }

        return $resultRoutes;
    }


    /**
     * Generate URL to config-gui given the RESTControllers
     * app.php directory.
     */
    public function getConfigURL($appDir) {
        // Find plugin directory (REST)
        $pluginDir = dirname($appDir);

        // Find base directory (ILIAS)
        $baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $baseDir = ($baseDir == '/' ? '' : $baseDir);

        // Build full directory
        $adminDir = $baseDir . '/' . $pluginDir . '/apps/admin/';

        return $adminDir;
    }
}
