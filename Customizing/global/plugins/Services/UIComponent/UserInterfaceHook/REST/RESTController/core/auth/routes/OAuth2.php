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
use \RESTController\database as Database;


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
     *
     * Returns:
     *  A website where the resource-owner can allow or deny the client access to is resources (via his account)
     *  Since this requires login, the user will be redirected to [POST] /v1/oauth2/authorize.
     */
    $app->get('/authorize', function () use ($app) {
      try {
        // Fetch parameters...
        $request      = $app->request();
        $clientParam  = Authorize::FetchClientParameters($request);

        // Check (and update) client-parameters
        $client       = Database\RESTclient::fromApiKey($clientParam['api_key']);
        $clientParam  = Authorize::CheckClientRequest($client, $clientParam);

        // Show permission website (should show login)
        Authorize::showWebsite($clientParam);
      }

      // Database query failed (eg. no client with given api-key)
      catch (Libs\Exceptions\Database $e) {
        $e->redirect($clientParam['redirect_uri'], 'unauthorized_client');
      }
      // Catch unsupported response_type (Exceptions\ResponseType)
      // Catch invalid request (Exceptions\InvalidRequest)
      // Catch if access is denied, by user of due to client settings (Exceptions\Denied)
      // Catch missing parameters (Libs\Exceptions\Parameter)
      catch (Libs\RESTException $e) {
        $e->redirect($clientParam['redirect_uri']);
      }
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
     *  grant - [Optional] Should be 'allow' or 'deny' (or null) and only be non-null AFTER resource-owner made his decision
     *  client_id - [Optional] Pass (via GET only!) to change the attached ILIAS client-id
     *              (This client_id will always be enforce when using the generated access-token, since it is part of the
     *               resource owner credentials!)
     *
     * Returns:
     *  A website where the resource-owner can allow or deny the client access to is resources (via his account)
     */
    $app->post('/authorize', function () use ($app) {
      try {
        // Fetch parameters...
        $request      = $app->request();
        $clientParam  = Authorize::FetchClientParameters($request);
        $owner        = Authorize::FetchResourceOwnerCredentials($request);

        // Check (and update) client-parameters
        $clientParam  = Authorize::CheckClientRequest($clientParam);
        $owner        = Authorize::CheckResourceOwnerCredentials($owner);

        // Combine all parameters
        $grant        = $request->params('grant', null);
        $param        = array_merge($clientParam, $owner, array('grant' => $grant));

        // Either redirect user-agent (access was granted or denied) back to client...
        if (isset($parameters['grant']))
          Authorize::RedirectUserAgent($app, $param);

        // ... or display website to ask to allow/deny the client access
        else
          Authorize::AskPermission($app, $param);
      }

      // Catch wrong resource-owner username (case-sensitive)
      catch (Libs\Exceptions\ilUser $e) {
        Authorize::LoginFailed($app, $clientParam, $e);
      }
      // Catch wrong resource-owner credentials
      catch (Exception\Credentials $e) {
        Authorize::LoginFailed($app, $clientParam, $e);
      }
      // Database query failed (eg. no client with given api-key)
      catch (Libs\Exceptions\Database $e) {
        $e->redirect($clientParam['redirect_uri'], 'unauthorized_client');
      }
      // Catch unsupported response_type
      // Catch invalid request
      // Catch if access is denied (by user of due to client settings)
      // Catch missing parameters
      catch (Libs\RESTException $e) {
        $e->redirect($clientParam['redirect_uri']);
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
        // Fetch RESTRequest object
        $request        = $app->request();

        // Fetch (manditory) oauth2 data
        $api_key      = $request->params('api_key',       null, true);
        $api_secret   = $request->params('api_secret',    null, true);
        $grant_type   = $request->params('grant_type',    null, true);
        $code         = $request->params('code',          null, true);
        $redirect_uri = $request->params('redirect_uri'); // Unused?!

        // Fetch information about client
        $client       = Database\RESTclient::fromApiKey($api_key);
        if ($client->getKey('api_secret') != $api_secret)
          $app->halt(401, 'API-Secrets mismatch', '');

        // Grant-Type: Authorization Code
        if ($grant_type == 'authorization_code') {
          // Generate authorization-token (from given string) and check validity
          $settings       = Tokens\Settings::load('authorization');
          $authorization  = Tokens\Authorization::fromMixed($settings, $code);
          if ($authorization->isExpired())
            $app->halt(401, 'Auth invalid or expired', '');

          if ($authorization->getApiKey() != $api_key)
            $app->halt(401, 'API-Key mismatch', '');

          //
          $accessSettings   = Tokens\Settings::load('access');
          $refreshSettings  = Tokens\Settings::load('refresh');
          $userId           = $authorization->getUserId();
          $iliasClient      = $authorization->getIliasClient();
          $scope            = $authorization->getScope();
          $bearer           = Tokens\Bearer::fromFields($accessSettings, $refreshSettings, $userId, $iliasClient, $apiKey, $scope);

          //
          $app->success($bearer->getResponseObject());

          /**

          Alle Routen:
            * IP restriction prüfen                   [Auth: X]
            * User-Restriction prüfen                 [Auth: X]
            * Check client with api-key exists        [Auth: X]
            * Check if client has grant-type enabled  [Auth: X]

          Token-Endpoint - Auth-Code:
            * check client-credentials
            * check auth-code values with client-parameters (eg. api-key, redirect_uri, etc.)
            * check auth-code not expired (look up in DB, delete afterwards!)
            * send access-token, send and store refresh-token
            * SCOPE steckt im Auth-TOKEN

          **/
        }

        // Unsupported grant-type
        else
          $app->halt(422, 'Wrong grant_type', '');
      }

      // TokenInvalid
      // ilUser

      // Catch missing parameters and inform client
      catch (Libs\Exceptions\MissingParameter $e) {
        $e->send(400);
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
    $app->get('/info', function () use ($app) {  });


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
