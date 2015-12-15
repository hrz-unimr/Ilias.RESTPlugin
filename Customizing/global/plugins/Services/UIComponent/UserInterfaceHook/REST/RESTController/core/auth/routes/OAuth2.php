<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs     as Libs;


// Group Version 2 implementation
$app->group('/v2', function () use ($app) {
  // Group all oAuth2 (RFC) routes
  $app->group('/oauth2', function () use ($app) {
    /**
     * Route: [GET] /v2/oauth2/authorize
     *  Implementation of the OAuth2 'Authorization Flow'. This route manages the
     *  step (A) for both 'Authorization-Code Grant' and the 'Implicit Grant'.
     *
     *  See https://tools.ietf.org/html/rfc6749#section-4 for more information.
     *
     * Parameters:
     *  api_key - OAuth2 client-id(entification) of client application
     *  response_type - Needs to be code for 'authorization-code grant' or 'token' for implicit grant
     *  redirect_uri - [Optional] URL where the user-agent should be redirected to after successfull/denied authorization
     *                 (If no default value is given for this client application, this value is MANDITORY!)
     *  scope - [Optional] Scope of requested access-token
     *  state - [Optional] State of client-application during authorization request
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     *
     * Returns:
     *  A website where the resource-owner can allow or deny the client access to is resources (via his account)
     *  Since this requires login, the user will be redirected to [POST] /v2/oauth2/authorize.
     */
    $app->get('/authorize', function () use ($app) {
      try {
        // Check request-parameters
        $request      = $app->request();
        $responseType = $request->params('response_type', null, true);
        $redirectUri  = $request->params('redirect_uri');
        $apiSecret    = $request->params('api_secret');
        $apiKey       = $request->params('api_key',       null, true);
        $scope        = $request->params('scope');
        $state        = $request->params('state');
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $remoteIP     = Libs\RESTLib::FetchUserAgentIP();

        // Proccess input-parameters according to (get) authorziation flow (throws exception on problem)
        $data         = Authorize::FlowGetAuthorize($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP);

        // Show permission website (should show login)
        Authorize::showWebsite($app, $data);
      }

      // Database query failed (eg. no client with given api-key)
      catch (Libs\Exceptions\Database $e) {
        $e->redirect($redirectUri, 'unauthorized_client');
      }
      // Catch unsupported response_type (Exceptions\ResponseType)
      // Catch invalid request (Exceptions\InvalidRequest)
      // Catch if access is denied, by user of due to client settings (Exceptions\Denied)
      // Catch missing parameters (Libs\Exceptions\Parameter)
      catch (Libs\RESTException $e) {
        $e->redirect($redirectUri);
      }

      // invalid api-key
      // Different exception for missing and wrong client-auth?
    });


    /**
     * Route: [POST] /v2/oauth2/authorize
     *  Implementation of the OAuth2 'Authorization Flow'. This route manages the
     *  steps (B) and (C) for both 'Authorization-Code Grant' and the 'Implicit Grant'.
     *
     *  See https://tools.ietf.org/html/rfc6749#section-4 for more information.
     *
     * Parameters:
     *  api_key - OAuth2 client-id(entification) of client application
     *  response_type - Needs to be code for 'authorization-code grant' or 'token' for implicit grant
     *  redirect_uri - [Optional] URL where the user-agent should be redirected to after successfull/denied authorization
     *                 (If no default value is given for this client application, this value is MANDITORY!)
     *  scope - [Optional] Scope of requested access-token
     *  state - [Optional] State of client-application during authorization request
     *  username - [Optional] Username of resource-owner, required to show 'allow/deny access to client'-Website
     *             (If omited a login dialog will be displayed)
     *  password - [Optional] Password of resource-owner, required to show 'allow/deny access to client'-Website
     *             (If omited a login dialog will be displayed)
     *  answer - [Optional] Should be 'allow' or 'deny' (or null) and only be non-null AFTER resource-owner made his decision
     *  client_id - [Optional] Pass (via GET only!) to change the attached ILIAS client-id
     *              (This client_id will always be enforce when using the generated access-token, since it is part of the
     *               resource owner credentials!)
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     *
     * Returns:
     *  A website where the resource-owner can allow or deny the client access to is resources (via his account)
     */
    $app->post('/authorize', function () use ($app) {
      try {
        // Check request-parameters (same as get)
        $request  = $app->request();
        $responseType = $request->params('response_type', null, true);
        $redirectUri  = $request->params('redirect_uri');
        $apiSecret    = $request->params('api_secret');
        $apiKey       = $request->params('api_key',       null, true);
        $scope        = $request->params('scope');
        $state        = $request->params('state');
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $remoteIP     = Libs\RESTLib::FetchUserAgentIP();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Check request-parameters (additional for post)
        $userName     = $request->params('username');
        $passWord     = $request->params('password');
        $answer       = $request->params('answer');

        // Proccess input-parameters according to (post) authorziation flow (throws exception on problem)
        $data         = Authorize::FlowPostAuthorize($responseType, $iliasClient, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP, $answer);

        // Either redirect user-agent (access was granted or denied) back to client...
        if (isset($answer)) {
          Authorize::RedirectUserAgent($app, $data);
          Common::DatabaseCleanup();
        }

        // ... or display website to ask to allow/deny the client access
        else
          Authorize::AskPermission($app, $data);
      }

      // Catch wrong resource-owner username (case-sensitive)
      catch (Libs\Exceptions\ilUser $e) {
        Authorize::LoginFailed($app, $params, $e);
      }
      // Catch wrong resource-owner credentials
      catch (Exceptions\Credentials $e) {
        Authorize::LoginFailed($app, $params, $e);
      }
      // Database query failed (eg. no client with given api-key)
      catch (Libs\Exceptions\Database $e) {
        $e->redirect($redirectUri, 'unauthorized_client');
      }
      // Catch unsupported response_type
      // Catch invalid request
      // Catch if access is denied (by user of due to client settings)
      // Catch missing parameters
      catch (Libs\RESTException $e) {
        $e->redirect($redirectUri);
      }
    });


    /**
     * Route: [POST] /v2/oauth2/token
     *  Implementation of the OAuth2 'Authorization Flow'. This route manages:
     *   1) the steps (D) and (E) for 'Authorization-Code Grant'.
     *   2) the steps (B) and (C) for 'Resource Owner (Password Credentials) Grant'
     *   3) the steps (A) and (B) for 'Client Credentials Grant'
     *
     *  See https://tools.ietf.org/html/rfc6749#section-4 for more information.
     *
     * Parameters:
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     * Returns:
     *  {
     *   "access_token": <String> - Generated access-token allowing access to certain scopes/routes
     *   "refresh_token": <String> - Generated refresh-token that has a longer ttl than an access-token and can be used to create new access-tokens
     *   "expires_in": <Integer> - Number of seconds until access-token expires
     *   "token_type": "Bearer" (Only tokens of type Bearer are supported)
     *   "scope": <String> - Space separated list of allowed scopes for the given access-token
     *  }
     */
    $app->post('/token', function () use ($app) {
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $grantType    = $request->params('grant_type', null, true);
        $apiSecret    = $request->params('api_secret');
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $remoteIp     = Libs\RESTLib::FetchUserAgentIP();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Exchange refresh-token for access-token (and new refresh-token?)
        if ($grantType == 'refresh_token') {
          // Fetch additional parameters for exchange (api-key is optional)
          $apiKey = $request->params('api_key');
          $scope  = $request->params('scope');
          $code   = $request->params('refresh_token', null, true);

          // Proccess input-parameters according to (post) token flow (throws exception on problem)
          $data = Token::FlowRefreshToken($grantType, $apiKey, $apiSecret, $apiCert, $code, $iliasClient, $scope, $remoteIp);
        }

        // Manage all of the other supported grant-types...
        else {
          // Fetch additional parameters for all other requests (api-key is manditory)
          $apiKey = $request->params('api_key', null, true);

          // Check grant_type is supported
          Token::CheckGrantType(null, $grantType);

          // Grant-Type: Authorization Code
          if ($grantType == 'authorization_code') {
            // Fetch additional parameters for Authorization-Code grant flow
            $redirectUri  = $request->params('redirect_uri');
            $code         = $request->params('code', null, true);

            // Proccess input-parameters according to (post) token flow (throws exception on problem)
            $data = Token::FlowAuthorizationCode($grantType, $apiKey, $apiSecret, $apiCert, $code, $redirectUri, $iliasClient, $remoteIp);
          }

          // Grant-Type: Resource-Owner Credentials
          elseif ($grantType == 'password') {
            // Fetch additional parameters for Resource-Owner Credentials grant flow
            $userName = $request->params('username', null, true);
            $passWord = $request->params('password', null, true);
            $scope    = $request->params('scope');

            // Proccess input-parameters according to (post) token flow (throws exception on problem)
            $data = Token::FlowResourceOwnerCredentials($grantType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $scope);
          }

          // Grant-Type: Client Credentials
          elseif ($grantType == 'client_credentials') {
            // Fetch additional parameters for Client Credentials grant flow
            $scope    = $request->params('scope');

            // Proccess input-parameters according to (post) token flow (throws exception on problem)
            $data = Token::FlowClientCredentials($grantType, $apiKey, $apiSecret, $apiCert, $iliasClient, $scope, $remoteIp);
          }
        }

        // Send generated token
        Common::DatabaseCleanup();
        $app->success($data);
      }

      // Catch all generated exceptions
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });


    /**
     * Route: /v2/oauth2/token
     *  This endpoint allows a user to invalidate their access- and refresh-token in case they were compromised.
     *  This is not (directly) covered by the oAuth2 RFC (but implementing such functionality is recommended)
     *
     * Parameters:
     *  access_token <String> - [Optional] Access-Token that should be removed from database (invalidted)
     *  refresh_token <String> - [Optional] Refresh-Token that should be removed from database (invalidted)
     *
     * Returns:
     *  HTTP 1.1/OK
     */
    $app->delete('/token', function () use ($app) {
      // Fetch parameters required for all routes
      $request      = $app->request();
      $accessCode   = $request->params('access_token');
      $refreshCode  = $request->params('refresh_token');

      // Delete all tokens/sessions that where given
      if (isset($accessCode))
        Misc::DeleteAccessToken($accessCode);
      if (isset($refreshCode))
        Misc::DeleteRefreshToken($refreshCode);

      // Show result of all actions
      $app->success(null);
    });


    /**
     * Route: [GET] /v2/oauth2/info
     *  Allows an end-user or client to get information about his refresh- or access-token.
     *  This is not (directly) covered by the oAuth2 RFC (but implementing such functionality is recommended)
     *
     * Parameters:
     *  access_token <String> - [Optional] Shows information about this access-token
     *  refresh_token <String> - [Optional] Shows information about this refresh-token
     *  (At least one of the parameters needs to be given!)
     *
     * Returns:
     *  {
     *    "user_id": <Integer> - ILIAS User-Id of the attached resource-owner
     *    "user_name": <String> - ILIAS user-Name of the attached resource-owner
     *    "ilias_client": <String> - ILIAS client_id of the attached resource-owner
     *    "api_key": <String> - API-Key used to generate this token
     *    "scope": <String> - Scope attached to this token
     *    "misc": <String> - Misc information attached to this token
     *    "expires": <String> - Date (Server-Timer) when the token will expire
     *    "ttl": <Integer> - Number of seconds till the token expires
     *  }
     */
    $app->get('/info', function () use ($app) {
      // Fetch parameters required for all routes
      $request  = $app->request();
      $access   = $request->params('access_token');
      $refresh  = $request->params('refresh_token');

      // Generate token-info data
      $data     = Misc::GetToken($access, $refresh);
      $app->success($data);
    });
  // End-Of /oauth2-group
  });


  // Group routes connecting oAuth2 <-> ILIAS
  $app->group('/bridge', function () use ($app) {
    /**
     * Route: [POST] /v2/bridge/ilias
     *  Allows for exchanging an active ilias session for an oauth2 token.
     *
     * Parameters:
     *
     * Returns:
     */
    $app->post('/ilias', function () use ($app) {
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->params('api_key', null, true);
        $apiSecret    = $request->params('api_secret');
        $scope        = $request->params('scope');
        $userId       = $request->params('user', null, true);
        $token        = $request->params('token', null, true);
        $sessionID    = $request->params('session', null, true);
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $remoteIp     = Libs\RESTLib::FetchUserAgentIP();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Proccess input-parameters to generate access-token
        $data = Misc::FlowFromILIAS($apiKey, $apiSecret, $apiCert, $userId, $token, $sessionID, $iliasClient, $remoteIp, $scope);

        // Send generated token
        $app->success($data);
      }

      // Catch all generated exceptions
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [POST] /v2/bridge/oauth2
     *  Allows for exchanging an oauth2 token for a new ILIAS session.
     *  Note: This INTENTIONALLY deletes all existing ILIAS sessions!
     *        (See Libs\RESTilias::createSession(...) to disable this.)
     *
     * Parameters:
     *
     * Returns:
     */
    $app->post('/oauth2', function () use ($app) {
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->params('api_key', null, true);
        $apiSecret    = $request->params('api_secret');
        $accessCode   = $request->params('access_token', null, true);
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $remoteIp     = Libs\RESTLib::FetchUserAgentIP();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();
        $goto         = $request->params('goto');

        // Proccess input-parameters to generate access-token
        $cookies = Misc::FlowFromOAUTH($apiKey, $apiSecret, $apiCert, $accessCode, $iliasClient, $remoteIp, $scope);

        // Redirect somewhere? (usefull if accessing via a user-agent)
        if (isset($goto)) {
          // Send cookie data
          foreach ($cookies as $cookie)
            $app->setCookie($cookie['key'], $cookie['value'], $cookie['expires'], $cookie['path']);

          // Direct to target (make sure its always relative to own ILIAS)
          $app->response()->redirect(ILIAS_HTTP_PATH . $goto, 303);
        }

        // Transmit cookie-information instead
        else
          $app->success(array(
            'cookies' => $cookies
          ));
      }

      // Catch all generated exceptions
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [DELETE] /v2/bridge/ilias
     *  Destroys an existing ILIAS-Session.
     *
     * Parameters:
     *  user <Integer> - Destroys ILIAS session for this user (requires user, token and session parameter)
     *  token <String> - Destroys ILIAS session for this token (requires user, token and session parameter)
     *  session <String> - Destroys ILIAS session for this session (requires user, token and session parameter)
     *
     * Returns:
     *  HTTP 1.1/OK
     */
    $app->delete('/session', function () use ($app) {
      // Fetch parameters required for all routes
      $request      = $app->request();
      $userId       = $request->params('user', null, true);
      $token        = $request->params('token', null, true);
      $sessionID    = $request->params('session', null, true);

      // Destroy given ILIAS session
      Libs\RESTilias::deleteSession($userId, $token, $sessionID);

      // Show result of all actions
      $app->success(null);
    });
  });
  // End-Of /bridge-group
// End-Of /v2-group
});
