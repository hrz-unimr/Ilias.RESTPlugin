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
use \RESTController\core\oauth2_v2 as Auth;


/*
 * Class: ILIAS (Middleware)
 *  Implements route authentification that is related to ILIAS.
 */
class ILIAS {
  // Allow to re-use status messages and codes
  const MSG_NO_ADMIN        = 'Access denied. Administrator permissions required.';
  const ID_NO_ADMIN         = 'RESTController\\libs\\OAuth2Middleware::ID_NO_ADMIN';


  /**
   * Function: ADMIN($route)
   *  This route can be used as middleware on a route
   *  to check if:
   *   a) The token is valid
   *   b) The user is admin in ILIAS
   */
  public static function ADMIN($route) {
    try {
      // Fetch reference to RESTController
      $app = \RESTController\RESTController::getInstance();

      // Fetch access-token (this also checks it)
      $request      = $app->request();
      $accessToken  = $request->getToken('access');

      // Delegate route-permission check
      OAuth2::checkRoutePermissions($app, $accessToken, $route, $request);

      // Check if user is admin in ILIAS
      self::checkAdmin($app,$accessToken);
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
   * Function: checkAdmin($accessToken)
   *  This function checks wether the user
   *  given by the access-token has the admin-role
   *  in ILIAS.
   *  Will stop with 401 if user isn't admin.
   *
   * Parameters:
   *  $accessToken <AccessToken> - Access-Token which contains the user that should be checked
   */
  protected static function checkAdmin($app, $accessToken) {
    // Check if given user has admin-role
    //$app = \RESTController\RESTController::getInstance();
    $app->log->debug("ILIAS > check Admin");
    //$app->log->debug(print_r(debug_backtrace(),true));
   // $app->halt(401, self::MSG_NO_ADMIN, self::ID_NO_ADMIN);
   /* $app->log->debug(print_r($accessToken,true)); */
    $userId = $accessToken->getUserId();
    if (!Libs\RESTilias::isAdmin($userId)) {
      $app->halt(401, self::MSG_NO_ADMIN, self::ID_NO_ADMIN);
    }
  }
 }
