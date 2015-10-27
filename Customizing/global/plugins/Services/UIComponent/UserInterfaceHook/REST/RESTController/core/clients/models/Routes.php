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
     * @param $routes: routes field as acquired by app->router()->getRoutes()
     * @param $includeAllRoutes: display all routes or only those that are protected by an auth middleware
     * @return array
     */
    public static function parseRoutes($routes, $includeAllRoutes) {
        // Build up response data
        $resultRoutes = array();
        foreach($routes as $route) {
            // Format/Get data
            $multiVerbs = $route->getHttpMethods();
            $verb = $multiVerbs[0];
            $middle = $route->getMiddleware();

            $includeEntry = false;
            if ($includeAllRoutes==true) {
                $includeEntry = true;
            } else {
                if (isset($middle[0]) == true ) {
                    if (strpos($middle[0],'OAuth2::PERMISSION') || strpos($middle[0],'ILIAS::ADMIN')) {
                        $includeEntry = true;
                    }
                }
            }

            if ($includeEntry == true) {
                $resultRoutes[] = array(
                    'pattern' => $route->getPattern(),
                    'verb' => $verb,
                    'middleware' => (isset($middle[0]) ? $middle[0] : "none")
                );
            }
        }

        return $resultRoutes;
    }

    /**
     * This method combines information from $clientModel->getPermissionsForApiKey
     * and from the SLIM router $app->router()->getRoutes(),
     * i.e. auth_type (e.g. OAuth2) and access_level
     *
     * @param $apiRoutes
     * @param $allRoutes
     * @param $includeUnrestrictedRoutes
     * @return mixed
     */
    public static function filterApiRoutes($apiRoutes, $allRoutes, $includeUnrestrictedRoutes)
    {
        $resultRoutes = array();
        /*$apiPatterns = array();
        foreach ($apiRoutes as $entry) {
            $apiPatterns[] = $entry['pattern'];
        }*/
        foreach($allRoutes as $route) {
            $ct_pattern = $route->getPattern();
            $tmp = $route->getHttpMethods();
            $ct_verb = $tmp[0];

            $middle = $route->getMiddleware();
            if (isset($middle[0]) == true) {
                $parts1 = explode('\\', $middle[0]);
                $parts2 = explode('::', $parts1[3]);
                if ($parts2[0] == 'OAuth2Middleware') {
                    $auth_type = 'OAuth2';
                }
                $access_level = $parts2[1];
                if ($access_level == "TokenAuth") {
                }
            } else {
                // open route found: no restrictions at all
                $auth_type = "";
                $access_level = ""; // "Open"
            }

            $includeCurrentRoute = false;

            $search_result = self::int_assoc_search($apiRoutes, 'pattern', $ct_pattern, 'verb', $ct_verb);
            if (count($search_result) > 0) {
                $includeCurrentRoute = true;
            }
            if ($includeUnrestrictedRoutes == true) {
                if ($access_level == 'TokenAuth' || $access_level== '') {
                    $includeCurrentRoute = true;
                }
            }

            if ($includeCurrentRoute == true) {
                // Pack data
                $resultRoutes[] = array(
                    'pattern' => $route->getPattern(),
                    'verb' => $ct_verb,
                    'auth_type' => $auth_type,
                    'access_level' => $access_level,
                );
            }

        }

        return $resultRoutes;
    }

    /**
     * Searches an associative array for an entry that matches two criteria.
     * @param $array
     * @param $key1
     * @param $value1
     * @param $key2
     * @param $value2
     * @return array
     */
    private static function int_assoc_search($array, $key1, $value1,$key2, $value2)
    {
        $results = array();
        self::int_assoc_search_r($array, $key1, $value1, $key2, $value2, $results);
        return $results;
    }

    /**
     * Helper function for int_assoc_search.
     * @param $array
     * @param $key1
     * @param $value1
     * @param $key2
     * @param $value2
     * @param $results
     */
    private static function int_assoc_search_r($array, $key1, $value1, $key2, $value2, &$results)
    {
        if (!is_array($array)) {
            return;
        }

        if (isset($array[$key1]) && $array[$key1] == $value1 && isset($array[$key2]) && $array[$key2] == $value2) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            self::int_assoc_search_r($subarray, $key1, $value1, $key2, $value2, $results);
        }
    }

    /**
     * Generate URL to config-gui given the RESTControllers
     * app.php directory.
     */
    public static function getConfigURL($appDir) {
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
