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


// Group as version-1 implementation
$app->group('/v1', function () use ($app) {
  // Group as oauth2 implementation
  $app->group('/oauth2', function () use ($app) {
    /**
     * Route: [GET] /v1/oauth2/authorize
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
     *  Since this requires login, the user will be redirected to [POST] /v1/oauth2/authorize.
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
     * Route: [POST] /v1/oauth2/authorize
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
     * Route: [POST] /v1/oauth2/token
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

            // Clear expired Authorization-Code tokens
            Common::DatabaseCleanup();
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
     * Route: /v1/oauth2/token
     *  This endpoint allows a user to invalidate his refresh-token.
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->delete('/token', function () use ($app) { });


    /**
     * Route: [GET] /v1/oauth2/tokeninfo
     *  Token-info route, Tokens obtained via the implicit code grant
     *  MUST by validated by the Javascript client to prevent the
     *  "confused deputy problem".
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->get('/info', function () use ($app) {
      // Fetch parameters required for all routes
      $request      = $app->request();
      $grantType    = $request->params('grant_type', null, true);
    });


    /**
     * Route: [POST] /v1/ilauth/ilias2bearer
     *  Allows for exchanging an ilias session with a bearer token.
     *  This is used for administration purposes.
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->post('/ilias', function () use ($app) { });
  // End-Of /oauth2-group
  });
// End-Of /v1-group
});
