<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 *
 */
class RESTAuthFactory {
  //
  const TOKEN       = 1 << 0;
  const PERMISSION  = 1 << 1;
  const ADMIN       = 1 << 2;
  const SHORT       = 1 << 3;


  /**
   *
   */
  public static function checkAccess($level = null) {
    // Only a valid token is required
    if ($level & self::TOKEN)
      return '\RESTController\libs\OAuth2Middleware::TokenAuth';

    // Only a valid token is required
    if ($level & self::PERMISSION)
      return '\RESTController\libs\OAuth2Middleware::TokenAdminAuth';

    // Only a valid token is required
    if ($level & self::ADMIN)
      return '\RESTController\libs\OAuth2Middleware::TokenRouteAuth';

    // No authorization required
    return function() {};
  }
}
