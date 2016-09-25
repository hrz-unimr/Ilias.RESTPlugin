<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v2;


// This allows us to use shorter names instead of full namespace quantifier
// Requires: $app to be \RESTController\RESTController::getInstance();
use \RESTController\libs as Libs;
use \RESTController\libs\RESTAuth as RESTAuth;


// Group implemented routes into common group
//  This routes are mostly implemeted for University of Marburg use-cases
$app->group('/v2/umr/system', function () use ($app) {
  /**
   *
   */
  $app->get('/login-stats/:user', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($user) use ($app) {
    // User was given by user-id
    if (is_numeric($user)) {
      $userId = intval($user);
      Libs\RESTIlias::getUserName($userId);
    }
    // User was given by login (probably)
    else
      $userId = Libs\RESTIlias::getUserId($user);

    // Fetch information about user
    $userData = System::LoginStats($userId);

    // Return result
    $app->success($userData);
  });


  /**
   *
   */
  $app->get('/filter-accounts', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
    // Note: Return only minimal set of user-data fields
    $app->halt(501, 'Not yet implemented...');
  });

// End of URI group
});
