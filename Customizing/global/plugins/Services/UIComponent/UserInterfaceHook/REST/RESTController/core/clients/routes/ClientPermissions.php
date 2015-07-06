<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\clients\Exceptions as ClientExceptions;
use \RESTController\core\auth as Auth;
// Requires <$app = \RESTController\RESTController::getInstance()>

/**
 * Route clientpermissions
 * Description:
 *  Creates a new client permission with the route and verb parameter pair.
 *  Also returns the new permission id (perm-id).
 * Method: POST
 * Auth: authenticateTokenOnly
 * Parameters:
 *  {
 *    api_key: "<API-Key for new client>",
 *
 *  }
 * Response:
 *  {
 *    [id: <Internal id (perm-id)>, route, verb],
 *    status: "<Success or Failure>"
 *  }
 */
 $app->get('/clientpermissions', '\RESTController\libs\OAuth2Middleware::TokenAuth', function () use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user)) {
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);
    }

     $request = $app->request;
     // Try/Catch all required inputs
     try {
         $api_key = $request->params('api_key', null, true);
     } catch(LibExceptions\MissingParameter $e) {
         $app->halt(422, $e->getFormatedMessage(), $e::ID);
     }

    // Use the model class to fetch data
    $model = new Clients();
    $data = $model->getPermissionsForApiKey($api_key);

    // Prepare data
    $result = array();
    $result['permissions'] = $data;

    // Send data
    $app->success($result);
});


/**
 * Route: /clientpermissions
 * Description:
 *  Creates a new permission statement.
 *  Also returns the new permission id (perm-id).
 * Method: POST
 * Auth: authenticateTokenOnly
 * Parameters:
 *  {
 *    api_key: "<API-Key of a client>",
 *    pattern: "Name of the route",
 *    verb: "Action, e.g. GET, PUT,..."
 *
 *  }
 * Response:
 *  {
 *    id: <Internal id (perm-id) of new permission statement>,
 *    status: "<Success or Failure>"
 *  }
 */
$app->post('/clientpermissions/', '\RESTController\libs\OAuth2Middleware::TokenAuth', function () use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if authorized user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user)) {
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);
    }


    // Shortcut for request object
    $request = $app->request();

    //$app->log->debug('in post clientpermissions');
    //$app->log->debug(print_r($request->getBody(),true));

    // Try/Catch all required inputs
    try {
        $api_key = $request->params('api_key', null, true);
    } catch(LibExceptions\MissingParameter $e) {
        $app->halt(422, $e->getFormatedMessage(), $e::ID);
    }

    // Get optional inputs
    $pattern = $request->params('pattern', '');
    $verb = $request->params('verb', '');


    // Supply data to model which processes it further
    $model = new Clients();
    $new_id = $model->addPermission($api_key, $pattern, $verb);

    // Send affirmation status
    $result = array();
    $result['id'] = $new_id;
    $app->success($result);
});


/**
 * Route: /clientpermissions/:id
 *  :id - Internal permission id (perm-id) the should be removed
 * Description:
 *  Deletes a permission statement given by :id (perm-id).
 * Method: DELETE
 * Auth: authenticateTokenOnly
 * Parameters:
 * Response:
 *  {
 *    status: "<Success or Failure>"
 *  }
 */
$app->delete('/clientpermissions/:id', '\RESTController\libs\OAuth2Middleware::TokenAuth',  function ($id) use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if authorized user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user)) {
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);
    }

    try {
        // Use the model class to update databse
        $model = new Clients();
        $aff_rows = $model->deletePermission($id);

        // Send affirmation status
        $result = array('NumItemsDeleted'=>$aff_rows);
        $app->success($result);
    } catch(ClientExceptions\DeleteFailed $e) {
        $app->halt(500, $e->getMessage(), $e::ID);
    }
});
