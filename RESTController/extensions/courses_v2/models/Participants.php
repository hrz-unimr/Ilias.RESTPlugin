<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v2;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs            as Libs;
use \RESTController\libs\Exceptions as LibExceptions;


/**
 * <DocIt!!!>
 */
class Participants extends Libs\RESTModel {
  //
  const MSG_IS_ASSIGNED       = 'This user is already a course-member or is assigned another member role.';
  const ID_IS_ASSIGNED        = 'RESTController\\extensions\\courses_v2\\Participants::ID_IS_ASSIGNED';


  /**
   * <DocIt!!!>
   */
  protected static function checkExists($refId) {
    // Check that object (of type course or course-reference) exists
    if (!\ilObject::_exists($refId, true, 'crs') && !\ilObject::_exists($refId, true, 'crsr'))
      throw new LibExceptions\ilObject(
        Libs\RESTilias::MSG_NO_OBJECT_BY_REF,
        Libs\RESTilias::ID_NO_OBJECT_BY_REF,
        array(
          'ref_id' => $refId
        )
      );
  }


  /**
   * <DocIt!!!>
   */
  protected static function checkWrite($refId) {
    global $rbacsystem;

    // Check for required permissions on object
    if (!$rbacsystem->checkAccess('write', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_WRITE_DENIED,
        Libs\RESTilias::ID_RBAC_WRITE_DENIED,
        array(
          'object' => 'course-object'
        )
      );
  }


  /**
   * <DocIt!!!>
   */
  protected static function checkRead($refId) {
    global $rbacsystem;

    // Check for required permissions on object
    if (!$rbacsystem->checkAccess('read', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_READ_DENIED,
        Libs\RESTilias::ID_RBAC_READ_DENIED,
        array(
          'object' => 'course-object'
        )
      );
  }


  /**
   *
   */
  protected static function checkUser($userId) {
    // Check if user does exist
    if (!\ilObjectFactory::getInstanceByObjId($userId ,false))
      throw new LibExceptions\ilUser(
        Libs\RESTilias::MSG_NO_USER_BY_ID,
        Libs\RESTilias::ID_NO_USER_BY_ID
      );
  }


  /**
   * <DocIt!!!>
   */
  public static function getParticipants($refId, $roles = false) {
    self::checkExists($refId);
    self::checkRead($refId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    if ($roles)
      return array(
        'admins'  => array_map('intval', $membersObj->getAdmins()),
        'tutors'  => array_map('intval', $membersObj->getTutors()),
        'members' => array_map('intval', $membersObj->getMembers()),
      );
    else
      return array(
        'ids' => array_map('intval', $membersObj->getParticipants())
      );
  }
  public static function getAdmins($refId) {
    self::checkExists($refId);
    self::checkRead($refId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    return array(
      'ids' => array_map('intval', $membersObj->getAdmins())
    );
  }
  public static function getTutors($refId) {
    self::checkExists($refId);
    self::checkRead($refId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    return array(
      'ids' => array_map('intval', $membersObj->getTutors())
    );
  }
  public static function getMembers($refId) {
    self::checkExists($refId);
    self::checkRead($refId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    return array(
      'ids' => array_map('intval', $membersObj->getMembers())
    );
  }


  /**
   * <DocIt!!!>
   */
  public static function addAdmin($refId, $userId) {
    self::checkExists($refId);
    self::checkWrite($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    if (!$membersObj->isAssigned($userId)) {
      $membersObj->add($userId, IL_CRS_ADMIN);
      $membersObj->sendNotification($membersObj->NOTIFY_ACCEPT_USER, $userId);
      $courseObject->checkLPStatusSync($userId);
      return true;
    }
    else
      throw new LibExceptions\ilUser(
        self::MSG_IS_ASSIGNED,
        self::ID_IS_ASSIGNED
      );
  }
  public static function addTutor($refId, $userId) {
    self::checkExists($refId);
    self::checkWrite($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    if (!$membersObj->isAssigned($userId)) {
      $membersObj->add($userId, IL_CRS_TUTOR);
      $membersObj->sendNotification($membersObj->NOTIFY_ACCEPT_USER, $userId);
      $courseObject->checkLPStatusSync($userId);
      return true;
    }
    else
      throw new LibExceptions\ilUser(
        self::MSG_IS_ASSIGNED,
        self::ID_IS_ASSIGNED
      );
  }
  public static function addMember($refId, $userId) {
    self::checkExists($refId);
    self::checkWrite($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    if (!$membersObj->isAssigned($userId)) {
      $membersObj->add($userId, IL_CRS_MEMBER);
      $membersObj->sendNotification($membersObj->NOTIFY_ACCEPT_USER, $userId);
      $courseObject->checkLPStatusSync($userId);
      return true;
    }
    else
      throw new LibExceptions\ilUser(
        self::MSG_IS_ASSIGNED,
        self::ID_IS_ASSIGNED
      );
  }


  /**
   * <DocIt!!!>
   */
  public static function removeParticipant($refId, $userId) {
    self::checkExists($refId);
    self::checkWrite($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    if ($membersObj->isAssigned($userId)) {
      $membersObj->delete($userId);
      return true;
    }
    return false;
  }


  /**
   * <DocIt!!!>
   */
  public static function isParticipant($refId, $userId, $roles = false) {
    self::checkExists($refId);
    self::checkRead($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    if ($roles === false)
      return $membersObj->isAssigned($userId);
    elseif ($membersObj->isAdmin($userId))
      return 'admin';
    elseif ($membersObj->isTutor($userId))
      return 'tutor';
    elseif ($membersObj->isMember($userId))
      return 'member';
    else
      return false;
  }
  public static function isAdmin($refId, $userId) {
    self::checkExists($refId);
    self::checkRead($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    return $membersObj->isAdmin($userId);
  }
  public static function isTutor($refId, $userId) {
    self::checkExists($refId);
    self::checkRead($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    return $membersObj->isTutor($userId);
  }
  public static function isMember($refId, $userId) {
    self::checkExists($refId);
    self::checkRead($refId);
    self::checkUser($userId);

    $courseObject = \ilObjectFactory::getInstanceByRefId($refId);
    $membersObj   = $courseObject->getMembersObject();
    return $membersObj->isMember($userId);
  }
}
