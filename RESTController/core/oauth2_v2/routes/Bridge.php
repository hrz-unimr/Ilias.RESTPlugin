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
use \RESTController\libs     as Libs;


// Group Version 2 implementation
$app->group('/v2', function () use ($app) {
  // Group routes connecting oAuth2 <-> ILIAS
  $app->group('/bridge', function () use ($app) {
    /**
     * Route: [POST] /v2/bridge/ilias
     *  Allows for exchanging an active ILIAS session for an oauth2 token.
     *
     * Parameters:
     *  api_key <String> - Api-Key used to identify requesting client
     *  scope <String> - [Optional] Requested scope for access-token
     *  user <Integer> - ILIAS user id of session to check [$ilUser->getId()]
     *  token <String> - Request-Token of session to check [$ilCtrl->rtoken]
     *  session <String> - PHP Session-ID of session to check [session_id()]
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     *
     * Returns:
     *  {
     *   "access_token": <String> - Generated access-token allowing access to certain scopes/routes
     *   "refresh_token": null
     *   "expires_in": <Integer> - Number of seconds until access-token expires
     *   "token_type": "Bearer" (Only tokens of type Bearer are supported)
     *   "scope": <String> - Space separated list of allowed scopes for the given access-token
     *  }
     */
    $app->post('/ilias', function () use ($app) {
      try {
        $app->log->debug('/v2/bridge/ilias ');
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->getParameter('api_key', null, true);
        $apiSecret    = $request->getParameter('api_secret');
        $scope        = $request->getParameter('scope');
        $userId       = $request->getParameter('user', null, true);
        $token        = $request->getParameter('token', null, true);
        $sessionID    = $request->getParameter('session', null, true);
        $remoteIp     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Proccess input-parameters to generate access-token
        $data = Misc::FlowFromILIAS($apiKey, $apiSecret, $apiCert, $userId, $token, $sessionID, $iliasClient, $remoteIp, $scope);

        // Send generated token
        $app->success($data);
      }

      // Catches missing parameter (from params())
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch invalid request (from Flow*())
      catch (Exceptions\InvalidRequest $e) {
        $e->send(400);
      }
      //  Catch if access is denied, by user of due to client settings (from Flow*())
      catch (Exceptions\Denied $e) {
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
     * Route: [POST] /v2/bridge/oauth2
     *  Allows for exchanging an oauth2 token for a new ILIAS session.
     *  Note: This INTENTIONALLY deletes all existing ILIAS sessions!
     *        (See Libs\RESTilias::createSession(...) to disable this.)
     *
     * Parameters:
     *  api_key <String> - Api-Key used to identify requesting client
     *  access_token <String> - The access-token that should be exchanged for a new ILIAS session
     *  goto <String> - Goto link (relative to index.php of current ILIAS instance) after successful creation of new ILIAS sessions
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     *
     * Returns:
     * [Without goto]
     *  {
     *    "cookies": <String> - Cookie data that can be given to a user-agent to connect to the given session
     *  }
     * [With goto]
     *  HTTP 1.1/Temporary Redirect 303
     *  set-cookies: PHPSESSID ...
     *  set-cookies: authchallenge ...
     *  -> ILIAS_HTTP_PATH . $goto
     */
    $app->post('/oauth2', function () use ($app) {
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->getParameter('api_key', null, true);
        $apiSecret    = $request->getParameter('api_secret');
        $accessCode   = $request->getParameter('access_token', null, true);
        $goto         = $request->getParameter('goto');
        $remoteIp     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Proccess input-parameters to generate access-token
        $cookies = Misc::FlowFromOAUTH($apiKey, $apiSecret, $apiCert, $accessCode, $iliasClient, $remoteIp);

        // Redirect somewhere? (usefull if accessing via a user-agent)
        if (isset($goto)) {
          // Send cookie data
          foreach ($cookies as $cookie)
            $app->setCookie($cookie['key'], $cookie['value'], $cookie['expires'], $cookie['path']);

          // Direct to target (make sure its always relative to own ILIAS)
          $app->response()->redirect(ILIAS_HTTP_PATH . '/' . $goto, 303);
        }

        // Transmit cookie-information instead
        else
          $app->success(array(
            'cookies' => $cookies
          ));
      }

      // Catches missing parameter (from params())
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch invalid request (from Flow*())
      catch (Exceptions\InvalidRequest $e) {
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
      // Catch database lookup error (from Flow*())
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [DELETE] /v2/bridge/session
     *  Destroys an existing ILIAS-Session.
     *
     * Parameters:
     *  api_key <String> - Api-Key used to identify requesting client
     *  user <Integer> - Destroys ILIAS session for this user (requires user, token and session parameter)
     *  token <String> - Destroys ILIAS session for this token (requires user, token and session parameter)
     *  session <String> - Destroys ILIAS session for this session (requires user, token and session parameter)
     *  <Client-Credentials> - IFF the client is confidential (has a api_secret or crt_* stored)
     *                         This includes either a valid api_secret or a ssl client-certificate.
     *
     * Returns:
     *  HTTP 1.1/OK 200
     */
    $app->delete('/session', function () use ($app) {
      try {
        // Fetch parameters required for all routes
        $request      = $app->request();
        $apiKey       = $request->getParameter('api_key', null, true);
        $apiSecret    = $request->getParameter('api_secret');
        $userId       = $request->getParameter('user', null, true);
        $token        = $request->getParameter('token', null, true);
        $sessionID    = $request->getParameter('session', null, true);
        $remoteIp     = $request->getIp();
        $apiCert      = Libs\RESTLib::FetchClientCertificate();
        $iliasClient  = Libs\RESTilias::FetchILIASClient();

        // Check client-credentials etc. and delete session afterwards
        Common::FlowDeleteSession($apiKey, $apiSecret, $apiCert, $remoteIp, $userId, $token, $sessionID);

        // Show result of all actions
        $app->success(null);
      }

      // Catches missing parameter (from params())
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch invalid request (from Flow*())
      catch (Exceptions\InvalidRequest $e) {
        $e->send(400);
      }
      //  Catch if access is denied, by user of due to client settings (from Flow*())
      catch (Exceptions\Denied $e) {
        $e->send(401);
      }
      // Catch invalid oauth2 client authorization/credentials (from Flow*())
      catch (Exceptions\UnauthorizedClient $e) {
        $e->send(401);
      }
      // Catch database lookup error (from Flow*())
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });
  });
  // End-Of /bridge-group
// End-Of /v2-group
});
