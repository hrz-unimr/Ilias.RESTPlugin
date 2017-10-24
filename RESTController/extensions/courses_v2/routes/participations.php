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
   * <DocIt!!!> Get list of participants
   */
  $app->get('/participants/:refid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch input parameters
      $request = $app->request();
      $roles   = filter_var($request->getParameter('roles', false), FILTER_VALIDATE_BOOLEAN);

      // Fetch all participants
      $app->success(Participants::getParticipants($refId, $roles));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });
  $app->get('/participants/:type/:refid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($type, $refId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Return list of specific participants
      switch (strtolower($type)) {
        case 'admin':
        case 'admins':
          $app->success(Participants::getAdmins($refId));
          break;
        case 'tutor':
        case 'tutors':
          $app->success(Participants::getTutors($refId));
          break;
        case 'member':
        case 'members':
        default:
          $app->success(Participants::getMembers($refId));
          break;
      }
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * <DocIt!!!> Check if participant
   */
  $app->get('/participant/:refid/:userid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch input parameters
      $request = $app->request();
      $roles   = filter_var($request->getParameter('roles', false), FILTER_VALIDATE_BOOLEAN);

      // Return wether is member of certain type
      $result = Participants::isParticipant($refId, $userId, $roles);
      if (is_array($result))
        $app->success($result);
      else
        $app->success(array('result' => $result));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });
  $app->get('/participant/:type/:refid/:userid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($type, $refId, $userId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Fetch input parameters
      $request = $app->request();
      $roles   = filter_var($request->getParameter('roles', false), FILTER_VALIDATE_BOOLEAN);

      // Return wether is member of certain type
      switch ($type) {
        case 'admin':
        case 'admins':
          $result = Participants::isAdmin($refId, $userId);
          break;
        case 'tutor':
        case 'tutors':
          $result = Participants::isTutor($refId, $userId);
          break;
        case 'member':
        case 'members':
          $result = Participants::isMember($refId, $userId);
          break;
        default:
          $result = Participants::isParticipant($refId, $userId, $roles);
          break;
      }
      if (is_array($result))
        $app->success($result);
      else
        $app->success(array('result' => $result));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * <DocIt!!!> Add participant
   */
  $app->post('/participant/:type/:refid/:userid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($type, $refId, $userId) use ($app) {
    // Catch and handle exceptions if possible
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Return wether is member of certain type
      switch ($type) {
        case 'admin':
        case 'admins':
          $result = Participants::addAdmin($refId, $userId);
          break;
        case 'tutor':
        case 'tutors':
          $result = Participants::addTutor($refId, $userId);
          break;
        default:
        case 'member':
        case 'members':
          $result = Participants::addMember($refId, $userId);
          break;
      }
      $app->success(array('result' => $result));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });


  /**
   * <DocIt!!!> Remove participant
   */
  $app->delete('/participant/:refid/:userid', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId, $userId) use ($app) {
    try {
      // Initialize RBAC (user is fetched from access-token)
      Libs\RESTilias::loadIlUser();
      Libs\RESTilias::initAccessHandling();

      // Remove participant and send success
      $result = Participants::removeParticipant($refId, $userId);
      $app->success(array('result' => $result));
    }
    // Catch any exception
    catch (Libs\RESTException $e) {
      $e->send();
    }
  });
// End of URI group
});
