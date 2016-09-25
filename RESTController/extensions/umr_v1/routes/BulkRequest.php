<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;


// This allows us to use shortcuts instead of full quantifier
// Requires: $app to be \RESTController\RESTController::getInstance();
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\oauth2_v2 as Auth;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/bulkrequest
   *  Returns collected (bulk) information of all contacts, calendars,
   *  events, groups, courses, user-info, items on users desktop
   *  combined with contents of those items for the user given by
   *  the access-token.
   *
   * @See docs/api.pdf
   */
  $app->get('/bulkrequest', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    // Fetch user-information
    $bulk = BulkRequest::getBulk($accessToken);

    // Output result
    $app->success($bulk);
  });

// End of '/v1/umr/' URI-Group
});
