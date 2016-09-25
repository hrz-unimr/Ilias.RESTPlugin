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
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\oauth2_v2 as Auth;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/personaldesktop
   *  Fetches all items on the personal desktop of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->get('/personaldesktop', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    // Fetch user-information
    $personalDesktop = PersonalDesktop::getPersonalDesktop($accessToken);

    // Output result
    $app->success($personalDesktop);
  });


  /**
   * Route: POST /v1/umr/personaldesktop
   *  Adds an item to the personal desktop of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->post('/personaldesktop', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: DELETE /v1/umr/personaldesktop
   *  Removes an item from the desktop of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->delete('/personaldesktop', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
