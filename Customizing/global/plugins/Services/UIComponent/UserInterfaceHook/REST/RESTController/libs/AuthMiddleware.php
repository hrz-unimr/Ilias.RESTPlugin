<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;


// Requires ../Slim/Slim.php
// Requires AuthLib.php
// Requires TokenLib.php
// Requires RESTLib.php


/*
 * Middleware Authentification Functions
 *  This middleware can be included in a route signature as follows:
 *  $app->get('/users', function () use ($app) { ... })
 *  $app->get('/users', \RESTController\libs\AuthMiddleware::authenticate, function () use ($app) { ... })
 *  $app->get('/users', \RESTController\libs\AuthMiddleware::authenticateTokenOnly, function () use ($app) { ... })
 *  $app->get('/users', \RESTController\libs\AuthMiddleware::authenticateILIASAdminRole, function () use ($app) { ... })
 *
 *  Every authentification method WILL also set certain environment variables that can be usefull
 *  while proccessing a route.
 *  @see checkToken()
 */
class AuthMiddleware {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_INVALID_SSL = 'RESTController\libs\AuthMiddleware::ID_INVALID_SSL';
    const ID_NO_TOKEN = 'RESTController\libs\AuthMiddleware::ID_NO_TOKEN';
    const ID_NO_USER = 'RESTController\libs\AuthMiddleware::ID_NO_USER';
    const ID_NO_KEY = 'RESTController\libs\AuthMiddleware::ID_NO_KEY';


    // Allow to re-use status-strings
    const MSG_TOKEN_EXPIRED = 'Token has expired.';
    const MSG_ADMIN_REQUIRED = 'Route requires ILIAS Admin-Role permissions.';
    const MSG_NO_PERMISSION = 'No permission to access this route.';
    const MSG_NO_TOKEN = 'No access-token provided or using invalid format.';
    const MSG_NO_USER = 'Token is invalid, missing user.';
    const MSG_NO_KEY = 'Token is invalid, missing api-key.';
    const MSG_INVALID_SSL = 'SSL-Certificate is invalid.';


    /* ### Auth-Middleware - Start ### */

    /**
     * This authorization middleware requires a valid  access-token (bearer)
     * or a valid SSQ certificate. Furthermore the permission for the client
     * to access the current route with a particular action is checked.
     *
     * @param \Slim\Route $route
     */
    public static function authenticate(\Slim\Route $route) {
        // Fetch instance of SLIM-Framework
        $app = \RESTController\RESTController::getInstance();

        // Authentication by client certificate
        // (see: http://cweiske.de/tagebuch/ssl-client-certificates.htm)
        $client = ($_SERVER['SSL_CLIENT_VERIFY'] && $_SERVER['SSL_CLIENT_S_DN_CN'] && $_SERVER['SSL_CLIENT_I_DN_O']) ? $_SERVER['SSL_CLIENT_S_DN_CN'] : NULL;
        $secret = NULL;
        if ($client) {
            // ToDo: no secret is needed, its just the organisation name
            $secret = $_SERVER['SSL_CLIENT_I_DN_O'];
            $ret = AuthLib::checkOAuth2ClientCredentials($client, $secret);

            // Stops everything and returns 401 response
            if (!$ret)
                $app->halt(401, MSG_INVALID_SSL, ID_INVALID_SSL);

            // Setup slim environment
            $env = $app->environment();
            $env['client_id'] = $client;
        }
        // Authentication by token
        else {
            $token = self::getToken($app);
            self::checkToken($app, $token);
        }

        // Check route permissions
        self::checkRoutePermissions($app, $route);
    }


    /**
     * This authorization middleware only checks if the access token (bearer) is valid,
     * but does not check if the user is allowed to acces this route.
     */
    public static function authenticateTokenOnly() {
        // Get instance of SLIM-Framework
        $app = \RESTController\RESTController::getInstance();

        // Fetch and check token
        $token = self::getToken($app);
        self::checkToken($app, $token);
    }


    /**
     * This authorization middleware checks if the access token (bearer) is valid and
     * the associated user has administration privileges (via ILIAS roles).
     */
    public static function authenticateILIASAdminRole() {
        // Get instance of SLIM-Framework
        $app = \RESTController\RESTController::getInstance();

        // Authentication by token
        $token = self::getToken($app);
        self::checkToken($app, $token);

        // Check if given user has admin-role ($env['user'] is set by checkToken())
        $env = $app->environment();
        if (!RESTLib::isAdminByUsername($env['user']))
            $app->halt(401, MSG_NO_ADMIN, RESTLib::ID_NO_ADMIN);
    }

    /* ### Auth-Middleware - End ### */


    /**
     *
     */
    public static function getToken($app) {
        // Fetch token from body GET/POST (json or plain)
        $request = $app->request();
        $token_ser = $request->getParam('token');

        // Fetch access_token from GET/POST (json or plain)
        if (is_null($token_ser))
            $token_ser = $request->getParam('access_token');

        // Fetch token from request header
        if (is_null($token_ser)) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'];

            // Found Authorization header?
            if ($authHeader != null) {
                $a_auth = explode(' ', $authHeader);
                $token_ser = $a_auth[1];        // With "Bearer"-Prefix
                if ($token_ser == null)
                    $token_ser = $a_auth[0];    // Without "Bearer"-Prefix
            }
        }

        // Decode token
        if (isset($token_ser))
            $token = TokenLib::deserializeToken($token_ser);
        return $token;
    }


    /**
     * Utility Function:
     *  Checks the validity of a token and stops the request flow, if the token is invalid.
     *  The token is fetched from the following locations:
     *   HTTP POST parameter: 'token'
     *   HTTP body json parameter: 'token'
     *   HTTP Authorization header: bearer <2. argument>
     *   HTTP Authorization header: <1. argument>
     *
     * IMPORTANT:
     *  This methods setup the application enviroment:
     *   (With $env = \Slim\Slim::getInstance()->environment();)
     *   $env['user'] = user value from given token
     *   $env['api_key'] = api-key value from given token
     *   $env['token'] = The given token is stored here
     */
    protected static function checkToken($app, $token) {
        // Check token for common problems: Non given or invalid format
        if (!$token)
            $app->halt(401, MSG_NO_TOKEN, ID_NO_TOKEN);

        // Check token for common problems: Invalid format
        if (TokenLib::tokenExpired($token))
            $app->halt(401, MSG_EXPIRED, ID_EXPIRED);

        // Check token for common problems: missing user entry (should not happen!)
        if (isset($token['user'])) {
            // Set ['user'] on environment
            $env = $app->environment();
            $env['user'] = $token['user'];
        }
        else
            $app->halt(401, MSG_NO_USER, ID_NO_USER);

        // Check token for common problems: missing api-key entry (should not happen!)
        if (isset($token['api_key'])) {
            // Set ['api_key'] on environment
            $env = $app->environment();
            $env['api_key'] = $token['api_key'];
        }
        else
            $app->halt(401, MSG_NO_KEY, ID_NO_KEY);

        // Set ['token'] on environment
        $env = $app->environment();
        $env['token'] = $token;
    }

    /**
     * Utility Function:
     *  Checks the permission for the current client to
     *  access a route with a certain action.
     *
     * NOTE: 'api_key' needs to be in \Slim\Slim::getInstance()->environment();
     *
     * @param \Slim\Route $route
     */
    protected static function checkRoutePermissions($app, $route) {
        // Fetch instance of SLIM-Framework and HTTP request
        $env = $app->environment();

        // Fetch data to check route access
        $api_key = $env['api_key'];
        $current_route = $route->getPattern();
        $current_verb = strtolower($app->request->getMethod());

        // Check route access rights given route, method and api-key
        if (!AuthLib::checkOAuth2Scope($current_route, $current_verb, $api_key))
            $app->halt(401, MSG_NO_PERMISSION, ID_NO_PERMISSION);
    }
 }
