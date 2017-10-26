<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v2;


// This allows us to use shorter names instead of full namespace quantifier
// Requires: $app to be \RESTController\RESTController::getInstance();
use \RESTController\libs          as Libs;
use \RESTController\libs\RESTAuth as RESTAuth;


// Group implemented routes into common group
//  These route are more administative
$app->group('/v2/courses', function () use ($app) {
  /**
   * <DocIt!!!> Get course + settings
   */
  $app->get('/course/:refid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch and return course data
      $app->success(Admin::GetCourseData($refId));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * <DocIt!!!> Edit course settings
   */
  $app->put('/course/:refid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Fetch input parameters
      $request    = $app->request();
      $parameters = $request->getParameter();

      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Edit course object
      $result = Admin::EditCourse($refId, $parameters);

      // Edit course and return success
      $app->success(array(
        'ref_id' => intval($result->getRefId())
      ));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * <DocIt!!!> Create new course (with settings)
   */
  $app->post('/course', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Fetch input parameters
      $request             = $app->request();
      $parameters          = $request->getParameter();

      // Required parameters
      $parameters['title'] = $request->getParameter('title', null, true);
      $parentRefId         = $request->getParameter('parent', null, true);

      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Edit Course
      $result = Admin::CreateCourse($parentRefId, $parameters);

      // Create course and return updated user data
      $app->success(array(
        'ref_id' => intval($result->getRefId())
      ));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * <DocIt!!!> Delete course
   */
  $app->delete('/course/:refid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch input parameters
      $request    = $app->request();
      $fromSystem = $request->getParameter('from_system', false);

      // Delete course
      $refId = Admin::DeleteCourse($refId, $fromSystem);

      // Send success information
      $app->success(array(
        'ref_id' => intval($refId)
      ));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });
// End of URI group
});
