<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Class: RESTAuth
 *  This class serves as main authentification-endpoint
 *  that selects the required authentification implementation
 *  based on the required access-level. (see below)
 */
class RESTAuth {
  // Possible security-levels
  const TOKEN       = 'RESTAuth::TOKEN';       // Check for valid token
  const PERMISSION  = 'RESTAuth::PERMISSION';  // Check if allowed on route and has valid token
  const ADMIN       = 'RESTAuth::ADMIN';       // Check if allowed and has admin-role and has valid token
  const SHORT       = 'RESTAuth::SHORT';       // Check if token has short ttl and attached ip and is otherwise valid


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
  public static function checkAccess($level = null) {
    // Select auth that matches given security-level
    switch($level) {
      case self::TOKEN:
        return 'RESTController\libs\Middleware\OAuth2::TOKEN';
      case self::PERMISSION:
        return 'RESTController\libs\Middleware\OAuth2::PERMISSION';
      case self::ADMIN:
        return 'RESTController\libs\Middleware\ILIAS::ADMIN';
      case self::SHORT:
        return 'RESTController\libs\Middleware\OAuth2::SHORT';
    }

    // No check required
    return function() {};
  }
}
