<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs\Middleware;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\auth as Auth;
use \RESTController\core\auth\Exceptions as TokenExceptions;
use \RESTController\core\clients as Clients;


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
  const ID_NO_TOKEN         = 'RESTController\\libs\\OAuth2Middleware::ID_NO_TOKEN';
  const MSG_NO_TOKEN        = 'No access-token provided or using invalid format.';


  /**
   * Function: TOKEN($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   */
  public static function TOKEN($route) {
    // Fetch reference to RESTController
    $app = \RESTController\RESTController::getInstance();

    // Delegate access-token check
    self::checkAccessToken($app);
  }


  /**
   * Function: PERMISSION($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   *   b) The user is is allowed on this route (scope)
   */
  public static function PERMISSION($route) {
    // Fetch reference to RESTController
    $app = \RESTController\RESTController::getInstance();

    // Delegate access-token check
    $accessToken = self::checkAccessToken($app);

    // Delete permission-check
    $request = $app->request;
    self::checkRoutePermissions($app, $accessToken, $route, $request);
  }


  /**
   * Function: SHORT($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   *   b) The user is using a special 'short-lived' access-token
   */
  public static function SHORT($route) {
    // Fetch reference to RESTController
    $app = \RESTController\RESTController::getInstance();

    // Delegate access-token check
    $accessToken = self::checkAccessToken($app);

    // Delete short-token test
    self::checkShort($app, $accessToken);
  }


  /**
   * Function: checkAccessToken($app)
   *  Checks the validity of a token and stops application if invalid.
   *
   * Parameters:
   *  $app <RESTController> - Instance of the RESTController
   */
  public static function checkAccessToken($app) {
    try {
      // Fetch token
      $accessToken = Auth\Util::getAccessToken();

      // Check token for common problems: Non given or invalid format
      if (!$accessToken)
          $app->halt(401, self::MSG_NO_TOKEN, self::ID_NO_TOKEN);

      // Check token for common problems: Invalid format
      if (!$accessToken->isValid())
          $app->halt(401, Auth\Tokens\Generic::MSG_INVALID, Auth\Tokens\Generic::ID_INVALID);

      // Check token for common problems: Invalid format
      if ($accessToken->isExpired())
          $app->halt(401, Auth\Tokens\Generic::MSG_EXPIRED, Auth\Tokens\Generic::ID_EXPIRED);

      // Check IP (if option is enabled)
      $api_key  = $accessToken->getApiKey();
      $client   = new Clients\RESTClient($api_key);
      if (!$client->checkIPAccess($_SERVER['REMOTE_ADDR']))
        $app->halt(401, self::MSG_IP_NOT_ALLOWED, self::ID_IP_NOT_ALLOWED);

      // For sake of simplicity also return the access-token
      return $accessToken;
    }
    catch (TokenExceptions\TokenInvalid $e) {
        $app->halt(401, $e->getMessage(), $e->getRESTCode());
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
    $api_key  = $accessToken->getApiKey();
    $pattern  = $route->getPattern();
    $verb     = $request->getMethod();

    // Check route access rights given route, method and api-key
    $client   = new Clients\RESTClient($api_key);
    if (!$client->checkScope($pattern, $verb))
      $app->halt(401, self::MSG_NO_PERMISSION, self::ID_NO_PERMISSION);
  }


  /**
   * Function: checkShort($app, $accessToken)
   *  Checks if the given access-token is a special short-lived access-token
   *
   * Parameters:
   *  $app <RESTController> - Instance of the RESTController
   *  $accessToken <AccessToken> - AccessToken that needs to be checked for permissions
   */
  public static function checkShort($app, $accessToken) {
    // Test if token is a short (ttl) one and ip does match
    if ($accessToken->getEntry('type') != Auth\Challenge::type)
      $app->halt(401, 'This route requires a special short-lived access-token.');
    if ($accessToken->getEntry('misc') != $_SERVER['REMOTE_ADDR'])
      $app->halt(401, 'This token was generated from another address then the your current one.');
  }
}
