<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\clients;

use \RESTController\core\auth as Auth;
// Requires <$app = \RESTController\RESTController::getInstance()>


/**
 * Route: /routes
 * Description:
 *  Returns all available REST routes, with there verb and auth-requirement.
 * Method: GET
 * Auth: none
 * Parameters:
 * Response:
 *  {
 *    routes: [
 *      {
 *        pattern: "<Route-URI>",
 *        verb: "<GET, POST, PUT or DELETE>",
 *        middleware: "<An Auth-Middleware or none>",
 *      }
 *      ...
 *    ],
 *    status: "<Success or Failure>"
 *  }
 */
$app->get('/routes', function () use ($app) {
    // Create model and inject $app
    $model = new Routes();

    // Fetch all available routes
    $routes = $app->router()->getRoutes();

    // Wrap routes into array
    $result = array();
    $result['routes'] = $model->parseRoutes($routes);

    // Send data
    $app->success($result);
});

/**
 * Returns the routes that can be accessed with the API-Key provided by the current token.
 * The result furthermore indicates if the user has capabilities to access admin routes.
 */
$app->get('/apiroutes', '\RESTController\libs\OAuth2Middleware::TokenAuth', function () use ($app) {
    $auth = new Auth\Util();
    $api_key = $auth->getAccessToken()->getApiKey();
    $model = new Clients();
    $data = $model->getPermissionsForApiKey($api_key);
    $result = array();
    $result['permissions'] = $data;
    // Send data
    $app->success($result);
});



/**
 * Route: /rest/config
 * Description:
 *  Redirects the client to the config-gui (AngularJS app website)
 * Method: GET
 * Auth: none
 * Parameters:
 * Response:
 *  <Sends http-redirect to config-gui>
 */
$app->get('/rest/config', function () use ($app) {
    // Create model and inject $app
    $model = new Routes();

    // Fetch for app_directory
    $env = $app->environment();

    // Redirect
    $app->redirect($model->getConfigURL($env['app_directory']));
});
