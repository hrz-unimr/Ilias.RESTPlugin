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
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\auth as Auth;
use \RESTController\core\auth\Exceptions as AuthExceptions;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/UserInfo
   *
   * @See docs/api.pdf
   */
   //'\RESTController\libs\OAuth2Middleware::TokenRouteAuth',
  $app->get('/userinfo', function () use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    try {
      // Fetch user-information
      $userInfo     = UserInfo::getUserInfo($accessToken);

      // Output result
      $app->success($userInfo);
    }
    // Catch error thrown by getUserInfo(...)
    catch (Exceptions\UserInfo $e) {
      $app->halt(422, $e->getMessage(), $e->getRestCode());
    }
  });


  /**
   * Route: POST /v1/umr/UserInfo
   *
   * @See docs/api.pdf
   */
  $app->post('/userinfo', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: PUT /v1/umr/UserInfo
   *
   * @See docs/api.pdf
   */
  $app->put('/userinfo', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
