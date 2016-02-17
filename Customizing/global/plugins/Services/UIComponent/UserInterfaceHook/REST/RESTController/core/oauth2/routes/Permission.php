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
     * Route: [GET] /v2/oauth2/
     * [Admin required]
     *
     *
     * Returns:
     *
     */
    // Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN),
    $app->get('/permissions/:clientId', function ($clientId) use ($app) {
      try {
        $clientId     = intval($clientId);
        $where        = sprintf('api_id = %d', Database\RESTpermission::quote($clientId, 'integer'));
        $permissions  = Database\RESTpermission::fromWhere($where, null, true);

        $result = array();
        foreach ($permissions as $permission) {
          $row          = $permission->getRow();
          $id           = $row['id'];
          $result[$id]  = $row;
        }

        $app->success($result);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/oauth2/
     * [Admin required]
     *
     *
     * Returns:
     *
     */
    // Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN),
    $app->post('/permission/:clientId', function ($clientId) use ($app) {
      try {
        $request      = $app->request();
        $permissionId = Client::InsertClient($request);
        if ($permissionId)
          $app->success(array( 'id' => $permissionId ));
        else
          $app->halt(500, Client::MSG_EXISTS);
      }

      // Catch missing parameter
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
    });


    /**
     * Route: [GET] /v2/oauth2/
     * [Admin required]
     *
     *
     * Returns:
     *
     */
    $app->delete('/permission/:permisionId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($permissionId) use ($app) {
      // Delete permission via given permissionId
      if (Database\RESTpermission::deleteByPrimary($permissionId) > 0)
        $app->success(array( 'id' => intval($permissionId) ));

      // Deleting failed! (No entry to begin with?)
      else
        $app->halt(500, Client::MSG_NOT_DELETED);
    });
  });
});
