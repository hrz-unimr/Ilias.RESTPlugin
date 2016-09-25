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
use \RESTController\libs as Libs;
use \RESTController\core\oauth2_v2 as Auth;


$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/news
   *  [Without HTTP-GET Parameters] Gets all news for the user encoded by the access-token.
   * @See docs/api.pdf
   */
  $app->get('/news', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    // Load additional (optional) parameters
    $request  = $app->request;
    $period   = $request->getParameter('period', null);
    $offset   = $request->getParameter('offset', null);
    $lastid   = $request->getParameter('lastid', null);
    $limit    = $request->getParameter('limit', null);
    $settings = array_filter(array(
      'period'  => $period  ? intval($period) : null,
      'offset'  => $offset  ? intval($offset) : null,
      'limit'   => $limit   ? intval($limit)  : null,
      'lastid'  => $lastid  ? intval($lastid) : null,
    ), function($value) { return !is_null($value); });

    // Fecth news
    $news  = News::getAllNews($accessToken, $settings);

    // Output result
    $app->success($news);
  });
});
