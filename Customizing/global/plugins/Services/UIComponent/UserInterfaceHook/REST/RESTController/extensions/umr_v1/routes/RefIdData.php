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
   * Route: GET /v1/umr/refiddata
   *
   * @See docs/api.pdf
   */
  $app->get('/refiddata', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
      // Fetch userId & userName
      $auth         = new Auth\Util();
      $accessToken  = $auth->getAccessToken();

      // Fetch refIds
      $request      = $app->request;
      $refIdString  = $request->params('refids');
      $refIds       = RefIdData::getRefIds($refIdString);

      // Fetch data for refIds
      $data         = RefIdData::getData($accessToken, $refIds);

      // Output result
      $app->success($data);
  });
  $app->get('/refiddata/:refId', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($refId) use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    // TODO: check is_numeric($refId)

    // Fetch data for refIds
    $data    = RefIdData::getData($accessToken, intval($refId));

    // Output result
    $app->success($data);
  });

// End of '/v1/umr/' URI-Group
});
