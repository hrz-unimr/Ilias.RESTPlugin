<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\clients\Exceptions as ClientExceptions;


/**
 * Route: /clients
 * Method: GET
 * Auth: authenticateTokenOnly
 * Head-Parameters:
 * Body-Parameters:
 * Response:
 */
$app->get('/clients', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly',  function () use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if user has admin role
    if (RESTLib::isAdminByUsername($user)) {
        // Use the model class to fetch data
        $admin_model = new ClientsModel();
        $data = $admin_model->getClients();

        // Prepare data
        $result = array();
        $result['clients'] = $data;

        // Send data
        $app->success($result);
    }
    else
        $app->halt(401, "Access denied. Administrator permissions required.", RESTLib::NO_ADMIN_ID);
});


/**
 * Route: /clients
 * Method: PUT
 * Auth: authenticateTokenOnly
 * Head-Parameters:
 * Body-Parameters:
 * Response:
 */
$app->put('/clients/:id', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly',  function ($id) use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if authorized user has admin role
    if (RESTLib::isAdminByUsername($user)) {
        // Shortcut for request object
        $request = $app->request;
        $app->log->debug("Update data: " . print_r($request->getRaw(), true));

        // Use model to update database
        $admin_model = new ClientsModel();

        // This fields will be updated (and nothing more!)
        $fields = array(
            'oauth2_redirection_uri',
            'oauth2_consent_message',
            'permissions',
            'oauth2_gt_client_active',
            'oauth2_gt_client_user',
            'oauth2_gt_authcode_active',
            'oauth2_gt_implicit_active',
            'oauth2_gt_resourceowner_active',
            'oauth2_user_restriction_active',
            'oauth2_consent_message_active',
            'oauth2_authcode_refresh_active',
            'oauth2_resource_refresh_active',
            'access_user_csv',
            'api_secret',
            'api_key'
        );

        // Try to fetch each fields data and update its db-entry
        $failed = array();
        foreach ($fields as $field) {
            try {
                // Fetch request data (Throws exception to prevent updateClient call)
                $api_key = $request->getParam($field, null, true);

                // Update client
                try {
                    $admin_model->updateClient($id, $field, $api_key);
                } catch(ClientExceptions\SaveFailed $e) {
                    $failed[] = sprintf("Could not (fully) update client. Failed to update Parameter: %s.", $e->paramName());
                }
            }
            // Fail silently for "missing" parameters
            catch (LibExceptions\MissingParameter $e) {  }
        }

        if (count($failed) > 0)
            $app->halt(500, implode(' ', $failed), ClientExceptions\SaveFailed::PUT_FAILED_ID);
        else {
            // Send affirmation status
            $result = array();
            $app->success($result);
        }
    }
    else
        $app->halt(401, "Access denied. Administrator permissions required.", RESTLib::NO_ADMIN_ID);
});


/**
 * Route: /clients
 * Method: POST
 * Auth: authenticateTokenOnly
 * Head-Parameters:
 * Body-Parameters:
 * Response:
 */
$app->post('/clients/', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly', function () use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if authorized user has admin role
    if (RESTLib::isAdminByUsername($user)) {
        // Shortcut for request object
        $request = $app->request();
        $app->log->debug("Request data (Create Client): " . print_r($request->getRaw(), true));

        // Try/Catch all required inputs
        try {
            $new_api_key = $request->getParam('api_key', null, true);
        } catch(LibExceptions\MissingParameter $e) {
            $app->halt(500, "Mandatory data is missing, parameter '" . $e.paramName() . "' not set.", LibExceptions\MissingParameter::MISSING_PARAM_ID);
        }

        // Get optional inputs
        $new_api_secret = $request->getParam('api_secret', '');
        $new_client_oauth2_consent_message = $request->getParam('oauth2_consent_message', '');
        $new_client_permissions = $request->getParam('permissions', '');
        $new_client_oauth2_redirect_url = $request->getParam('oauth2_redirection_uri', '');
        $oauth2_gt_client_user = $request->getParam('oauth2_gt_client_user', '');
        $access_user_csv = $request->getParam('access_user_csv', '');
        $oauth2_gt_client_active = $request->getParam('oauth2_gt_client_active', 0);
        $oauth2_gt_authcode_active = $request->getParam('oauth2_gt_authcode_active', 0);
        $oauth2_gt_implicit_active = $request->getParam('oauth2_gt_implicit_active', 0);
        $oauth2_gt_resourceowner_active = $request->getParam('oauth2_gt_resourceowner_active', 0);
        $oauth2_user_restriction_active = $request->getParam('oauth2_user_restriction_active', 0);
        $oauth2_consent_message_active = $request->getParam('oauth2_consent_message_active', 0);
        $oauth2_authcode_refresh_active = $request->getParam('oauth2_authcode_refresh_active', 0);
        $oauth2_resource_refresh_active = $request->getParam('oauth2_resource_refresh_active', 0);

        // Supply data to model which processes it further
        $admin_model = new ClientsModel();
        $new_id = $admin_model->createClient(
            $new_api_key,
            $new_api_secret,
            $new_client_oauth2_redirect_url,
            $new_client_oauth2_consent_message,
            $oauth2_consent_message_active,
            $new_client_permissions,
            $oauth2_gt_client_active,
            $oauth2_gt_authcode_active,
            $oauth2_gt_implicit_active,
            $oauth2_gt_resourceowner_active,
            $oauth2_user_restriction_active,
            $oauth2_gt_client_user,
            $access_user_csv,
            $oauth2_authcode_refresh_active,
            $oauth2_resource_refresh_active
        );
        $app->log->debug('Result of createClient: '.$new_id);

        // Send affirmation status
        $result = array();
        $result['id'] = $new_id;
        $app->success($result);
    }
    else
        $app->halt(401, "Access denied. Administrator permissions required.", RESTLib::NO_ADMIN_ID);
});


/**
 * Route: /clients/:id
 *  :id
 * Method: GET
 * Auth: authenticateTokenOnly
 * Head-Parameters:
 * Body-Parameters:
 * Response:
 */
$app->delete('/clients/:id', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly',  function ($id) use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if authorized user has admin role
    if (RESTLib::isAdminByUsername($user)) {
        try {
            // Use the model class to update databse
            $admin_model = new ClientsModel();
            $admin_model->deleteClient($id);

            // Send affirmation status
            $result = array();
            $app->success($result);
        } catch(ClientExceptions\SaveFailed $e) {
            $app->halt(500, "Could not delete client with id: " . $e->id(), ClientExceptions\SaveFailed::DELETE_FAILED_ID);
        }
    }
    else
        $app->halt(401, "Access denied. Administrator permissions required.", RESTLib::NO_ADMIN_ID);
});


/**
 * Route: /routes
 * Method: GET
 * Auth: none
 * Head-Parameters:
 * Body-Parameters:
 * Response:
 */
$app->get('/routes', function () use ($app) {
    // Fetch all available routes
    $routes = $app->router()->getRoutes();

    // Build up response data
    $resultRoutes = array();
    foreach($routes as $route) {
        // Format/Get data
        $multiVerbs = $route->getHttpMethods();
        $verb = $multiVerbs[0];
        $middle = $route->getMiddleware();

        // Pack data
        $resultRoutes[] = array(
            "pattern" => $route->getPattern(),
            "verb" => $verb,
            "middleware" => (isset($middle[0]) ? $middle[0] : "none")
        );
    }

    // Wrap routes into array
    $result = array();
    $result['routes'] = $resultRoutes;

    // Send data
    $app->success($result);
});


/**
 * Route: /rest/config
 * Method: GET
 * Auth: none
 * Head-Parameters:
 * Body-Parameters:
 * Response:
 */
$app->get('/rest/config', function () use ($app) {
    // Find plugin directory (REST)
    $env = $app->environment();
    $pluginDir = dirname($env['app_directory']);

    // Find base directory (ILIAS)
    $baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseDir = ($baseDir == '/' ? '' : $baseDir);

    // Build full directory
    $apDir = $baseDir . "/" . $pluginDir . '/apps/admin/';
    $app->redirect($apDir);
});
