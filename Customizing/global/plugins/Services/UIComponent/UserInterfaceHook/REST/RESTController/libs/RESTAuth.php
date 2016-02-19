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
  const SHORT       = 'RESTAuth::SHORT';       // TOKEN, PERMISSION and check if token has short ttl and attached ip
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
      case self::SHORT:
        return 'RESTController\\libs\\Middleware\\OAuth2::SHORT';
      case self::ADMIN:
        return 'RESTController\\libs\\Middleware\\ILIAS::ADMIN';
    }
  }


  /**
   * Function: checkScope($scope)
   *  Returns a reference to an actual function that checks if the given
   *  access-token has the required scope given as parameter.
   * Note: Can be used together with checkAccess() as another route callable.
   *
   * Parameters:
   *  $scope <String> - Specify the required access-level for a given route (see above)
   *
   * Return:
   *  <String> - Reference (fully-quantified name of/) to the function that will be called
   */
  public static function checkScope($scope) {
    // TODO: !!! Implement
  }
}
