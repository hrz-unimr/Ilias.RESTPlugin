<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


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
        $responseType = $request->getParameter('response_type', null, true);
        $redirectUri  = $request->getParameter('redirect_uri');
        $apiSecret    = $request->getParameter('api_secret');
        $apiKey       = $request->getParameter('api_key',       null, true);
        $scope        = $request->getParameter('scope');
        $state        = $request->getParameter('state');
        $remoteIP     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Proccess input-parameters according to (get) authorziation flow (throws exception on problem)
        $data         = Authorize::FlowGetAuthorize($responseType, $iliasClient, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP);

        // Show permission website (should show login)
        Authorize::showWebsite($app, $data);
      }

      // Database query failed (eg. no client with given api-key)
      //  Own catch to send custom error-code
      catch (Libs\Exceptions\Database $e) {
        $e->redirect($redirectUri, 'unauthorized_client');
      }
      // Catches the following exceptions from params()
      //  Catch missing parameters (Libs\Exceptions\Parameter)
      // Catches the following exceptions from FlowGetAuthorize():
      //  Catch unsupported response_type (Exceptions\ResponseType)
      //  Catch invalid request (Exceptions\InvalidRequest)
      //  Catch if access is denied, by user of due to client settings (Exceptions\Denied)
      //  Catch database lookup error (Exceptions\Database)
      //  Invalid oauth2 client credentials (Exceptions\UnauthorizedClient)
      catch (Libs\RESTException $e) {
        $e->redirect($redirectUri);
      }
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
        $responseType = $request->getParameter('response_type', null, true);
        $redirectUri  = $request->getParameter('redirect_uri');
        $apiSecret    = $request->getParameter('api_secret');
        $apiKey       = $request->getParameter('api_key',       null, true);
        $scope        = $request->getParameter('scope');
        $state        = $request->getParameter('state');
        $remoteIP     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Check request-parameters (additional for post)
        $userName     = $request->getParameter('username');
        $passWord     = $request->getParameter('password');
        $answer       = $request->getParameter('answer');

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
        Authorize::LoginFailed(
          $app,
          array(
            'response_type'   => $responseType,
            'redirect_uri'    => $redirectUri,
            'api_key'         => $apiKey,
            'scope'           => $scope,
            'state'           => $state,
            'username'        => $userName,
            'password'        => $passWord
          ),
          $e
        );
      }
      // Catch wrong resource-owner credentials
      catch (Exceptions\Credentials $e) {
        Authorize::LoginFailed(
          $app,
          array(
            'response_type'   => $responseType,
            'redirect_uri'    => $redirectUri,
            'api_key'         => $apiKey,
            'scope'           => $scope,
            'state'           => $state,
            'username'        => $userName,
            'password'        => $passWord
          ),
          $e
        );
      }
      // Database query failed (eg. no client with given api-key)
      //  Own catch to send custom error-code
      catch (Libs\Exceptions\Database $e) {
        $e->redirect($redirectUri, 'unauthorized_client');
      }
      // Catches the following exceptions from params()
      //  Catch missing parameters (Libs\Exceptions\Parameter)
      // Catches the following exceptions from FlowPostAuthorize():
      //  Catch unsupported response_type (Exceptions\ResponseType)
      //  Catch invalid request (Exceptions\InvalidRequest)
      //  Catch if access is denied, by user of due to client settings (Exceptions\Denied)
      //  Catch database lookup error (Exceptions\Database)
      //  Invalid oauth2 client credentials (Exceptions\UnauthorizedClient)
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
     *  grant_type
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
        $grantType    = $request->getParameter('grant_type', null, true);
        $apiSecret    = $request->getParameter('api_secret');
        $remoteIp     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Exchange refresh-token for access-token (and new refresh-token?)
        if ($grantType == 'refresh_token') {
          // Fetch additional parameters for exchange (api-key is optional)
          $apiKey = $request->getParameter('api_key');
          $scope  = $request->getParameter('scope');
          $code   = $request->getParameter('refresh_token', null, true);

          // Proccess input-parameters according to (post) token flow (throws exception on problem)
          $data = Token::FlowRefreshToken($grantType, $apiKey, $apiSecret, $apiCert, $code, $iliasClient, $scope, $remoteIp);
        }

        // Manage all of the other supported grant-types...
        else {
          // Fetch additional parameters for all other requests (api-key is manditory)
          $apiKey = $request->getParameter('api_key', null, true);

          // Check grant_type is supported
          Token::CheckGrantType(null, $grantType);

          // Grant-Type: Authorization Code
          if ($grantType == 'authorization_code') {
            // Fetch additional parameters for Authorization-Code grant flow
            $redirectUri  = $request->getParameter('redirect_uri');
            $code         = $request->getParameter('code', null, true);

            // Proccess input-parameters according to (post) token flow (throws exception on problem)
            $data = Token::FlowAuthorizationCode($grantType, $apiKey, $apiSecret, $apiCert, $code, $redirectUri, $iliasClient, $remoteIp);
          }

          // Grant-Type: Resource-Owner Credentials
          elseif ($grantType == 'password') {
            // Fetch additional parameters for Resource-Owner Credentials grant flow
            $userName = $request->getParameter('username', null, true);
            $passWord = $request->getParameter('password', null, true);
            $scope    = $request->getParameter('scope');

            // Proccess input-parameters according to (post) token flow (throws exception on problem)
            $data = Token::FlowResourceOwnerCredentials($grantType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $scope);
          }

          // Grant-Type: Client Credentials
          elseif ($grantType == 'client_credentials') {
            // Fetch additional parameters for Client Credentials grant flow
            $scope    = $request->getParameter('scope');

            // Proccess input-parameters according to (post) token flow (throws exception on problem)
            $data = Token::FlowClientCredentials($grantType, $apiKey, $apiSecret, $apiCert, $iliasClient, $scope, $remoteIp);
          }
        }

        // Send generated token
        $app->success($data);
      }

      // Catches missing parameter (from params())
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch unsupported response_type (from Flow*())
      catch (Exceptions\ResponseType $e) {
        $e->send(400);
      }
      // Catch invalid request (from Flow*())
      catch (Libs\Exceptions\InvalidRequest $e) {
        $e->send(400);
      }
      //  Catch if access is denied, by user of due to client settings (from Flow*())
      catch (Exceptions\Denied $e) {
        $e->send(401);
      }
      // Catch if given username was invalid (from Flow*())
      catch (Libs\Exceptions\ilUser $e) {
        $e->send(401);
      }
      // Catch if given (auth-code) token was invalid (from Flow*())
      catch (Exceptions\TokenInvalid $e) {
        $e->send(401);
      }
      // Catch invalid oauth2 client authorization/credentials (from Flow*())
      catch (Exceptions\UnauthorizedClient $e) {
        $e->send(401);
      }
      // Catch invalid resource-owner credentials (from Flow*())
      catch (Exceptions\Credentials $e) {
        $e->send(401);
      }
      // Catch database lookup error (from Flow*())
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: /v2/oauth2/token
     *  This endpoint allows a user to invalidate their access- and refresh-token in case they were compromised.
     *  This is not (directly) covered by the oAuth2 RFC (but implementing such functionality is recommended)
     *
     * Parameters:
     *  api_key <String> - Api-Key used to identify requesting client
     *  token <String> - Access- or Refresh-Token that should be removed from database (invalidted)
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     *
     * Returns:
     *  HTTP 1.1/OK 200
     */
    $app->delete('/token', function () use ($app) {
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->getParameter('api_key', null, true);
        $apiSecret    = $request->getParameter('api_secret');
        $tokenCode    = $request->getParameter('token', null, true);
        $remoteIp     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Delete all tokens/sessions that where given
        Misc::FlowDeleteToken($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $accessCode);

        // Show result of all actions
        $app->success(null);
      }

      // Catches missing parameter (from params())
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch unsupported response_type (from Flow*())
      catch (Exceptions\ResponseType $e) {
        $e->send(400);
      }
      // Catch invalid request (from Flow*())
      catch (Libs\Exceptions\InvalidRequest $e) {
        $e->send(400);
      }
      //  Catch if access is denied, by user of due to client settings (from Flow*())
      catch (Exceptions\Denied $e) {
        $e->send(401);
      }
      // Catch if given (auth-code) token was invalid (from Flow*())
      catch (Exceptions\TokenInvalid $e) {
        $e->send(401);
      }
      // Catch invalid oauth2 client authorization/credentials (from Flow*())
      catch (Exceptions\UnauthorizedClient $e) {
        $e->send(401);
      }
      // Catch invalid resource-owner credentials (from Flow*())
      catch (Exceptions\Credentials $e) {
        $e->send(401);
      }
      // Catch database lookup error (from Flow*())
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/oauth2/info
     *  Allows an end-user or client to get information about his refresh- or access-token.
     *  This is not (directly) covered by the oAuth2 RFC (but implementing such functionality is recommended)
     *
     * Parameters:
     *  api_key <String> - Api-Key used to identify requesting client
     *  token <String> - Shows information about this access-token
     *  (At least one of the parameters needs to be given!)
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
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
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->getParameter('api_key', null, true);
        $apiSecret    = $request->getParameter('api_secret');
        $token        = $request->getParameter('token', null, true);
        $remoteIp     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Generate token-info data
        $data         = Misc::FlowTokenInfo($apiKey, $apiSecret, $apiCert, $iliasClient, $remoteIp, $token);
        $app->success($data);
      }

      // Catches missing parameter (from params())
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch invalid request (from Flow*())
      catch (Libs\Exceptions\InvalidRequest $e) {
        $e->send(400);
      }
      //  Catch if access is denied, by user of due to client settings (from Flow*())
      catch (Exceptions\Denied $e) {
        $e->send(401);
      }
      // Catch if given (auth-code) token was invalid (from Flow*())
      catch (Exceptions\TokenInvalid $e) {
        $e->send(401);
      }
      // Catch invalid oauth2 client authorization/credentials (from Flow*())
      catch (Exceptions\UnauthorizedClient $e) {
        $e->send(401);
      }
      // Catch invalid resource-owner credentials (from Flow*())
      catch (Exceptions\Credentials $e) {
        $e->send(401);
      }
      // Catch database lookup error (from Flow*())
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });
  // End-Of /oauth2-group
  });
// End-Of /v2-group
});
