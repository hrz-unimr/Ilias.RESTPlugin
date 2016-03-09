<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// Requires Middleware/OAuth2
// Requires Middleware/ILIAS


/**
 * Class: RESTAuth
 *  This class serves as main authentification-endpoint
 *  that selects the required authentification implementation
 *  based on the required access-level. (see below)
 */
class RESTAuth {
  // Possible security-levels
  const TOKEN       = 'RESTAuth::TOKEN';       // Check for valid token
  const PERMISSION  = 'RESTAuth::PERMISSION';  // TOKEN and check if allowed on route
  const ADMIN       = 'RESTAuth::ADMIN';       // TOKEN, PERMISSION and check if user has ILIAS admin-role


  /**
   * Function: checkAccess($level)
   *  Returns a reference to an actual authentification-function
   *  that corresponds to the requested access-level.
   *
   *  <sarcasm>
   *   Shout-out to PHP which only supports function references as strings
   *  </sarcasm>
   *
   * Parameters:
   *  $level <String> - Specify the required access-level for a given route (see above)
   *
   * Return:
   *  <String> - Reference (fully-quantified name of/) to the function that will be called
   */
  public static function checkAccess($level) {
    // Select auth that matches given security-level
    switch($level) {
      default:
      case self::TOKEN:
        return 'RESTController\\libs\\Middleware\\OAuth2::TOKEN';
      case self::PERMISSION:
        return 'RESTController\\libs\\Middleware\\OAuth2::PERMISSION';
      case self::ADMIN:
        return 'RESTController\\libs\\Middleware\\ILIAS::ADMIN';
    }
  }


  /**
   * Function: checkScope($scope)
   *  Returns a reference to an actual function that checks if the given
   *  access-token has the required scope given as parameter.
   *
   *  Note that this will generate a new function everywhere this
   *  function is used, since you can't easily pass pre-defined parameters
   *  to slim callables.
   *
   * Note:
   *  Can be used together with checkAccess() as another route callable.
   *
   * Parameters:
   *  $scope <String> - Specify the required access-level for a given route (see above)
   *
   * Return:
   *  <String> - Reference (fully-quantified name of/) to the function that will be called
   */
  public static function checkScope($scope) {
    return function($getScope = false) use ($scope) {
      // Get the scope (required to fetch route-information)
      if ($getScope === true)
        return $scope;

      try {
        // Fetch reference to RESTController
        $app = \RESTController\RESTController::getInstance();

        // Fetch access-token (this also checks it)
        $request      = $app->request();
        $accessToken  = $request->getToken('access');
        $tokenScope   = $accessToken->getScope();
        $client       = $accessToken->getClient();

        // Check wether access-token scope covers the requested scope
        if (!$client->isScopeAllowed($scope))
            $app->halt(401, self::MSG_NO_SCOPE, self::ID_NO_SCOPE);
      }

      // Catches following exceptions from getToken():
      //  Auth\Exceptions\TokenInvalid - Token is invalid or expired
      //  Exceptions\Parameter - Token is missing
      //  Exceptions\Denied - IP- or User- restriction in place
      //  Exceptions\Database - Tokens oAuth2 client does not exists
      // Catches following exceptions from getClient()
      //  Exceptions\Database - Tokens oAuth2 client does not exists
      catch (Libs\RESTException $e) {
        $e->send(401);
      }
    };
  }
}
