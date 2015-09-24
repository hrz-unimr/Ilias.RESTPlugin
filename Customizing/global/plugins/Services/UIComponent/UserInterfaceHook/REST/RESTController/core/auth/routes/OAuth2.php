<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\auth\Exceptions as AuthExceptions;
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
            try {
                // Fetch request data
                $request = $app->request();

                // Mandatory parameters
                $api_key = $request->params('api_key', null, true);
                $redirect_uri = $request->params('redirect_uri', null, true);
                $response_type = $request->params('response_type', null, true);

                // Optional parameters (will be checked by model)
                $username = $request->params('username');
                $password = $request->params('password');
                $authenticity_token = $request->params('authenticity_token');

                // Proccess with OAuth2-Model
                $model = new AuthEndpoint();
                if ($authenticity_token)
                    $authenticityToken = Token\Generic::fromMixed($model->tokenSettings(), $authenticity_token);
                $result = $model->allGrantTypes($api_key, $redirect_uri, $username, $password, $response_type, $authenticityToken);

                // Process results (send response)
                if ($result['status'] == 'showLogin') {
                    $render = new Util();
                    $render->renderWebsite('REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', $result['data']);
                }
                elseif ($result['status'] == 'showPermission') {
                    $render = new Util();
                    $render->renderWebsite('REST OAuth - Client autorisieren', 'oauth2grantpermissionform.php', $result['data']);
                }
                elseif ($result['status'] == 'redirect')
                    $app->redirect($result['data']);
            }
            catch (AuthExceptions\ResponseType $e) {
                $app->halt(400, $e->getMessage(), $e::ID);
            }
            catch (AuthExceptions\LoginFailed $e) {
                $app->halt(401, $e->getMessage(), $e::ID);
            }
            catch (AuthExceptions\TokenInvalid $e) {
                $app->halt(401, $e->getMessage(), $e::ID);
            }
            catch (LibExceptions\MissingParameter $e) {
                $app->halt(422, $e->getFormatedMessage(), $e::ID);
            }
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
            try {
                // Fetch request data
                $request = $app->request();

                // Mandatory parameters
                $api_key = $request->params('client_id');
                if (is_null($api_key))
                    $api_key = $request->params('api_key', null, true);
                $redirect_uri = $request->params('redirect_uri', null, true);
                $response_type = $request->params('response_type', null, true);

                // Render oauth2login.php for authorization code and implicit grant type flow
                if ($response_type != 'code' && $response_type != 'token')
                    throw new Exceptions\ResponseType(Exceptions\ResponseType::MSG);

                // Display login form
                $render = new Util();
                $render->renderWebsite(
                    'REST OAuth - Login für Tokengenerierung',
                    'oauth2loginform.php',
                    array(
                        'api_key' => $api_key,
                        'redirect_uri' => $redirect_uri,
                        'response_type' => $response_type
                    )
                );
            }
            catch (AuthExceptions\ResponseType $e) {
                $app->halt(400, $e->getMessage(), $e::ID);
            }
            catch (LibExceptions\MissingParameter $e) {
                $app->halt(422, $e->getFormatedMessage(), $e::ID);
            }
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
            try {
                // Get Request & OAuth-Model objects
                $request =  $app->request();
                $model = new TokenEndpoint();
                $grant_type = $request->params('grant_type', null, true);

                // Resource Owner (User) grant type
                if ($grant_type == 'password') {
                    // Fetch request data
                    $api_key = $request->params('api_key', null, true);
                    $user = $request->params('username', null, true);
                    $password = $request->params('password', null, true);
                    $new_refresh = $request->params('new_refresh');

                    // Invoke OAuth2-Model with data
                    $result = $model->userCredentials($api_key, $user, $password, $new_refresh);

                    // Send result
                    $app->response()->disableCache();
                    $app->success($result);
                }
                // grant type
                elseif ($grant_type == 'client_credentials') {
                    // Fetch request data
                    $api_key = $request->params('api_key', null, true);
                    $api_secret = $request->params('api_secret', null, true);

                    // Invoke OAuth2-Model with data
                    $result = $model->clientCredentials($api_key, $api_secret);
                    $app->response()->disableCache();
                    $app->success($result);
                }
                // grant type
                elseif ($grant_type == 'authorization_code') {
                    // Fetch request data (POST-Form instead of body)
                    $api_key = $_POST['api_key'];

                    // Fetch request data
                    $code = $request->params('code', null, true);
                    $api_secret = $request->params('api_secret', null, true);
                    $redirect_uri = $request->params('redirect_uri');
                    $new_refresh = $request->params('new_refresh');

                    // Invoke OAuth2-Model with data
                    $authCodeToken = Token\Generic::fromMixed($model->tokenSettings(), $code);
                    $result = $model->authorizationCode($api_key, $api_secret, $authCodeToken, $redirect_uri, $new_refresh);

                    // Send result
                    $app->response()->disableCache();
                    $app->success($result);
                }
                // grant type
                elseif ($grant_type == 'refresh_token') {
                    // Fetch request data
                    $refresh_token = $request->params('refresh_token', null, true);
                    $new_refresh = $request->params('new_refresh');

                    // Invoke OAuth2-Model with data
                    $refreshToken = Token\Refresh::fromMixed($model->tokenSettings(), $refresh_token);
                    $result = $model->refresh2Access($refreshToken, $new_refresh);

                    // Send result to client
                    if ($result) {
                        $app->response()->disableCache();
                        $app->success($result);
                    }
                    else
                        $app->halt(422, TokenEndpoint::MSG_NO_REFRESH_LEFT, TokenEndpoint::ID_NO_REFRESH_LEFT);
                }
                // Wrong grant-type
                else
                    throw new Exceptions\GrantType(Exceptions\GrantType::MSG);
            }
            catch (AuthExceptions\GrantType $e) {
                $app->halt(400, $e->getMessage(), $e::ID);
            }
            catch (AuthExceptions\LoginFailed $e) {
                $app->halt(401, $e->getMessage(), $e::ID);
            }
            catch (AuthExceptions\TokenInvalid $e) {
                $app->halt(422, $e->getMessage(), $e::ID);
            }
            catch (LibExceptions\MissingParameter $e) {
                $app->halt(422, $e->getFormatedMessage(), $e::ID);
            }
        });


        /**
         * Route: /v1/oauth2/token
         * Description:
         *  This endpoint allows a user to invalidate his refresh-token.
         * Method: DELETE
         * Auth:
         * Parameters:
         * Response:
         */
        $app->delete('/token', function () use ($app) {
            try {
                // Get Request & OAuth-Model objects
                $request = $app->request();
                $model = new RefreshEndpoint();

                // Fetch request data
                $refresh_token = $request->params('refresh_token', null, true);

                // Extract data
                $user_id = $refresh_token->getUserId();
                $api_key = $refresh_token->getApiKey();

                // Invoke OAuth2-Model with data
                $result = $model->deleteToken($user_id, $api_key);

                // Send response
                if ($result > 0)
                    $app->success();
                else
                    $app->halt(500, 'Refresh-Token could not be deleted, no match in Database.');
            }
            catch (AuthExceptions\LoginFailed $e) {
                $app->halt(401, $e->getMessage(), $e::ID);
            }
            catch (AuthExceptions\TokenInvalid $e) {
                $app->halt(422, $e->getMessage(), $e::ID);
            }
            catch (LibExceptions\MissingParameter $e) {
                $app->halt(422, $e->getFormatedMessage(), $e::ID);
            }
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
            try {
                // Fetch token
                $util = new Util();
                $accessToken = $util->getAccessToken();

                // Generate token-information
                $model = new MiscEndpoint();
                $result = $model->tokenInfo($accessToken);

                // Return status-data
                $app->success($result);
            }
            catch (Exceptions\TokenInvalid $e) {
                $app->halt(422, $e->getMessage(), $e::ID);
            }
        });


    // End-Of /oauth2-group
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
        try {
            // Fetch parameters
            $request = $app->request();
            $api_key = $request->params('api_key', null, true);
            $user_id = $request->params('user_id', null, true);
            $rtoken = $request->params('rtoken', null, true);
            $session_id = $request->params('session_id', null, true);

            // Convert userId, rToken and sessionId to bearer-token (using api-key)
            $model = new MiscEndpoint();
            $result = $model->rToken2Bearer($api_key, $user_id, $rtoken, $session_id);

            // Return status-data
            $app->response()->disableCache();
            $app->success($result);
        }
        catch (LibExceptions\TokenInvalid $e) {
            $app->halt(401, $e->getMessage(), $e::ID);
        }
        catch (LibExceptions\MissingParameter $e) {
            $app->halt(422, $e->getFormatedMessage(), $e::ID);
        }
    });


// End-Of /v1-group
});
