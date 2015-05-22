<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;

use RESTController\core\auth as Auth;
use RESTController\core\auth\Token as Token;
use RESTController\core\auth\Exceptions as TokenExceptions;
// Requires RESTController
// Requires RESTLib


/*
 * OAuth2 Authentification Middleware Functions
 *
 *  This middleware can be included in a route signature as follows:
 *  $app->get('/users', function () use ($app) { ... })
 *  $app->get('/users', \RESTController\libs\OAuth2Middleware::TokenRouteAuth, function () use ($app) { ... })
 *  $app->get('/users', \RESTController\libs\OAuth2Middleware::TokenRouteAuthTokenOnly, function () use ($app) { ... })
 *  $app->get('/users', \RESTController\libs\OAuth2Middleware::TokenRouteAuthILIASAdminRole, function () use ($app) { ... })
 */
class OAuth2Middleware {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_NO_PERMISSION = 'RESTController\libs\OAuth2Middleware::ID_NO_PERMISSION';

    // Allow to re-use status-strings
    const MSG_NO_PERMISSION = 'No permission to access this route.';


    /* ### Auth-Middleware - Start ### */
    /**
     * This authorization middleware requires a valid  access-token (bearer)
     * or a valid SSQ certificate. Furthermore the permission for the client
     * to access the current route with a particular action is checked.
     *
     * @param \Slim\Route $route
     */
    public static function TokenRouteAuth(\Slim\Route $route) {
        try {
            // Fetch token
            $app = \RESTController\RESTController::getInstance();
            $util = new Auth\Util($app, $GLOBALS['ilDB'], $GLOBALS['ilPluginAdmin']);
            $accessToken = $util->getAccessToken();

            // Authenticate token
            self::checkAccessToken($app, $accessToken);

            // Check route permissions
            self::checkRoutePermissions($app, $route);
        }
        catch (TokenExceptions\TokenInvalid $e) {
            $app->halt(401, $e->getMessage(), $e::ID);
        }
    }


    /**
     * This authorization middleware checks if the access token (bearer) is valid and
     * the associated user has administration privileges (via ILIAS roles).
     *
     * @param \Slim\Route $route
     */
    public static function TokenAdminAuth(\Slim\Route $route) {
        try {
            // Fetch token
            $app = \RESTController\RESTController::getInstance();
            $util = new Auth\Util($app, $GLOBALS['ilDB'], $GLOBALS['ilPluginAdmin']);
            $accessToken = $util->getAccessToken();

            // Authentication by token
            self::checkAccessToken($app, $accessToken);

            // Check if given user has admin-role
            $user = $accessToken->getUserName();
            if (!RESTLib::isAdminByUsername($user))
                $app->halt(401, RESTLib::MSG_NO_ADMIN, RESTLib::ID_NO_ADMIN);
        }
        catch (TokenExceptions\TokenInvalid $e) {
            $app->halt(401, $e->getMessage(), $e::ID);
        }
    }


    /**
     * This authorization middleware only checks if the access token (bearer) is valid,
     * but does not check if the user is allowed to acces this route.
     *
     * @param \Slim\Route $route
     */
    public static function TokenAuth(\Slim\Route $route) {
        try {
            // Fetch token
            $app = \RESTController\RESTController::getInstance();
            $util = new Auth\Util($app, $GLOBALS['ilDB'], $GLOBALS['ilPluginAdmin']);
            $accessToken = $util->getAccessToken();

            // Fetch and check token
            self::checkAccessToken($app, $accessToken);
        }
        catch (TokenExceptions\TokenInvalid $e) {
            $app->halt(401, $e->getMessage(), $e::ID);
        }
    }
    /* ### Auth-Middleware - End ### */


    /**
     * Checks the validity of a token and stops application if invalid.

     * !!!
     */
    protected static function checkAccessToken($app, $accessToken) {
        // Check token for common problems: Non given or invalid format
        if (!$accessToken)
            $app->halt(401, Token\Base::MSG_NO_TOKEN, Token\Base::ID_NO_TOKEN);

        // Check token for common problems: Invalid format
        if (!$accessToken->isValid($accessToken))
            $app->halt(401, Token\Generic::MSG_INVALID, Token\Generic::ID_INVALID);

        // Check token for common problems: Invalid format
        if ($accessToken->isExpired($accessToken))
            $app->halt(401, Token\Generic::MSG_EXPIRED, Token\Generic::ID_EXPIRED);
    }


    /**
     * Checks the permission for the current client to access a route with a certain action.
     *
     * !!!
     */
    protected static function checkRoutePermissions($app, $route, $accessToken) {
        // Fetch data to check route access
        $api_key = $accessToken->getEntry('api_key');
        $pattern = $route->getPattern();
        $verb = $app->request->getMethod();

        // Check route access rights given route, method and api-key
        if (!Auth\Util::checkOAuth2Scope($current_route, $current_verb, $api_key))
            $app->halt(401, self::MSG_NO_PERMISSION, self::ID_NO_PERMISSION);
    }
 }
