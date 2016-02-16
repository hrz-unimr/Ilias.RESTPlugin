<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\oauth2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


// Group Version 2 implementation
$app->group('/v2', function () use ($app) {
  // Group all oAuth2 (RFC) routes
  $app->group('/oauth2', function () use ($app) {
    /**
     * Route: [GET] /v2/oauth2/client
     *
     *
     * Parameters:
     *
     *
     * Returns:
     *
     */
    $app->get('/client', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
      try {
        $clients = Database\RESTclient::fromWhere(null, null, true);

        $result = array();
        foreach($clients as $client) {
          $id           = $client->getKey('id');
          $row          = $client->getRow();
          $result[$id]  = array_filter($row, function($value) { return !is_null($value); });
        }

        $app->success($result);
      }
      catch (Libs\Exceptions\Database $e) {
        $app->success(null);
      }
    });


    /**
     * Route: [GET] /v2/oauth2/client/:clientId
     *
     *
     * Parameters:
     *
     *
     * Returns:
     *
     */
    $app->get('/client/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        $client = Database\RESTclient::fromPrimary($clientId);
        $row    = $client->getRow();

        $app->success(array_filter($row, function($value) { return !is_null($value); }));
      }

      //
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [POST] /v2/oauth2/client
     *
     *
     * Parameters:
     *
     *
     * Returns:
     *
     */
    $app->post('/client', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
      try {
        $request  = $app->request();
        $row      = array(
          'id'                          => $request->params('id',                         null),
          'api_key'                     => $request->params('api_key',                    null, true),
          'api_secret'                  => $request->params('api_secret',                 null),
          'cert_serial'                 => $request->params('cert_serial',                null),
          'cert_issuer'                 => $request->params('cert_issuer',                null),
          'cert_subject'                => $request->params('cert_subject',               null),
          'redirect_uri'                => $request->params('redirect_uri',               null),
          'consent_message'             => $request->params('consent_message',            null),
          'client_credentials_userid'   => $request->params('client_credentials_userid',  6),
          'grant_client_credentials'    => $request->params('grant_client_credentials',   false),
          'grant_authorization_code'    => $request->params('grant_authorization_code',   false),
          'grant_implicit'              => $request->params('grant_implicit',             false),
          'grant_resource_owner'        => $request->params('grant_resource_owner',       false),
          'refresh_authorization_code'  => $request->params('refresh_authorization_code', false),
          'refresh_resource_owner'      => $request->params('refresh_resource_owner',     false),
          'grant_bridge'                => $request->params('grant_bridge',               false),
          'ips'                         => $request->params('ips',                        null),
          'users'                       => $request->params('users',                      null),
          'scopes'                      => $request->params('scopes',                     null),
          'description'                 => $request->params('description',                null),
        );

        $client = Database\RESTclient::fromRow($row);
        $id     = $row['id'];

        if ($id == null || !Database\RESTclient::existsByPrimary($id)) {
          $client->insert($id == null);
          $app->success(array( 'id' => $client->getKey('id') ));
        }
        else
          $app->halt(500, 'Exists!');
      }

      // Catch missing parameters (Libs\Exceptions\Parameter)
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [PUT] /v2/oauth2/client | /v2/oauth2/client/:clientId
     *
     *
     * Parameters:
     *
     *
     * Returns:
     *
     */
    $app->put('/client', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
      try {
        $request  = $app->request();
        $clientId = $request->params('id', null, true);
        $updated  = Client::UpdateClient($clientId, $request);

        if ($updated)
          $app->success(array( 'id' => $clientId ));
        else
          $app->halt(500, Client::MSG_NOT_UPDATED);
      }

      // Catch missing parameters (Libs\Exceptions\Parameter)
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });
    $app->put('/client/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        $request  = $app->request();
        $updated  = Client::UpdateClient($clientId, $request);

        if ($updated)
          $app->success(array( 'id' => $clientId ));
        else
          $app->halt(500, Client::MSG_NOT_UPDATED);
      }

      // Catch missing parameters (Libs\Exceptions\Parameter)
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [DELETE] /v2/oauth2/client | /v2/oauth2/client/:clientId
     *
     *
     * Parameters:
     *
     *
     * Returns:
     *
     */
    $app->delete('/client', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
      try {
        $clientId = $request->params('id', null, true);

        if (Database\RESTclient::deleteByPrimary($clientId) > 0)
          $app->success(array( 'id' => intval($clientId) ));
        else
          $app->halt(500, Client::MSG_NOT_DELETED);
      }

      // Catch missing parameters (Libs\Exceptions\Parameter)
      catch (Libs\RESTException $e) {
        $e->send(500);
      }
    });
    $app->delete('/client/:id', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      if (Database\RESTclient::deleteByPrimary($clientId) > 0)
        $app->success(array( 'id' => intval($clientId) ));
      else
        $app->halt(500, Client::MSG_NOT_DELETED);
    });
  });
});


// TODO:
//  - Permissions
//  - Config
