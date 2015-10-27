<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
// Requires <$app = \RESTController\RESTController::getInstance()>
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\auth as Auth;
use \RESTController\libs as Libs;


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
    $includeAllRoutes = true;
    $request = $app->request();
    if ($request->params('middleware')) {
        // Include only those routes that are protected by an auth middleware.
        //$view_status = $request->params('middleware');
        //if ($view_status == 'all') ...
        $includeAllRoutes = false;
    }
    // Fetch all available routes
    $routes = $app->router()->getRoutes();

    // Wrap routes into array
    $result = array();
    $result['routes'] = Routes::parseRoutes($routes,$includeAllRoutes);

    // Send data
    $app->success($result);
});

/**
 * Returns the routes that can be accessed with the API-Key provided by the current token.
 * The result furthermore indicates if the user has capabilities to access admin routes.
 */
$app->get('/apiroutes', RESTAuth::checkAccess(RESTAuth::TOKEN), function () use ($app) {
    $includeUnrestrictedRoutes = false;
    $request = $app->request();
    if ($request->params('view')) {
        //$view_status = $request->params('view');
        //if ($view_status == 'all') ...
        $includeUnrestrictedRoutes = true;
    }
    $api_key = Auth\Util::getAccessToken()->getApiKey();
    $apiRoutes = Clients::getPermissionsForApiKey($api_key);

    // Fetch all available routes
    $allRoutes = $app->router()->getRoutes();

    $filteredRoutes = Routes::filterApiRoutes($apiRoutes, $allRoutes, $includeUnrestrictedRoutes);

    $result = array();
    $result['permissions'] = $filteredRoutes;

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
    // Fetch for app_directory
    $env = $app->environment();

    // Redirect
    $app->redirect(Routes::getConfigURL($env['app_directory']));
});
