<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// OAuth 2.0 Support
// Server implementations:
// (1) authorization endpoint
// (2) token endpoint
// see http://tools.ietf.org/html/rfc6749
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/*
 * Authorization Endpoint
 * RCF6749: "The authorization endpoint is used by the grant type:
 *  - authorization code grant and
 *  - implicit grant type flows."
 */
$app->post('/v1/oauth2/auth', function () use ($app) {
    $model = new ilOAuth2Model();
    $request = $app->request();
    $response_type = $request->params('response_type');

    if ($response_type == "code"){ // authorization grant
        $model->handleAuthorizationEndpoint_authorizationCode($app);
    } elseif ($response_type == "token") { // implicit grant
        $model->handleAuthorizationEndpoint_implicitGrant($app);
    }
});

/*
 * Authorization Endpoint - GET part.
 *
 * This part covers only the first section of the auth flow and is included here,
 * s.t. clients can initiate the "authorization or implicit grant flow" with a GET request.
 * The flow after calling "oauth2loginform" continues with the POST version of "oauth2/auth".
 */
$app->get('/v1/oauth2/auth', function () use ($app) {
    try {
        $request = $app->request();
        $apikey = $_GET['api_key']; // Issue: Standard ILIAS Init absorbs client_id GET request field
        $client_redirect_uri = $_GET['redirect_uri'];
        $response_type = $_GET['response_type'];

        if ($response_type == "code") {
            if ($apikey && $client_redirect_uri && $response_type){
                $app->render('oauth2loginform.php', array('api_key' => $apikey, 'redirect_uri' => $client_redirect_uri, 'response_type' => $response_type));
            }

        }else if ($response_type == "token") { // implicit grant
            if ($apikey && $client_redirect_uri && $response_type){
                $app->render('oauth2loginform.php', array('api_key' => $apikey, 'redirect_uri' => $client_redirect_uri, 'response_type' => $response_type));
            }
        }
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

/*
 * Token Endpoint
 *
 * Supported grant types:
 *  - Resource Owner(User),
 *  - Client Credentials and
 *  - Authorization Code Grant
 *
 * see http://tools.ietf.org/html/rfc6749
*/
$app->post('/v1/oauth2/token', function () use ($app) {

    $request = new ilRESTRequest($app);
    $model = new ilOAuth2Model();
    $app->log->debug("Entering Token-Endpoint ... GC: ".$request->getParam('grant_type'));
    if ($request->getParam('grant_type') == "password") { // = user credentials grant
        $model->handleTokenEndpoint_userCredentials($app, $request);
    } elseif ($request->getParam('grant_type') == "client_credentials") {
        $model->handleTokenEndpoint_clientCredentials($app, $request);
    } elseif ($request->getParam('grant_type') == "authorization_code") {
        $model->handleTokenEndpoint_authorizationCode($app, $request);
    } elseif ($request->getParam('grant_type') == "refresh_token") {
        $model->handleTokenEndpoint_refreshToken2Bearer($app);
    }

});


/*
 * Refresh Endpoint
 *
 * This endpoint allows for exchanging a bearer token with a ong-lasting refresh token.
 * Note: a client needs the appropriate permission to use this endpoint.
 */
$app->get('/v1/oauth2/refresh', 'authenticate', function () use ($app) {
    $env = $app->environment();
    $uid = ilRESTLib::loginToUserId($env['user']);
    $response = new ilOauth2Response($app);
    global $ilLog;
    $ilLog->write('Requesting new refresh token for user '.$uid);

    // Create new refresh token
    $bearerToken = $env['token'];
    $model = new ilOAuth2Model();
    $refreshToken = $model->getRefreshToken($bearerToken);

    $response->setHttpHeader('Cache-Control', 'no-store');
    $response->setHttpHeader('Pragma', 'no-cache');
    $response->setField("refresh_token",$refreshToken);
    $response->send();
});

/*
 * Token-info route
 *
 * Tokens obtained via the implicit code grant MUST by validated by the Javascript client
 * to prevent the "confused deputy problem".
 */
$app->get('/v1/oauth2/tokeninfo', function () use ($app) {
    $model = new ilOAuth2Model();
    $model->handleTokeninfoRequest($app);
});

/*
 * rtoken2bearer: Allows for exchanging an ilias session with a bearer token.
 * This is used for administration purposes.
 */
$app->post('/v1/ilauth/rtoken2bearer', function () use ($app) {
    $model = new ilOAuth2Model();
    $model->handleRTokenToBearerRequest($app);
});

?>