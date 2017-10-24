<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\learning_v1;


// This allows us to use shorter names instead of full namespace quantifier
// Requires: $app to be \RESTController\RESTController::getInstance();
use \RESTController\libs          as Libs;
use \RESTController\libs\RESTAuth as RESTAuth;


// Group implemented routes into common group
//  This groups handles learning objectvies and progress information
$app->group('/v1/learning', function () use ($app) {
  /**
   * Route: /v1/learning/:ref_id
   * Method: GET
   * Description:
   *  Return some general learning-progress oriented information about the requsted object if it does support learning-progress
   * URL-Parameters:
   *  ref_id <Numeric> Reference-Id of object to fetch learning-progress information for
   * Return:
   *   ref_id <Numeric> Reference-Id of object
   *   obj_id <Numeric> Object-Id of object
   *   title  <String> Title of object
   *   description <String> Description of object (in any)
   *   type <String> Internal type-name of object
   *   online <Boolean> True if object is online (not offline)
   *   mode <Numeric> Internal learning-progress mode of object (@See ilLPObjSettings -> LP_MODE_*)
   *   active <Boolean> True when learning-prgress has been actived for this object
   *   supports <Array> List of supported extensions
   *    spend_seconds <Boolean> Can measure time spend on learning
   *    marks <Boolean> Can return learning marks
   *    matrix_view <Boolean> Supports learning progress as matrix view
   *   participants <Array<Numeric>> List of learning participants
   *   collection <Array<Numeric>> List of sub-items for objects in collection-mode
   *   objectives <Array<Mixed>> List of learning-progress objectives, indexed by objective-id
   *    [objective-id]
   *     id <Numeric> Unique identifier for learning-progress objective
   *     title <String> Title of learning-progress objective
   *     description <String> Description for learning-progress objective
   *     initial <Numeric> Reference-Id of initial test for learning-progress objective
   *     qualified <Numeric> Reference-Id of qualified test for learning-progress objective
   */
  $app->get('/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch details for (sub-)item/object
      $app->success(Learning::GetDetails(intval($refId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/objectives/:ref_id
   * Method: GET
   * Description:
   *  Returns the learning-objective result information for given ILIAS object (ref_id) for all users and all objectives.
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of object to fetch learning-objective information for
   * Return:
   *  <Array> Array containing learning-objective result information indexed by user-ids
   *   [user_id] => @See [GET] /v1/learning/objectives/:ref_id/[user_id]
   */
  $app->get('/objectives/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $app->success(Learning::GetObjectiveResults(intval($refId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/objectives/:ref_id/:user_id
   * Method: GET
   * Description:
   *  Returns the learning-objective result information for given user (user_id) in
   *  given ILIAS object (ref_id) for all objectives.
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of object to fetch learning-objective information for
   *  user_id <Numeric> User-ID of user to fetch learning-objective information for
   * Return:
   *  <Array> Array containing learning-objective information indexed by objective-ids
   *   [objective_id] => @See [GET] /v1/learning/objectives/:ref_id/:user_id/[objective_id]
   */
  $app->get('/objectives/:ref_id/:user_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $app->success(Learning::GetObjectiveResults(intval($refId), intval($userId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/objectives/:ref_id/:user_id/:objectiveId
   * Method: GET
   * Description:
   *  Returns the learning-objective results information for given user (user_id) in
   *  given ILIAS object (ref_id) for a given objective (objective_id).
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of object to fetch learning-objective information for
   *  user_id <Numeric> User-ID of user to fetch learning-objective information for
   *  objective_id <Numeric> Index of learning-objective to fetch information for
   * Return:
   *  initial <Array> [Optional] Contains leraning-objective information for initial test if enabled
   *   status <Numeric> 0 Unknown, 1 Completed, 2 Failed (@See ilLOUserResults,  STATUS_*)
   *   result <Numeric> Percent value achieved for learning-objective
   *   limit <Numeric> Percent value need to succeed learning-objective
   *   tries <Numeric> Number of tries for learning-objective
   *   final <Boolean> True wether learning-objective was finalized (no further tries allowed)
   *  qualified <Array> [Optional] Contains leraning-objective information for qualified test if enabled
   *   @See 'initial'
   */
  $app->get('/objectives/:ref_id/:user_id/:objective_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId, $objectiveId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $app->success(Learning::GetObjectiveResults(intval($refId), intval($userId), intval($objectiveId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/scos/:ref_id
   * Method: GET
   * Description:
   *  Returns the learning-objective result information for given ILIAS shareable content object (scorm).
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of scorm object to fetch learning-objective information for
   * Return:
   *  <Array> Array containing learning-objective information indexed by user-ids
   *   [user-id] => @See [GET] /v1/learning/scos/:ref_id/[user-id]
   */
  $app->get('/scos/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $app->success(Learning::GetSCOResults(intval($refId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/scos/:ref_id/:user_id
   * Method: GET
   * Description:
   *  Returns the learning-objective result information for given ILIAS shareable content object (scorm).
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of scorm object to fetch learning-objective information for
   *  user_id <Numeric> User-ID of user to fetch learning-objective information for
   * Return:
   *  <Array> Array containing learning-objective information indexed by sco-ids
   *   [sco-id] => @See [GET] /v1/learning/scos/:ref_id/:user_id/[sco-id]
   */
  $app->get('/scos/:ref_id/:user_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $app->success(Learning::GetSCOResults(intval($refId), intval($userId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/scos/:ref_id/:user_id/:sco_id
   * Method: GET
   * Description:
   *  Returns the learning-objective result information for given ILIAS shareable content object (scorm).
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of scorm object to fetch learning-objective information for
   *  user_id <Numeric> User-ID of user to fetch learning-objective information for
   *  sco_id <Numeric> Internal Id of the shareable content object
   * Return:
   *  status <Numeric> Status of user for sco in ILIAS scorm object (@See ilLPStatus -> LP_STATUS_*_NUM)
   *  score <Numeric> Score that was reached by user for sco in ILIAS scorm object
   */
  $app->get('/scos/:ref_id/:user_id/:sco_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId, $scoId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      $app->success(Learning::GetSCOResults(intval($refId), intval($userId), intval($scoId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/progress/:ref_id
   * Method: GET
   * Description:
   *  Returns all learning-progress information for this object for all participants and all configured sub-items.
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of object to fetch learning-progress information for
   * Return:
   *  <Array> Learning progress results of all users index by user-id
   *   [user-id] <Array> Learning-progress of a participant @See [GET] /v1/learning/progress/:ref_id/[user_id]
   */
  $app->get('/progress/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch results
      $app->success(Learning::GetProgressResults(intval($refId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * Route: /v1/learning/progress/:ref_id/:user_id
   * Method: GET
   * Description:
   *  Returns all learning-progress information for this object for a given participant but all configured sub-items.
   * URL-Parameters:
   *  ref_id <Numeric> Reference-ID of object to fetch learning-progress information for
   *  user_id <Numeric> Limit results to given user
   * Return:
   *  status <Numeric> Learning-progress status of user for given object (@See ilLPStatus -> LP_STATUS_*)
   *  percent <Numeric> Percentage value of learning-progress of user for given object (if supported by object)
   *  status_changed <String> Time when status was (last) updated (ISO 8601 - Date & Time)
   *  access_time <String> Time when the user last accessed the attached object (ISO 8601 - Date & Time)
   *  read_count <Numeric> ???
   *  spent_seconds <Numeric> Time spend by user on improving learning-progress (if supported by object)
   *  visits <Numeric> Number of 'visits' (attempts?) by user on learning-progress (if supported by object)
   *  mark <String> Mark for learning progress given to user (if supported by object and enabled)
   *  comment <String> Comment for learning progress given to user (if supported by object and enabled)
   */
  $app->get('/progress/:ref_id/:user_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch results
      $app->success(Learning::GetProgressResults(intval($refId), intval($userId)));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });
// End of URI group
});
