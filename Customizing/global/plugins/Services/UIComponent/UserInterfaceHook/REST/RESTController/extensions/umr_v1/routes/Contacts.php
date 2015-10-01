<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;


// This allows us to use shortcuts instead of full quantifier
// Requires: $app to be \RESTController\RESTController::getInstance()
use \RESTController\core\auth as Auth;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/contacts
   *
   * @See docs/api.pdf
   */
  $app->get('/contacts', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    try{
      // Fetch user-information
      $cags       = Contacts::getContacts($accessToken);

      // Output result
      $app->success($cags);
    }
    // Catch error thrown by getUserInfo(...)
    catch (Exceptions\UserInfo $e) {
      $app->halt(422, $e->getMessage(), $e->getRestCode());
    }
  });


  /**
   * Route: POST /v1/umr/contacts
   *
   * @See docs/api.pdf
   */
  $app->post('/contacts', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: DELETE /v1/umr/contacts
   *
   * @See docs/api.pdf
   */
  $app->delete('/contacts', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
