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
   * Route: GET /v1/umr/personaldesktop
   *  Fetches all items on the personel desktop of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->get('/personaldesktop', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    // Fetch user-information
    $personelDesktop     = PersonalDesktop::getPersonalDesktop($accessToken);

    // Output result
    $app->success($personelDesktop);
  });


  /**
   * Route: POST /v1/umr/personaldesktop
   *  Adds an item to the personal desktop of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->post('/personaldesktop', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: DELETE /v1/umr/personaldesktop
   *  Removes an item from the desktop of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->delete('/personaldesktop', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
