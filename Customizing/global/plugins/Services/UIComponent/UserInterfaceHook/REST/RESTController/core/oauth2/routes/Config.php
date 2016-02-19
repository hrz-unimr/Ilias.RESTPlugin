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
     * Route: [GET] /v2/oauth2/config/:key
     * [Admin required]
     *  Returns the current value for the requested config key.
     *
     * Returns:
     *  {
     *    'key': 'value'
     *  }
     */
    $app->get('/config/:key', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($key) use ($app) {
      try {
        // Fetch settings
        $settings = Database\RESTconfig::fetchSettings($key);

        // Send settings (contains requested key)
        $app->success($settings);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/oauth2/config/:key
     * [Admin required]
     *  Updates a config settings with a new value.
     *
     * Parameters:
     *  value <String> - New value
     *
     * Returns:
     *  {
     *    'key': 'value'
     *  }
     */
    $app->put('/config/:key', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($key) use ($app) {
      try {
        // Fetch new value from request
        $request  = $app->request();
        $value    = $request->params('value', null, true);

        // Fetch current table entry and update with new value
        $settings = Database\RESTconfig::fromSettingName($key);
        $settings->setKey('setting_value', $value);
        $settings->update();

        // Send settings (contains requested key)
        $app->success(array(
          $key => $value,
        ));
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


    $app->get('/test', function () use ($app) {
      $request  = $app->request();
      $accessToken  = $request->getToken('access');
      $route = $app->router()->getCurrentRoute();
      
      Libs\Middleware\OAuth2::checkRoutePermissions($app, $accessToken, $route, $request);
    });


  });
});
