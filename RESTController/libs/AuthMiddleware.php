<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */

 
// Requires ../Slim/Slim.php
// Requires AuthLib.php
// Requires TokenLib.php
 
 
/* 
 * ---------------------------------------------------------------------------
 * Middleware Authentification Functions
 *  This middleware can be included in a route signature as follows:
 *  $app->get('/users', function () use ($app) { ... })
 *  $app->get('/users', authenticate, function () use ($app) { ... })
 *  $app->get('/users', authenticateTokenOnly, function () use ($app) { ... })
 *  $app->get('/users', authenticateILIASAdminRole, function () use ($app) { ... })
 * --------------------------------------------------------------------------- 
 */
 
 
/**
 * This authorization middleware requires a valid  access-token (bearer) 
 * or a valid SSQ certificate. Furthermore the permission for the client 
 * to access the current route with a particular action is checked.
 *
 * @param \Slim\Route $route
 */
function authenticate(\Slim\Route $route) {
    // Fetch instance of SLIM-Framework and HTTP request
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    
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
            $app->halt(401, "Using invalid SSL-Certificate.");
        
        // Setup slim environment 
        $env = $app->environment();
        $env['client_id'] = $client;
    }
    // Authentication by token
    else 
        checkToken();

    // Check route permissions
    checkRoutePermissions($route);
}


/**
 * This authorization middleware only checks if the access token (bearer) is valid, 
 * but does not check if the user is allowed to acces this route.
 */
function authenticateTokenOnly() {
    // Authentication by token
    checkToken();
}


/**
 * This authorization middleware checks if the access token (bearer) is valid and
 * the associated user has administration privileges.
 */
function authenticateILIASAdminRole() {
    // Authentication by token
    checkToken();
    
    // Fetch instance of SLIM-Framework
    $app = \Slim\Slim::getInstance();
    $env = $app->environment();
    
    // Check if given user has admin-role ($env['user'] is set by checkToken())
    if (!RESTLib::isAdminByUsername($env['user'])) 
        $app->halt(401, "Admin permissions required.");
}


/* 
 * ---------------------------------------------------------------------------
 * End of Middleware Authentification Functions
 * --------------------------------------------------------------------------- 
 */
 
 
/**
 * Utility Function: 
 *  Checks the validity of a token and stops the request flow, if the token is invalid.
 *  The token is fetched from the following locations:
 *   HTTP POST parameter: 'token'
 *   HTTP body json parameter: 'token'
 *   HTTP Authorization header: bearer <2. argument>
 *   HTTP Authorization header: <1. argument>
 */
function checkToken() {
    // Fetch instance of SLIM-Framework and HTTP request
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    
    // Fetch token from body (as POST data)
    $token_ser = $request->params('token');
    
    // Fetch token from body (as json)
    if ($token_ser == null) {
        $jsondata = $request->getBody();
        $a_data = json_decode($jsondata, true);
        $token_ser = $a_data['token'];
    }

    // Fetch token from Authorization header
    if ($token_ser == null) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'];
        
        // Found Authorization header?
        if ($authHeader != null) {
            $a_auth = explode(" ", $authHeader);
            $token_ser = $a_auth[1];        // Bearer Access Token
            if ($token_ser == null) 
                $token_ser = $a_auth[0];    // Non bearer prefix
        }
    }

    // Decode token
    $token = TokenLib::deserializeToken($token_ser);

    // Check token for common problems: Non given or invalid format
    if (!$token) 
        $app->halt(401, "No access-token provided or using invalid format.");
    
    // Check token for common problems: Invalid format
    if (TokenLib::tokenExpired($token)) 
        $app->halt(401, "Token expired.");
    
    // Check token for common problems: missing user entry (should not happen!)
    if (isset($token['user'])) {
        // Set ['user'] on environment
        $env = $app->environment();
        $env['user'] = $token['user'];
    }
    else
        $app->halt(401, "Invalid token, missing user.");
    
    // Check token for common problems: missing api-key entry (should not happen!)
    if (isset($token['api_key'])) {
        // Set ['api_key'] on environment
        $env = $app->environment();
        $env['api_key'] = $token['api_key'];
    }
    else
        $app->halt(401, "Invalid token, missing api-key.");

    // Set ['token'] on environment
    $env['token'] = $token;
}

/**
 * Utility Function: 
 *  Checks the permission for the current client to 
 *  access a route under a requested action.
 *
 * @param \Slim\Route $route
 */
function checkRoutePermissions($route) {
    // Fetch instance of SLIM-Framework and HTTP request
    $app = \Slim\Slim::getInstance();
    $env = $app->environment();
    
    // Fetch data to check route access
    $api_key = $env['api_key'];
    $current_route = $route->getPattern();
    $current_verb = strtolower($app->request->getMethod());

    // Check route access rights given route, method and api-key
    if (!AuthLib::checkOAuth2Scope($current_route, $current_verb, $api_key)) 
        $app->halt(401, "No permission to access route.");
}
