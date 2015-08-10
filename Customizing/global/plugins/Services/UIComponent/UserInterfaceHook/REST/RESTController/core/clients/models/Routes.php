<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * This model handels all data that is required
 * by the Routes-Routes.
 *
 * Constructor requires nothing.
 */
class Routes extends Libs\RESTModel {
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
