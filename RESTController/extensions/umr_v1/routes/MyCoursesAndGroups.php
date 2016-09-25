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
   * Route: GET /v1/umr/mycoursesandgroups
   *  Fetches all groups and courses the user given by the access-token is a member of.
   *
   * @See docs/api.pdf
   */
  $app->get('/mycoursesandgroups', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    // Fetch user-information
    $cags         = MyCoursesAndGroups::getMyCoursesAndGroups($accessToken);

    // Output result
    $app->success($cags);
  });

// End of '/v1/umr/' URI-Group
});
