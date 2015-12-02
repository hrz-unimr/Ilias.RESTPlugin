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
use \RESTController\core\auth as Auth;


/*
 * Class: ILIAS (Middleware)
 *  Implements route authentification that is related ILIAS.
 */
class ILIAS {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_NO_ADMIN         = 'RESTController\libs\OAuth2Middleware::ID_NO_ADMIN';

    // Allow to re-use status-strings
    const MSG_NO_ADMIN        = 'Access denied. Administrator permissions required.';


    /**
     * Function: ADMIN($route)
     *  This route can be used as middleware on a route
     *  to check if:
     *   a) The token is valid
     *   b) The user is admin in ILIAS
     */
    public static function ADMIN($route) {
      // Fetch reference to RESTController
      $app = \RESTController\RESTController::getInstance();

      // Delegate access-token check
      $accessToken = OAuth2::checkAccessToken($app);

      // Check if user is admin in ILIAS
      self::checkAdmin($app,$accessToken);
    }
    

    /**
     * Function: checkAdmin($accessToken)
     *  This function checks whether the user
     *  given by the access-token has the admin-role
     *  in ILIAS.
     *  Will stop with 401 if user isn't admin.
     *
     * Parameters:
     *  $accessToken <AccessToken> - Access-Token which contains the user that should be checked
     */
    protected static function checkAdmin($app,$accessToken) {
      // Check if given user has admin-role
      $user = $accessToken->getUserName();
      if (!Libs\RESTLib::isAdminByUserName($user))
          $app->halt(401, ILIAS::MSG_NO_ADMIN, ILIAS::ID_NO_ADMIN);
    }
 }
