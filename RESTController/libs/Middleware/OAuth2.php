<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs\Middleware;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;
use \RESTController\core\oauth2_v2\Tokens as Tokens;


/*
 * Class: OAuth2 (Middleware)
 *  Implements route authentification that is only concerned with oauth2 information.
 */
class OAuth2 {
  // Allow to re-use status messages and codes
  const ID_IP_NOT_ALLOWED   = 'RESTController\\libs\\OAuth2Middleware::ID_IP_NOT_ALLOWED';
  const MSG_IP_NOT_ALLOWED  = 'Access denied for client IP address.';
  const ID_NO_PERMISSION    = 'RESTController\\libs\\OAuth2Middleware::ID_NO_PERMISSION';
  const MSG_NO_PERMISSION   = 'No permission to access this route.';
  const ID_NEED_SHORT       = 'RESTController\\libs\\OAuth2Middleware::ID_NEED_SHORT';
  const MSG_NEED_SHORT      = 'This route requires a special short-lived access-token.';
  const ID_WRONG_IP         = 'RESTController\\libs\\OAuth2Middleware::ID_WRONG_IP';
  const MSG_WRONG_IP        = 'This token was generated from another address then the your current one.';


  /**
   * Function: TOKEN($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   */
  public static function TOKEN($route) {
    try {
      // Fetch reference to RESTController
      $app = \RESTController\RESTController::getInstance();

      // Fetch access-token (this also checks it)
      $request      = $app->request();
      $accessToken  = $request->getToken('access');
    }

    // Catches following exceptions from getToken():
    //  Auth\Exceptions\TokenInvalid - Token is invalid or expired
    //  Exceptions\Parameter - Token is missing
    //  Exceptions\Database - Tokens oAuth2 client does not exists
    //  Exceptions\Denied - IP- or User- restriction in place
    catch (Libs\RESTException $e) {
      $e->send(401);
    }
  }


  /**
   * Function: PERMISSION($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   *   b) The user is is allowed on this route (scope)
   */
  public static function PERMISSION($route) {
    try {
      // Fetch reference to RESTController
      $app = \RESTController\RESTController::getInstance();

      // Fetch access-token (this also checks it)
      $request      = $app->request();
      $accessToken  = $request->getToken('access');

      // Delete permission-check
      self::checkRoutePermissions($app, $accessToken, $route, $request);
    }

    // Catches following exceptions from getToken():
    //  Auth\Exceptions\TokenInvalid - Token is invalid or expired
    //  Exceptions\Parameter - Token is missing
    //  Exceptions\Database - Tokens oAuth2 client does not exists
    //  Exceptions\Denied - IP- or User- restriction in place
    catch (Libs\RESTException $e) {
      $e->send(401);
    }
  }


  /**
   * Function: checkRoutePermissions($app, $accessToken, $route, $request)
   *  Checks the permission for the current client to access a route with a certain action.
   *
   * Parameters:
   *  $app <RESTController> - Instance of the RESTController
   *  $accessToken <AccessToken> - AccessToken that needs to be checked for permissions
   *  $route <String> - Route for which the access-token needs to be checked
   *  $request <RESTRequest> - Request-object (used to fetch VERB)
   */
  public static function checkRoutePermissions($app, $accessToken, $route, $request) {
      // Fetch data to check route access
      $apiKey   = $accessToken->getApiKey();
      $pattern  = $route->getPattern();
      $verb     = $request->getMethod();

      // Query (and throw if no) permissions exists for pattern/verb
      $where    = sprintf(
        'RESTpermission.pattern = %s AND RESTpermission.verb = %s AND RESTclient.api_key = %s',
        Database\RESTpermission::quote($pattern, 'text'),
        Database\RESTpermission::quote($verb,    'text'),
        Database\RESTpermission::quote($apiKey,  'text')
      );
      if (!Database\RESTpermission::existsByWhere($where, 'RESTclient'))
        $app->halt(401, self::MSG_NO_PERMISSION, self::ID_NO_PERMISSION);
  }
}
