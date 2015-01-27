<?php
/**
 * This default middleware function realizes an authentication mechanism
 * for any REST routes for client access.
 *
 * Clients with a valid certificate or with a valid token are allowed to pass.
 *
 * Furthermore the permission for the client to access the current route with a
 * particular action is checked.
 *
 * This middleware can be included in a route signature as follows:
 * $app->get('/users', authenticate, function () use ($app) {...
 *
 * @param \Slim\Route $route
 */
function authenticate(\Slim\Route $route)
{
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    $client = ($_SERVER["SSL_CLIENT_S_DN_CN"] && $_SERVER["SSL_CLIENT_I_DN_O"]) ? $_SERVER["SSL_CLIENT_S_DN_CN"] : NULL;
    $secret = NULL;
    if ($client) { // Authentication by client certificate
        $secret = $_SERVER["SSL_CLIENT_I_DN_O"]; // ToDo: no secret is needed, its just the organisation name
        $ret = ilAuthLib::checkOAuth2ClientCredentials($client, $secret);
        if (!$ret) {
            $app->halt(401);
            exit;
        }
        $env = $app->environment();
        $env['client_id'] = $client;
    }
    else { // Authentication by token

        checkToken();
    }

    checkRoutePermissions($route);
}

/**
 * This authorization middleware can be used for routes, where no check against client permissions are necessary.
 *
 * In contrast to the default mechanism, only the validity check on the token is performed.
 */
function authenticateTokenOnly()
{
    checkToken();
}

/**
 * This authorization middleware can be used for routes
 * that require that the requester has obtained a valid token and
 * the associated user has administration privileges.
 */
function authenticateILIASAdminRole()
{
    checkToken();
    $app = \Slim\Slim::getInstance();
    $env = $app->environment();
    if (ilRestLib::isAdminByUsername($env['user']) == false) {
        $output=array();
        $output['msg'] = "Admin permission required.";
        echo json_encode($output);
        exit;
    }
}

// ---------------------------------------------------------------------------
/**
 * Utility Function: checks the validity of a token and stops the request flow, if the token is invalid.
 */
function checkToken()
{
    $app = \Slim\Slim::getInstance();
    $request = $app->request();
    $token_ser = $request->params('token');

    if ($token_ser == null) {
        $jsondata = $app->request()->getBody(); // json
        $a_data = json_decode($jsondata, true);
        $token_ser = $a_data['token'];
    }

    if ($token_ser == null) {
        $headers = apache_request_headers();
        // $app->log->debug(print_r($headers, true));
        $authHeader = $headers['Authorization'];
        if ($authHeader!=null) {
            $a_auth = explode(" ",$authHeader);
            $token_ser = $a_auth[1];    // Bearer Access Token
            if ($token_ser == null) {
                $token_ser = $a_auth[0];
            }
        }
    }

    $token = ilTokenLib::deserializeToken($token_ser);

    if (!$token) {
        $app->halt(401);
        exit;
    }

    if (ilTokenLib::tokenExpired($token)) {
        $output=array();
        $output['msg'] = "Token expired.";
        echo json_encode($output);
        //$app->halt(403);
        exit;
    }

    // State: token valid
    if (isset($token['user'])) {
        $env = $app->environment();
        $env['user'] = $token['user'];
    }

    if (isset($token['api_key'])) {
        $env = $app->environment();
        $env['api_key'] = $token['api_key'];
    }

    $env['token'] = $token;
}

/**
 * Utility Function: checks the permission for the current client to access
 * a route under a requested action.
 */
function checkRoutePermissions($route)
{
    $app = \Slim\Slim::getInstance();
    $env = $app->environment();
    $api_key = $env['api_key'];
    $current_route = $route->getPattern();
    $current_verb = strtolower($app->request->getMethod());

    if (!ilAuthLib::checkOAuth2Scope($current_route, $current_verb, $api_key)) {
        $app->log->debug("Invalid scope for client ".$api_key);
        $app->response()->header('Content-Type', 'application/json');
        $output=array();
        $output['msg'] = "Client has no permission to access route";
        echo json_encode($output);
        $app->halt(401);
        //exit;
    }
}

?>
