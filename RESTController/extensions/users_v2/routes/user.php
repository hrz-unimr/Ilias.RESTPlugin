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
use \RESTController\libs          as Libs;
use \RESTController\libs\RESTAuth as RESTAuth;


// Group implemented routes into common group
//  This routes are more common-user focused rather than administative
$app->group('/v2/users', function () use ($app) {
  /*
   * Route: [GET] /users/search
   *  Searches users by certain criteria and returns list of user-ids.
   *
   * Parameters:
   *  search <String> Search string that is partially matched (LIKE %SEARCH%) against in login, firstname, lastname and email
   *  login <String> Search user by his (exact) login
   *  external <String> Search user by his (exact) ext_account value (requires authmode too)
   *  authmode <String> Search ext_account based on given auth-mode
   *  role <Numeric> Search users by the role (id) they have
   *  parent <Numeric> Search users by the category or org-unit they are contained within
   *
   * Returns:
   *   <Array[Numeric]> - List of user-ids that match the search-criteria
   */
  $app->get('/search', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    try {
      // Fetch input parameters
      $request  = $app->request();
      $search   = $request->getParameter('search',   false);
      $login    = $request->getParameter('login',    false);
      $external = $request->getParameter('external', false);
      $authmode = $request->getParameter('authmode', false);
      $role     = $request->getParameter('role',     false);
      $parent   = $request->getParameter('parent',   false);

      // Fetch user-ids
      $ids = User::SearchUser($search, $login, $external, $authmode, $role, $parent);

      // Return found users
      $app->success(array(
        'ids' => $ids,
      ));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });
// End of URI group
});
