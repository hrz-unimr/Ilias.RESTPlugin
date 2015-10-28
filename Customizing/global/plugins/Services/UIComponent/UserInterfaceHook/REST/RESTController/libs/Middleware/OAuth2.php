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
 */
class OAuth2 {
  /**
   * List of default REST error-codes
   *  Extensions are allowed to create their own error-codes.
   *  Using a unique string seems to be an easier solution than assigning unique numbers.
   */
  const ID_IP_NOT_ALLOWED   = 'RESTController\libs\RESTLib::ID_IP_NOT_ALLOWED';
  const ID_NO_PERMISSION    = 'RESTController\libs\OAuth2Middleware::ID_NO_PERMISSION';

  // Allow to re-use status-strings
  const MSG_IP_NOT_ALLOWED  = 'Access denied for client IP address.';
  const MSG_NO_PERMISSION   = 'No permission to access this route.';


  /**
   * Function: TOKEN($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   *   b) The user is is allowed on this route (scope)
   */
  public static function TOKEN($route) {
    // Fetch reference to RESTController
    $app = \RESTController\RESTController::getInstance();

    // Delegate access-token check
    self::checkAccessToken($app);
  }


  /**
   * Function: TOKEN($route)
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
   * Function: TOKEN($route)
   *
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
   * Checks the validity of a token and stops application if invalid.
   */
  public static function checkAccessToken($app) {
    try {
      // Fetch token
      $accessToken = Auth\Util::getAccessToken();

      // Check token for common problems: Non given or invalid format
      if (!$accessToken)
          $app->halt(401, Auth\Token\Base::MSG_NO_TOKEN, Auth\Token\Base::ID_NO_TOKEN);

      // Check token for common problems: Invalid format
      if (!$accessToken->isValid())
          $app->halt(401, Auth\Token\Generic::MSG_INVALID, Auth\Token\Generic::ID_INVALID);

      // Check token for common problems: Invalid format
      if ($accessToken->isExpired())
          $app->halt(401, Auth\Token\Generic::MSG_EXPIRED, Auth\Token\Generic::ID_EXPIRED);

      // Check IP (if option is enabled)
      $api_key  = $accessToken->getApiKey();
      $client   = new Clients\RESTClient($api_key);
      if (!$client->checkIPAccess($_SERVER['REMOTE_ADDR']))
        $app->halt(401, self::MSG_IP_NOT_ALLOWED, self::ID_IP_NOT_ALLOWED);

      // For sake of simplicity also return the access-token
      return $accessToken;
    }
    catch (TokenExceptions\TokenInvalid $e) {
        $app->halt(401, $e->getMessage(), $e::ID);
    }
  }


  /**
   * Checks the permission for the current client to access a route with a certain action.
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
   * Checks if the given access-token is a special short-lived access-token
   */
  public static function checkShort($app, $accessToken) {
    // Test if token is a short (ttl) one and ip does match
    if ($accessToken->getEntry('type') != Auth\Challenge::type)
      $app->halt(401, 'This route requires a special short-lived access-token.');
    if ($accessToken->getEntry('misc') != $_SERVER['REMOTE_ADDR'])
      $app->halt(401, 'This token was generated from another address then the your current one.');
  }
}
