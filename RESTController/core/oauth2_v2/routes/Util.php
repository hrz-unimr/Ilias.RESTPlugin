<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


// Group Version 2 implementation
$app->group('/v2', function () use ($app) {
  // Group all util routes
  $app->group('/util', function () use ($app) {
    /**
     * Route: [GET] /v2/util/config
     *  Redirects to the oauth2 admin-panel where client information and permissions can be changed.
     */
    $app->get('/config', function () use ($app) {
      // Reference to $ilPluginAdmin-Object
      global $ilPluginAdmin;

      // Fetch location of admin-panel
      $ilPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");
      $url      = substr($ilPlugin->getDirectory(), 1) . '/apps/admin/';

      // Redirect to admin panel
      $app->redirect($url);
    });


    /**
     * Route: [GET] /v2/util/routes
     *
     *
     * Returns:
     *  {
     *
     *  }
     */
    $app->get('/routes', function () use ($app) {
      // Fetch all information required by this route
      $request  = $app->request();
      $apiKey   = $request->getParameter('api_key');
      $filter   = $request->getParameter('filter');
      $routes   = $app->router()->getRoutes();

      // Fetch list of (restricted) routes
      $result   = Util::GetRoutes($routes, $apiKey, $filter);
      $app->success($result);
    });

    /**
     * Returns the routes that are accessible with the token.
     * Generic routes that do not require special access permissions
     * are filtered out by default.
     *
     * If all routes should be displayed then the additional parameter
     * filter must be set to false.
     *
     */
    $app->get('/tokenroutes', function () use ($app) {
      $request     = $app->request();
      $accessToken = $request->getToken();
      $apiKey      = $accessToken->getApiKey();
      $filter      = $request->getParameter('filter', true, false);
      $routes      = $app->router()->getRoutes();

      // Fetch list of (restricted) routes
      $result   = Util::GetRoutes($routes, $apiKey, $filter);
      $app->success($result);
    });
  });
});
