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
  // Group all admin routes
  $app->group('/admin', function () use ($app) {
    /**
     * Route: [GET] /v2/admin/permissions/:clientId
     * [Admin required]
     *  Returns a list of all available permissions for the given client.
     *
     * Returns:
     *  {
     *    'id': {
     *      'id',       <Number> - Internal id for this permission
     *      'api_id',   <String> - Association between permission and client via api_id = clientId
     *      'pattern',  <String> - Route that the client will have access to
     *      'verb',     <String> - Operation that the client will have access to [GET]/[PUT]/[POST]/[DELETE]
     *    }
     *  }
     */
    $app->get('/permissions/:clientId', function ($clientId) use ($app) {
      try {
        // Fetch permission by given client-id
        $clientId     = intval($clientId);
        $where        = sprintf('api_id = %d', Database\RESTpermission::quote($clientId, 'integer'));
        $permissions  = Database\RESTpermission::fromWhere($where, true);

        // Iterate over all permissions
        $result = array();
        foreach ($permissions as $permission) {
          // Extract permissions and complete table-row
          $row          = $permission->getRow();
          $id           = $row['id'];

          // Insert permission into result
          $result[$id]  = $row;
        }

        // Send list of all permissions
        $app->success($result);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/admin/permission/:clientId
     * [Admin required]
     *  Adds a new permission with given parameters to the selected client.
     *
     * Parameters:
     *  id      <Number> - [Optional] Internal id for this permission
     *  pattern <String> - Route that the client will have access to
     *  verb    <String> - Operation that the client will have access to [GET]/[PUT]/[POST]/[DELETE]
     *
     * Returns:
     *  array(
     *    'id' <Number> - PermissionId of newly created database entry for this permission
     *  )
     */
    $app->post('/permission/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        // Delegate insert to model
        $request      = $app->request();
        $permissionId = Permission::InsertPermission($request);
        if ($permissionId)
          $app->success(array( 'id' => $permissionId ));
        else
          $app->halt(500, Client::MSG_EXISTS);
      }

      // Catch missing parameter
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/admin/permission/:permisionId
     * [Admin required]
     *  Deletes the clients permission with selected permissionId
     *
     * Returns:
     *  array(
     *    'id' <Number> - PermissionId of deleted database entry for this permission
     *  )
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
