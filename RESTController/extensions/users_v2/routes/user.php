<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\users_v2;


// This allows us to use shorter names instead of full namespace quantifier
// Requires: $app to be \RESTController\RESTController::getInstance();
use \RESTController\libs\RESTAuth as RESTAuth;


// Group implemented routes into common group
//  This routes are more common-user focused rather than administative
$app->group('/v2/users', function () use ($app) {
  /**
   * Todo: Implement route to list all available user accounts, keep returned user-data fields to a minimum though!
   */
  $app->get('/list', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Note: Return only minimal set of user-data fields
    $app->halt(501, 'Not yet implemented...');
  });


  /**
   * Todo: Implement route to search users by certain user-data fields, keep returned user-data fields to a minimum though!
   */
  $app->get('/search', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Note: Return some user-data fields only based on RBAC (& Profile settings)
    $app->halt(501, 'Not yet implemented...');
  });


  /**
   * Todo: Implement route to return users profile data
   */
  $app->get('/profile/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($id) use ($app) {
    // Note: Return some user-data fields only based on Profile settings
    $app->halt(501, 'Not yet implemented...');
  });


  /**
   * Todo: Implement route to update users profile data
   */
  $app->put('/profile', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
    // Note: Only allowed to edit onw account/profile
    $app->halt(501, 'Not yet implemented...');
  });
// End of URI group
});
