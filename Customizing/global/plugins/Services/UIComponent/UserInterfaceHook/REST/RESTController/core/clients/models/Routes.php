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
 * This model handles all data that is required
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
     * This method combines information from $clientModel->getPermissionsForApiKey
     * and from the SLIM router $app->router()->getRoutes().
     *
     * @param $apiRoutes
     * @param $allRoutes
     * @param $isAdmin
     * @return mixed
     */
    public function filterApiRoutes($apiRoutes, $allRoutes, $isAdmin)
    {
        $resultRoutes = array();
        $apiPatterns = array();
        foreach ($apiRoutes as $entry) {
            $apiPatterns[] = $entry['pattern'];
        }
        foreach($allRoutes as $route) {

            //$resultRoutes[] = array('debug'=>$apiPatterns);
            $inApiRoute = in_array ( $route->getPattern() , $apiPatterns);
            if ($inApiRoute == true) {
                // Format/Get data
                $multiVerbs = $route->getHttpMethods();
                $verb = $multiVerbs[0];
                $middle = $route->getMiddleware();

                $auth_type = "none";
                $access_level = "none";
                $has_access = true;
                if (isset($middle[0]) == true) {
                    $parts1 = explode('\\', $middle[0]);
                    $parts2 = explode('::', $parts1[3]);
                    if ($parts2[0] == 'OAuth2Middleware') {
                        $auth_type = 'OAuth2';
                    }
                    $access_level = $parts2[1];
                    if ($access_level == "TokenAdminAuth") {
                        if ($isAdmin == false) {
                            $has_access = false;
                        }
                    }
                }

                // Pack data
                $resultRoutes[] = array(
                    'pattern' => $route->getPattern(),
                    'verb' => $verb,
                    'auth_type' => $auth_type,
                    'access_level' => $access_level,
                    'has_access' => $has_access
                    //'middleware' => (isset($middle[0]) ? $middle[0] : "none")
                );
            }
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
