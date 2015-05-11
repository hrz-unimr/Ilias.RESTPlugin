<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Lib;
use \RESTController\libs\Exceptions as LibExceptions;
// Requires <$app = \RESTController\RESTController::getInstance()>


// Group as version-1 implementation
$app->group('/v1', function () use ($app) {


    // Group as oauth2 implementation
    $app->group('/oauth2', function () use ($app) {


        /**
         * Route: /v1/oauth2/auth
         * Description:
         *  (RCF6749) Authorization Endpoint, used by the following grant-types:
         *   - authorization code grant
         *   - implicit grant type flows
         *  See http://tools.ietf.org/html/rfc6749
         * Method: POST
         * Auth:
         * Parameters:
         * Response:
         */
        $app->post('/auth', function () use ($app) {
            // Fetch request data
            $request = $app->request();
            $api_key = $request->getParam('api_key');
            $username = $request->getParam('username');
            $password = $request->getParam('password');
            $redirect_uri = $request->getParam('redirect_uri');
            $response_type = $request->getParam('response_type');
            $authenticity_token = $request->getParam('authenticity_token');

            // Get Oauth2-Model
            $model = new OAuth2($app, $ilDB);

            // Type: Authorization grant
            if ($response_type == 'code')
//                $model->authAuthorizationCode($api_key, $redirect_uri, $username, $password, $response_type, $authenticity_token);
                echo "<stub>";
            // Type: Implicit grant
            elseif ($response_type == 'token')
//                $model->authImplicitGrant($api_key, $redirect_uri, $username, $password, $response_type, $authenticity_token);
                echo "<stub>";
            // Wrong grant-type
            else
                $app->halt(500, 'Parameter <response_type> need to match "code" or "token".', OAuth2Exceptions\ResponseType::ID);
        });


        /**
         * Route: /v1/oauth2/auth
         * Description:
         *  Authorization Endpoint, this part covers only the first section of the auth
         *  flow and is included here, s.t. clients can initiate the "authorization or
         *  implicit grant flow" with a GET request.
         *  The flow after calling "oauth2loginform" continues with the POST
         *  version of "oauth2/auth".
         * Method: GET
         * Auth:
         * Parameters:
         * Response:
         */
        $app->get('/auth', function () use ($app) {
            // Fetch request data (GET instead of body)
            // Issue: Standard ILIAS Init absorbs client_id GET request field
            $apikey = $_GET['api_key'];
            $redirect_uri = $_GET['redirect_uri'];
            $response_type = $_GET['response_type'];

            // Render oauth2login.php for authorization code and implicit grant type flow
            if ($response_type == 'code' || $response_type == 'token') {
                if ($apikey && $redirect_uri) {
                    // Get Oauth2-Model
                    $model = new OAuth2($app, $ilDB);

                    // Render login-form
/*
                    $model->render(
                        'REST OAuth - Login für Tokengenerierung',
                        'oauth2loginform.php',
                        array(
                            'api_key' => $apikey,
                            'redirect_uri' => $redirect_uri,
                            'response_type' => $response_type
                        )
                    );
*/
                }
                // Missing data
                else
                    $app->halt(500, 'Mandatory data is missing, GET-Parameters <api_key, redirect_uri> need to be set.', LibExceptions\MissingParameter::ID);
            }
            // Wrong grant-type
            else
                $app->halt(500, 'Parameter <response_type> need to match "code" or "token".', OAuth2Exceptions\ResponseType::ID);
        });


        /**
         * Route: /v1/oauth2/token
         * Description:
         *  Token Endpoint, supported grant types:
         *   - Resource Owner (User),
         *   - Client Credentials and
         *   - Authorization Code Grant
         *  See http://tools.ietf.org/html/rfc6749
         * Method: POST
         * Auth:
         * Parameters:
         * Response:
         */
        $app->post('/token', function () use ($app) {
            // Get Request & OAuth-Model objects
            $request =  $app->request();
            $model = new OAuth2($app, $ilDB);

            // Resource Owner (User) grant type
            if ($request->getParam('grant_type') == 'password') {
                try {
                    // Fetch request data
                    $api_key = $request->getParam('api_key', null, true);
                    $user = $request->getParam('username', null, true);
                    $password = $request->getParam('password', null, true);

                    // Invoke OAuth2-Model with data
//                    $model->tokenUserCredentials($api_key, $user, $password);
                }
                catch (LibExceptions\MissingParameter $e) {
                    $app->halt(500, sprintf('Mandatory data is missing, parameter <%s> not set.', $e.paramName()), LibExceptions\MissingParameter::ID);
                }
            }
            // grant type
            elseif ($request->getParam('grant_type') == 'client_credentials') {
                try {
                    // Fetch request data
                    $api_key = $request->getParam('api_key', null, true);
                    $api_secret = $request->getParam('api_secret', null, true);

                    // Invoke OAuth2-Model with data
//                    $model->tokenClientCredentials($api_key, $api_secret);
                }
                catch (LibExceptions\MissingParameter $e) {
                    $app->halt(500, sprintf('Mandatory data is missing, parameter <%s> not set.', $e.paramName()), LibExceptions\MissingParameter::ID);
                }
            }
            // grant type
            elseif ($request->getParam('grant_type') == 'authorization_code') {
                try {
                    // Fetch request data (POST-Form instead of body)
                    $api_key = $_POST['api_key'];

                    // Fetch request data
                    $code = $request->getParam('code', null, true);
                    $api_secret = $request->getParam('api_secret', null, true);
                    $redirect_uri = $request->getParam('redirect_uri');

                    // Invoke OAuth2-Model with data
//                    $model->tokenAuthorizationCode($api_key, $api_secret, $code, $redirect_uri);
                }
                catch (LibExceptions\MissingParameter $e) {
                    $app->halt(500, sprintf('Mandatory data is missing, parameter <%s> not set.', $e.paramName()), LibExceptions\MissingParameter::ID);
                }
            }
            // grant type
            elseif ($request->getParam('grant_type') == 'refresh_token') {
                try {
                    // Fetch request data
                    $refresh_token = $request->getParam('refresh_token', null, true);

                    // Invoke OAuth2-Model with data
//                    $model->tokenRefresh2Bearer($refresh_token);

                    $response->setHttpHeader('Cache-Control', 'no-store');
                    $response->setHttpHeader('Pragma', 'no-cache');
                    $response->setField('access_token',$bearer_token['access_token']);
                    $response->setField('expires_in',$bearer_token['expires_in']);
                    $response->setField('token_type',$bearer_token['token_type']);
                    $response->setField('scope',$bearer_token['scope']);
                    $response->send();
                }
                catch (LibExceptions\MissingParameter $e) {
                    $app->halt(500, sprintf('Mandatory data is missing, parameter <%s> not set.', $e.paramName()), LibExceptions\MissingParameter::ID);
                }
            }
            // Wrong grant-type
            else
                $app->halt(500, 'Parameter <grant_type> need to match "password", client_credentials, "authorization_code" or "refresh_token".', OAuth2Exceptions\ResponseType::ID);
        });


        /**
         * Route: /v1/oauth2/refresh
         * Description:
         *  Refresh Endpoint, This endpoint allows for exchanging a bearer
         *  token with a long-lasting refresh token.
         *  Note: A client needs the appropriate permission to use this endpoint.
         * Method:
         * Auth:
         * Parameters:
         * Response:
         */
        $app->get('/refresh', '\RESTController\libs\AuthMiddleware::authenticate', function () use ($app) {
            $env = $app->environment();
            $bearerToken = $env['token'];

            // Create new refresh token
            $model = new OAuth2($app, $ilDB);
//            $refreshToken = $model->getRefreshToken($bearerToken);

            $response = new Oauth2Response($app, $ilDB);
            $response->setHttpHeader('Cache-Control', 'no-store');
            $response->setHttpHeader('Pragma', 'no-cache');
            $response->setField('refresh_token',$refreshToken);
            $response->send();
        });


        /**
         * Route: /v1/oauth2/tokeninfo
         * Description:
         *  Token-info route, Tokens obtained via the implicit code grant
         *  MUST by validated by the Javascript client to prevent the
         *  "confused deputy problem".
         * Method:
         * Auth:
         * Parameters:
         * Response:
         */
        $app->get('/tokeninfo', function () use ($app) {
            $model = new OAuth2($app, $ilDB);

            $request = $app->request();

//            $result = $model->tokenInfo($request());

            echo json_encode($result);
        });


    // Enf-Of /oauth2-group
    });


    /**
     * Route: /v1/ilauth/rtoken2bearer
     * Description:
     *  Allows for exchanging an ilias session with a bearer token.
     *  This is used for administration purposes.
     * Method:
     * Auth:
     * Parameters:
     * Response:
     */
    $app->post('/ilauth/rtoken2bearer', function () use ($app) {
        $request = $app->request();

        $model = new OAuth2($app, $ilDB);
//        $model->rToken2Bearer($request);


        $app->response()->header('Content-Type', 'application/json');
        $app->response()->header('Cache-Control', 'no-store');
        $app->response()->header('Pragma', 'no-cache');
        echo json_encode($result); // output-format: {"access_token":"03807cb390319329bdf6c777d4dfae9c0d3b3c35","expires_in":3600,"token_type":"bearer","scope":null}
    });


// Enf-Of /v1-group
});
