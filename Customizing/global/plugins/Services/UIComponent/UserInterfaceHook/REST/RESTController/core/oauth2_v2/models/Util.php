<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


/**
 * Class: Permission
 *
 */
class Util extends Libs\RESTModel {
  // List of routes that need explicit permission entry
  const NeedPermission = array(
    'RESTController\\libs\\Middleware\\OAuth2::PERMISSION',
    'RESTController\\libs\\Middleware\\ILIAS::ADMIN'
  );


  /**
   * Function: GetRoutes($routes, $apiKey = null, $filter = null)
   *  Returns a list of all routes, a list of restricted routes or a
   *  list of all routes allowed to be accessed by a given api-key, depending
   *  on the given parameters. (See below)
   *
   * Parameters:
   *  $routes <Array[SlimRoute]> - List of all available routes
   *  $apiKey <String> - Returns only routes the given API-Key has access (permission) to
   *  $filter <Boolean> - Filter routes, only returning those requiring a permission to be added
   */
  public static function GetRoutes($routes, $apiKey = null, $filter = null) {
    $result = array();
    foreach($routes as $route) {
      // Fetch information about route
      $pattern      = $route->getPattern();
      $verbs        = $route->getHttpMethods();
      $verb         = $verbs[0];

      // Fetch access and scope middleware
      $access       = null;
      $scope        = null;
      $middlewares  = $route->getMiddleware();
      foreach($middlewares as $middleware) {
        if (is_string($middleware))
          $access   = $middleware;
        elseif (is_callable($middleware))
          $scope    = $middleware(true);
      }

      // Fetch only those routes the api-key has access to
      $hasPermission = true;
      if ($apiKey && in_array($access, self::NeedPermission)) {
        $where      = sprintf(
          'RESTpermission.pattern = %s AND RESTpermission.verb = %s AND RESTclient.api_key = %s',
          Database\RESTpermission::quote($pattern, 'text'),
          Database\RESTpermission::quote($verb,    'text'),
          Database\RESTpermission::quote($apiKey,  'text')
        );
        $hasPermission = Database\RESTpermission::existsByWhere($where, 'RESTclient');
      }

      // Only return routes which require a permission entry (and client has a permission for)
      if ($hasPermission && (!$filter || in_array($access, self::NeedPermission))) {
        // Build result array
        $id           = sprintf('[%s]%s', $verb, $pattern);
        $result[$id]  = array_filter(array(
          pattern     => $pattern,
          verb        => $verb,
          access      => $access,
          scope       => $scope
        ), function($value) { return !is_null($value); });
      }
    }

    // return parsed routes
    return $result;
  }
}
