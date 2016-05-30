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
   * Route: GET /v1/umr/UserInfo
   *  Return profil-data for the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->get('/userinfo', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch user-information
      $userInfo     = UserInfo::getUserInfo($accessToken->getUserId());

      // Output result
      $app->success($userInfo);
    }
    // Catch error thrown by getUserInfo(...)
    catch (Exceptions\UserInfo $e) {
      $app->halt(500, $e->getRESTMessage(), $e->getRESTCode());
    }
  });


  /**
   * Route: PUT /v1/umr/UserInfo
   *  Updates the profil of the user given by the access-token with provided data.
   *
   * @See docs/api.pdf
   */
  $app->put('/userinfo', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
