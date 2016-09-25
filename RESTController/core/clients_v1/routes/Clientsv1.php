<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\clients_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\clients\Exceptions as ClientExceptions;
use \RESTController\core\auth as Auth;
use \RESTController\core\oauth2_v2 as OAuth2v2;

// Group Version 2 implementation
$app->group('/v1', function () use ($app) {

  /**
   * Route: /v1/clients
   * Returns a list of all REST clients and their settings.
   * Method: GET
   */
  $app->get('/clients', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
    $result = ClientsLegacyModel::getClients();
    $app->success($result);
  });

  /**
   * Route: /v1/clients
   * Updates an existing REST client.
   * Method: PUT
   */
  $app->put('/clients/:id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($id) use ($app) {
    $request = $app->request();
    OAuth2v2\Admin::UpdateClient($id, $request);
    if ($request->hasParameter("permissions")) {
      $permArray  = $request->getParameter(permissions);
      ClientsLegacyModel::setPermissions($id, $permArray);
    }
  });

  /**
   * Route: /v1/clients
   * Creates a new REST ApiKey/Client using the parameters provided with the request.
   * Method: POST
   */
  $app->post('/clients/', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
    $request = $app->request();
    $api_id  = OAuth2v2\Admin::InsertClient($request);
    if ($api_id != false) {
        if ($request->hasParameter("permissions")) {
          $permArray  = $request->getParameter(permissions);
          ClientsLegacyModel::setPermissions($api_id, $permArray);
        }
    }
  });

  /**
   * Route: /clients/:id
   *  :id - Internal client id (api-id) the should be removed
   * Deletes the REST client given by :id (api-id).
   * Method: DELETE
   */
  $app->delete('/clients/:id', RESTAuth::checkAccess(RESTAuth::ADMIN),  function ($id) use ($app) {
    try {
      ClientsLegacyModel::deleteClient($id);

      // Send affirmation status
      $app->success(array('id' => $id));
    }
    catch(ClientExceptions\DeleteFailed $e) {
      $e->send();
    }
  });

});
