<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\clients\Exceptions as ClientExceptions;
// Requires <$app = \RESTController\RESTController::getInstance()>


/**
 * Route: /clients
 * Description:
 *  Returns a list of all REST clients and their settings.
 * Method: GET
 * Auth: authenticateTokenOnly
 * Parameters:
 * Response:
 *  {
 *    clients: [
 *      {
 *        api_key: "<API-Key of client>",
 *        api_secret: "<API-Secret of client>",
 *        client_oauth2_consent_message: "<OAuth2 Consent-Message of client>",
 *        client_permissions: [
 *          {
 *            pattern: "<Route-URI>"
 *            verb: "<GET, POST, PUT or DELETE>"
 *          },
 *          ...
 *        ],
 *        client_oauth2_redirect_url: "<OAuth2 redirect-url of client>",
 *        oauth2_gt_client_user: "<OAuth Resource-Owner of client>",
 *        access_user_csv: [
 *          <ILIAS User-Id>,
 *          ...
 *        ], <Allowed ILIAS users for this client>
 *        oauth2_gt_client_active: "<OAuth2 use client-credentials of client>",
 *        oauth2_gt_authcode_active: "<OAuth2 use authentification-code of client>",
 *        oauth2_gt_implicit_active: "<OAuth2 use implicit-grant ofclient>",
 *        oauth2_gt_resourceowner_active: "<OAuth2 use resource-owner of client>",
 *        oauth2_user_restriction_active: "<OAuth2 restrict to certain user of client>",
 *        oauth2_consent_message_active: "<OAuth2 Consent-Message of client>",
 *        oauth2_authcode_refresh_active: "<OAuth2 enable refresh-token for authentification-code of client>",
 *        oauth2_resource_refresh_active: "<OAuth2 enable refresh-token for resource-owner of client>"
 *      },
 *      ...
 *    ],
 *    status: "<Success or Failure>"
 *  }
 */
 $app->get('/clients', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly', function () use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if user has admin role
    if (!RESTLib::isAdminByUsername($user))
        $app->halt(401, 'Access denied. Administrator permissions required.', Libs\RESTLib::NO_ADMIN_ID);

    // Use the model class to fetch data
    $model = new Clients($app, $ilDB);
    $data = $model->getClients();

    // Prepare data
    $result = array();
    $result['clients'] = $data;

    // Send data
    $app->success($result);
});


/**
 * Route: /clients/:id
 *  id: <Internal id (api-id) of the client that should be updated>
 * Description:
 *  Updates an existing REST client with new settings.
 * Method: PUT
 * Auth: authenticateTokenOnly
 * Parameters:
 *  {
 *    api_key: "<API-Key for new client>", <OPTIONAL>
 *    api_secret: "<API-Secret for new client>", <OPTIONAL>
 *    client_oauth2_consent_message: "<OAuth2 Consent-Message for new client>", <OPTIONAL>
 *    client_permissions: [
 *      {
 *        pattern: "<Route-URI>"
 *        verb: "<GET, POST, PUT or DELETE>"
 *      },
 *      ...
 *    ], <OPTIONAL>
 *    client_oauth2_redirect_url: "<OAuth2 redirect-url for new client>", <OPTIONAL>
 *    oauth2_gt_client_user: "<OAuth Resource-Owner for new client>", <OPTIONAL>
 *    access_user_csv: [
 *      <ILIAS User-Id>,
 *      ...
 *    ], <OPTIONAL>
 *    oauth2_gt_client_active: "<OAuth2 use client-credentials for new client>", <OPTIONAL>
 *    oauth2_gt_authcode_active: "<OAuth2 use authentification-code for new client>", <OPTIONAL>
 *    oauth2_gt_implicit_active: "<OAuth2 use implicit-grant for new client>", <OPTIONAL>
 *    oauth2_gt_resourceowner_active: "<OAuth2 use resource-owner for new client>", <OPTIONAL>
 *    oauth2_user_restriction_active: "<OAuth2 restrict to certain user for new client>", <OPTIONAL>
 *    oauth2_consent_message_active: "<OAuth2 Consent-Message for new client>", <OPTIONAL>
 *    oauth2_authcode_refresh_active: "<OAuth2 enable refresh-token for authentification-code for new client>", <OPTIONAL>
 *    oauth2_resource_refresh_active: "<OAuth2 enable refresh-token for resource-owner for new client>" <OPTIONAL>
 *  }
 * Response:
 *  {
 *    id: <Internal id (api-id) of new client>,
 *    status: "<Success or Failure>"
 *  }
 */
$app->put('/clients/:id', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly', function ($id) use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if authorized user has admin role
    if (!Libs\RESTLib::isAdminByUsername($user))
        $app->halt(401, 'Access denied. Administrator permissions required.', Libs\RESTLib::NO_ADMIN_ID);

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
    $model = new Clients($app, $ilDB);
    $request = $app->request;
    $failed = array();
    foreach ($fields as $field) {
        try {
            // Fetch request data (Throws exception to prevent updateClient call)
            $api_key = $request->getParam($field, null, true);

            // Update client
            try {
                $model->updateClient($id, $field, $api_key);
            } catch(ClientExceptions\PutFailed $e) {
                $failed[] = sprintf('Could not (fully) update client. Failed to update Parameter: %s.', $e->paramName());
            }
        }
        // Fail silently for "missing" parameters
        catch (LibExceptions\MissingParameter $e) {  }
    }

    // Return update results
    if (count($failed) > 0)
        $app->halt(500, implode(' ', $failed), ClientExceptions\PutFailed::ID);
    else {
        // Send affirmation status
        $result = array();
        $app->success($result);
    }
});


/**
 * Route: /clients
 * Description:
 *  Creates a new client with the parameters that are passes along.
 *  Default values are used for missing optional parameters.
 *  Also returns the new clients id (api-id).
 * Method: POST
 * Auth: authenticateTokenOnly
 * Parameters:
 *  {
 *    api_key: "<API-Key for new client>",
 *    api_secret: "<API-Secret for new client>", <OPTIONAL>
 *    client_oauth2_consent_message: "<OAuth2 Consent-Message for new client>", <OPTIONAL>
 *    client_permissions: [
 *      {
 *        pattern: "<Route-URI>"
 *        verb: "<GET, POST, PUT or DELETE>"
 *      },
 *      ...
 *    ], <OPTIONAL>
 *    client_oauth2_redirect_url: "<OAuth2 redirect-url for new client>", <OPTIONAL>
 *    oauth2_gt_client_user: "<OAuth Resource-Owner for new client>", <OPTIONAL>
 *    access_user_csv: [
 *      <ILIAS User-Id>,
 *      ...
 *    ], <OPTIONAL>
 *    oauth2_gt_client_active: "<OAuth2 use client-credentials for new client>", <OPTIONAL>
 *    oauth2_gt_authcode_active: "<OAuth2 use authentification-code for new client>", <OPTIONAL>
 *    oauth2_gt_implicit_active: "<OAuth2 use implicit-grant for new client>", <OPTIONAL>
 *    oauth2_gt_resourceowner_active: "<OAuth2 use resource-owner for new client>", <OPTIONAL>
 *    oauth2_user_restriction_active: "<OAuth2 restrict to certain user for new client>", <OPTIONAL>
 *    oauth2_consent_message_active: "<OAuth2 Consent-Message for new client>", <OPTIONAL>
 *    oauth2_authcode_refresh_active: "<OAuth2 enable refresh-token for authentification-code for new client>", <OPTIONAL>
 *    oauth2_resource_refresh_active: "<OAuth2 enable refresh-token for resource-owner for new client>" <OPTIONAL>
 *  }
 * Response:
 *  {
 *    id: <Internal id (api-id) of new client>,
 *    status: "<Success or Failure>"
 *  }
 */
$app->post('/clients/', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly', function () use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if authorized user has admin role
    if (Libs\RESTLib::isAdminByUsername($user))
        $app->halt(401, 'Access denied. Administrator permissions required.', Libs\RESTLib::NO_ADMIN_ID);

    // Shortcut for request object
    $request = $app->request();

    // Try/Catch all required inputs
    try {
        $api_key = $request->getParam('api_key', null, true);
    } catch(LibExceptions\MissingParameter $e) {
        $app->halt(422, $e->getMessage(), LibExceptions\MissingParameter::ID);
    }

    // Get optional inputs
    $api_secret = $request->getParam('api_secret', '');
    $client_oauth2_consent_message = $request->getParam('oauth2_consent_message', '');
    $client_permissions = $request->getParam('permissions', '');
    $client_oauth2_redirect_url = $request->getParam('oauth2_redirection_uri', '');
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
    $model = new Clients($app, $ilDB);
    $new_id = $model->createClient(
        $api_key,
        $api_secret,
        $client_oauth2_redirect_url,
        $client_oauth2_consent_message,
        $oauth2_consent_message_active,
        $client_permissions,
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

    // Send affirmation status
    $result = array();
    $result['id'] = $new_id;
    $app->success($result);
});


/**
 * Route: /clients/:id
 *  :id - Internal client id (api-id) the should be removed
 * Description:
 *  Deletes the REST client given by :id (api-id).
 * Method: DELETE
 * Auth: authenticateTokenOnly
 * Parameters:
 * Response:
 *  {
 *    status: "<Success or Failure>"
 *  }
 */
$app->delete('/clients/:id', '\RESTController\libs\AuthMiddleware::authenticateTokenOnly',  function ($id) use ($app) {
    // Fetch authorized user
    $env = $app->environment();
    $user = $env['user'];

    // Check if authorized user has admin role
    if (Libs\RESTLib::isAdminByUsername($user))
        $app->halt(401, 'Access denied. Administrator permissions required.', Libs\RESTLib::NO_ADMIN_ID);

    try {
        // Use the model class to update databse
        $model = new Clients($app, $ilDB);
        $model->deleteClient($id);

        // Send affirmation status
        $result = array();
        $app->success($result);
    } catch(ClientExceptions\DeleteFailed $e) {
        $app->halt(500, sprintf('Could not delete client with id: %d', $e->id()), ClientExceptions\DeleteFailed::ID);
    }
});
