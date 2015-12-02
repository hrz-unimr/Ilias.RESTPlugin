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
     *
     */
    $app->get('/authorize', function () use ($app) {
      try {
        // Fetch RESTRequest object
        $request        = $app->request();

        // Fetch (manditory) oauth2 data
        // Note: Against oauth2-RFC, we allow scope and redirect_uri be optional parameters
        $api_key        = $request->params('api_key',       null, true);
        $response_type  = $request->params('response_type', null, true);
        $redirect_uri   = $request->params('redirect_uri');
        $scope          = $request->params('scope',         'PERMISSION');

        // Fetch redirect_uri from client db-entry
        if (!isset($redirect_uri)) {
          // Fetch redirect_uri from client db-entry
          $client       = Database\RESTKeys::fromApiKey($api_key);
          $redirect_uri = $client->getKey('redirect_uri');

          // If no redirect_uri was given and non is attached to the client, exit!
          if (!isset($redirect_uri))
            throw new Libs\Exceptions\Parameter(
              Libs\RESTRequest::MSG_MISSING,
              Libs\RESTRequest::ID_MISSING,
              array(
                'key' => 'redirect_uri'
              )
            );
        }

        // Grant-Type: Authorization-Code
        if ($response_type == 'code') {
          $app->response()->setFormat('HTML');
          $app->render(
            'core/auth/views/authorization.php',
            array(
              'baseURL'       => ILIAS_HTTP_PATH,
              'api_key'       => $api_key,
              'response_type' => $response_type,
              'redirect_uri'  => $redirect_uri,
              'scope'         => $scope
            )
          );
        }

        // Wrong response_type
        else
          $app->halt(422, 'Wront response_type', '');
      }

      // Catch missing parameters and inform client
      catch (Libs\Exceptions\MissingParameter $e) {
        $e->send(400);
      }
    });


    /**
     * Route: [POST] /v1/oauth2/authorize
     */
    $app->post('/authorize', function () use ($app) {
      try {
        // Fetch RESTRequest object
        $request        = $app->request();

        // Fetch (manditory) oauth2 data
        $api_key        = $request->params('api_key',         null, true);
        $response_type  = $request->params('response_type',   null, true);
        $redirect_uri   = $request->params('redirect_uri',    null, true);
        $scope          = $request->params('scope',           null, true);

        //
        $username       = $request->params('username',        null, true);
        $password       = $request->params('password',        null, true);
        $client_id      = $request->params('ilias_client_id', CLIENT_ID);

        //
        if (CLIENT_ID != $client_id)
          $app->halt(422, 'CLIENT_ID mismatch', '');

        //
        if (!Libs\RESTilias::authenticate($username, $password))
          $app->halt(422, 'Wrong resource-owner credentials', '');

        // Throws...
        $userId = Libs\RESTilias::getUserId($username);

        //
        if ($response_type == 'code') {
          //
          $settings       = Tokens\Settings::load('authorization');
          $authorization  = Tokens\Authorization::fromFields($settings, $userId, $client_id, $api_key, $response_type);

          //
          $app->success(array(
            'authorization_token' => $authorization->getTokenString()
          ));
        }

        // Wrong response_type
        else
          $app->halt(422, 'Wrong response_type', '');
      }

      // Catch wrong username (must be case-sensitive)
      catch (Libs\Exceptions\ilUser $e) {
        $e->send(422);
      }

      // Catch missing parameters and inform client
      catch (Libs\Exceptions\MissingParameter $e) {
        $e->send(400);
      }
    });


    /**
     * Route: [POST] /v1/oauth2/token
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
        $redirect_uri = $request->params('redirect_uri',  null, true);

        // Fetch information about client
        $client       = Database\RESTKeys::fromApiKey($api_key);
        if ($client->getKey('api_secret') != $api_secret)
          $app->halt(401, '', '');

        // Generate authorization-token (from given string) and check validity
        $settings       = Tokens\Settings::load('authorization');
        $authorization  = Tokens\Authorization::fromMixed($settings, $code);
        if (!$authorization->isValid())
          $app->halt(401, 'Auth not valid', '');

        //

        $accessSettings   = Tokens\Settings::load('access');
        $refreshSettings  = Tokens\Settings::load('refresh');
        $bearer           = Tokens\Bearer::fromArray($accessSettings, $refreshSettings, $authorization->getTokenArray());

        //
        $app->success($bearer->getResponseObject());
      }

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
