<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
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
 *        oauth2_gt_authcode_active: "<OAuth2 use authentication-code of client>",
 *        oauth2_gt_implicit_active: "<OAuth2 use implicit-grant of client>",
 *        oauth2_gt_resourceowner_active: "<OAuth2 use resource-owner of client>",
 *        oauth2_user_restriction_active: "<OAuth2 restrict to certain user of client>",
 *        oauth2_consent_message_active: "<OAuth2 Consent-Message of client>",
 *        oauth2_authcode_refresh_active: "<OAuth2 enable refresh-token for authentication-code of client>",
 *        oauth2_resource_refresh_active: "<OAuth2 enable refresh-token for resource-owner of client>"
 *      },
 *      ...
 *    ],
 *    status: "<Success or Failure>"
 *  }
 */
 $app->get('/clients', '\RESTController\libs\OAuth2Middleware::TokenAuth', function () use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user))
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);

    // Use the model class to fetch data
    $model = new Clients();
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
 *    oauth2_gt_authcode_active: "<OAuth2 use authentication-code for new client>", <OPTIONAL>
 *    oauth2_gt_implicit_active: "<OAuth2 use implicit-grant for new client>", <OPTIONAL>
 *    oauth2_gt_resourceowner_active: "<OAuth2 use resource-owner for new client>", <OPTIONAL>
 *    oauth2_user_restriction_active: "<OAuth2 restrict to certain user for new client>", <OPTIONAL>
 *    oauth2_consent_message_active: "<OAuth2 Consent-Message for new client>", <OPTIONAL>
 *    oauth2_authcode_refresh_active: "<OAuth2 enable refresh-token for authentication-code for new client>", <OPTIONAL>
 *    oauth2_resource_refresh_active: "<OAuth2 enable refresh-token for resource-owner for new client>" <OPTIONAL>
 *  }
 * Response:
 *  {
 *    id: <Internal id (api-id) of new client>,
 *    status: "<Success or Failure>"
 *  }
 */
$app->put('/clients/:id', '\RESTController\libs\OAuth2Middleware::TokenAuth', function ($id) use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if authorized user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user))
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);

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
        //'access_ip_csv',
        'api_secret',
        'api_key'
    );

    // Try to fetch each fields data and update its db-entry
    $model = new Clients();
    $request = $app->request;

    $failed = array();
    foreach ($fields as $field) {
        try {
            // Fetch request data (Throws exception to prevent updateClient call)
            $newVal = $request->params($field, null, true);

            // Update client
            try {
                $model->updateClient($id, $field, $newVal);
            } catch(ClientExceptions\PutFailed $e) {
                $failed[] = $e->getMessage();
            }
        }
        // Fail silently for "missing" parameters
        catch (LibExceptions\MissingParameter $e) {  }
    }

    // Return update results
    if (count($failed) > 0)
        $app->halt(500, array('msg' => $failed), ClientExceptions\PutFailed::ID);
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
 *    oauth2_gt_authcode_active: "<OAuth2 use authentication-code for new client>", <OPTIONAL>
 *    oauth2_gt_implicit_active: "<OAuth2 use implicit-grant for new client>", <OPTIONAL>
 *    oauth2_gt_resourceowner_active: "<OAuth2 use resource-owner for new client>", <OPTIONAL>
 *    oauth2_user_restriction_active: "<OAuth2 restrict to certain user for new client>", <OPTIONAL>
 *    oauth2_consent_message_active: "<OAuth2 Consent-Message for new client>", <OPTIONAL>
 *    oauth2_authcode_refresh_active: "<OAuth2 enable refresh-token for authentication-code for new client>", <OPTIONAL>
 *    oauth2_resource_refresh_active: "<OAuth2 enable refresh-token for resource-owner for new client>" <OPTIONAL>
 *  }
 * Response:
 *  {
 *    id: <Internal id (api-id) of new client>,
 *    status: "<Success or Failure>"
 *  }
 */
$app->post('/clients/', '\RESTController\libs\OAuth2Middleware::TokenAuth', function () use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if authorized user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user))
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);

    // Shortcut for request object
    $request = $app->request();

    $app->log->debug('in post clients');
    $app->log->debug(print_r($request->getBody(),true));

    // Try/Catch all required inputs
    try {
        $api_key = $request->params('api_key', null, true);
    } catch(LibExceptions\MissingParameter $e) {
        $app->halt(422, $e->getFormatedMessage(), $e::ID);
    }

    // Get optional inputs
    $api_secret = $request->params('api_secret', '');
    $client_oauth2_consent_message = $request->params('oauth2_consent_message', '');
    $client_permissions = $request->params('permissions', '');
    $client_oauth2_redirect_url = $request->params('oauth2_redirection_uri', '');
    $oauth2_gt_client_user = $request->params('oauth2_gt_client_user', '');
    $access_user_csv = $request->params('access_user_csv', '');
    $access_ip_csv = $request->params('access_ip_csv', '');
    $oauth2_gt_client_active = $request->params('oauth2_gt_client_active', 0);
    $oauth2_gt_authcode_active = $request->params('oauth2_gt_authcode_active', 0);
    $oauth2_gt_implicit_active = $request->params('oauth2_gt_implicit_active', 0);
    $oauth2_gt_resourceowner_active = $request->params('oauth2_gt_resourceowner_active', 0);
    $oauth2_user_restriction_active = $request->params('oauth2_user_restriction_active', 0);
    $oauth2_consent_message_active = $request->params('oauth2_consent_message_active', 0);
    $oauth2_authcode_refresh_active = $request->params('oauth2_authcode_refresh_active', 0);
    $oauth2_resource_refresh_active = $request->params('oauth2_resource_refresh_active', 0);

    // Supply data to model which processes it further
    $model = new Clients();
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
        $access_ip_csv,
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
$app->delete('/clients/:id', '\RESTController\libs\OAuth2Middleware::TokenAuth',  function ($id) use ($app) {
    // Fetch authorized user
    $auth = new Auth\Util();
    $user = $auth->getAccessToken()->getUserName();

    // Check if authorized user has admin role
    if (!Libs\RESTLib::isAdminByUserName($user))
        $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);

    try {
        // Use the model class to update databse
        $model = new Clients();
        $model->deleteClient($id);

        // Send affirmation status
        $result = array();
        $app->success($result);
    } catch(ClientExceptions\DeleteFailed $e) {
        $app->halt(500, $e->getMessage(), $e::ID);
    }
});
