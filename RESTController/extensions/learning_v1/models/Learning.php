<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\learning_v1;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs            as Libs;
use \RESTController\libs\Exceptions as LibExceptions;


// Include required classes to fetch LP information
include_once('Modules/Course/classes/Objectives/class.ilLOTestAssignments.php');
include_once('Modules/Course/classes/Objectives/class.ilLOUserResults.php');
include_once('Services/Tracking/classes/class.ilLearningProgressBaseGUI.php');
include_once('Services/Tracking/classes/class.ilLearningProgress.php');
include_once('Services/Tracking/classes/class.ilLPStatusFactory.php');
include_once('Services/Tracking/classes/class.ilLPStatusWrapper.php');
include_once('Services/Tracking/classes/class.ilLPObjSettings.php');
include_once('Services/Tracking/classes/class.ilLPStatus.php');
include_once('Services/Tracking/classes/class.ilTrQuery.php');
include_once('Services/Object/classes/class.ilObjectLP.php');


/**
 * Class: Learning
 *  Implements fetching of learning objective and progress information.
 */
class Learning extends Libs\RESTModel {
  // Define reusable class messaging constants
  const MSG_TYPE_UNSUPPORTED  = 'Objects of type \'{{type}}\' do not support learning-progress.';
  const ID_TYPE_UNSUPPORTED   = 'RESTController\\extensions\\learning_v1\\Learning::ID_TYPE_UNSUPPORTED';


  /**
   * Function: ValidateInput($refId)
   *  Validates that object with given object-id exists and
   *  supports learning-progress. This does not check if
   *  if learning-progress is actually enabled and configured
   *  for this object. This will throw an exception!
   *
   * Parameters:
   *  refId <Numeric> Reference-Id of ILIAS object
   */
  protected static function ValidateInput($refId) {
    // Make sure object exists by trying to fetch its type
    if (!\ilObject::_lookupType($refId, true))
      throw new LibExceptions\ilObject(
        Libs\RESTilias::MSG_NO_OBJECT_BY_OBJ,
        Libs\RESTilias::ID_NO_OBJECT_BY_OBJ,
        array(
          'ref_id' => $refId
        )
      );

    // Check wether this type has support for learning-progress
    if (!self::IsSupported($refId))
      throw new LibExceptions\Parameter(
        self::MSG_TYPE_UNSUPPORTED,
        self::ID_TYPE_UNSUPPORTED,
        array(
          'type'   => \ilObject::_lookupType($refId, true),
          'ref_id' => $refId
        )
      );
  }


  /**
   * Function: CheckRead($refId)
   *  Checks if user has read-access to object
   *
   * Parameters:
   *  refId <Numeric> - Reference-ID of object to check acccess too
   */
  protected function CheckRead($refId) {
    global $rbacsystem;

    if (!$rbacsystem->checkAccess('read', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_READ_DENIED,
        Libs\RESTilias::ID_RBAC_READ_DENIED,
        array(
          'object' => 'learning-progress supported object'
        )
      );
  }


  /**
   * Function: CheckWrite($refId)
   *  Checks if user has write-access to object
   *
   * Parameters:
   *  refId <Numeric> - Reference-ID of object to check acccess too
   */
  protected function CheckWrite($refId) {
    global $rbacsystem;

    if (!$rbacsystem->checkAccess('write', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_READ_DENIED,
        Libs\RESTilias::ID_RBAC_READ_DENIED,
        array(
          'object' => 'learning-progress supported object'
        )
      );
  }


  /**
   * Function: IsSupported($refId)
   *  Checks wether given object supports learning-progress.
   *
   * Parameters:
   *  refId <Numeric> Reference-Id of ILIAS object
   *
   * Return:
   *  <Boolean> True if this object (type) supports learning-progress
   */
  protected static function IsSupported($refId) {
    // Fetch type of object and  check lp support
    $type = \ilObject::_lookupType($refId, true);
    return \ilObjectLP::isSupportedObjectType($type);
  }


  /**
   * Function: GetDetails($refId)
   *  Get datails about item(s) that support learning progress.
   *
   * Parameters:
   *  $refId <Numeric> Reference-Id of object to fetch learning-prgress realted information for
   *
   * Return:
   *  <Array>
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
  public static function GetDetails($refId) {
    // Validate input before any further processing (throws on error)
    self::ValidateInput($refId);
    self::CheckRead($refId);

    // Fetch details
    $objId  = \ilObject::_lookupObjectId($refId);
    $object = \ilObjectLP::getInstance($objId);
    $type   = \ilObject::_lookupType($objId);
    $mode   = $object->getCurrentMode();

    // Compile details...
    return array(
      'ref_id'       => intval($refId),
      'obj_id'       => intval($objId),
      'title'        => \ilObject::_lookupTitle($objId),
      'description'  => \ilObject::_lookupDescription($objId),
      'type'         => $type,
      'online'       => !\ilLearningProgressBaseGUI::isObjectOffline($objId),
      'mode'         => $mode,
      'active'       => $object->isActive(),
      'supports'     => array(
        'spend_seconds' => \ilObjectLP::supportsSpentSeconds($type),
        'marks'         => \ilObjectLP::supportsMark($type),
        'matrix_view'   => \ilObjectLP::supportsMatrixView($type),
      ),
      'participants' => self::GetParticipants($object, $objId),
      'collection'   => self::GetCollectionItems($object, $refId),
      'objectives'   => self::GetObjectives($object, $objId),
      'scos'         => self::GetSCOs($object, $refId),
    );
  }


  /**
   * Function: GetParticipants($object, $objId)
   *  Returns a list of user-id that are participants of this object. (Does not include admins, tutor, etc.)
   *
   * Parameters:
   *  object <ilObjectLP> Object to fetch participants of
   *  objId <Numeric> Object-Id of ILIAS object
   *
   * Return:
   *  <Array> Numeric user-id list of all participants
   */
  protected static function GetParticipants($object, $objId) {
    // Convert all member ids to numeric values 'because ILIAS'
    $members = $object->getMembers();
    if (is_array($members))
      return array_map('intval', $members);
    // Handle special classes for which ILIAS does not offer member on the ilObjectLP object (Thanks ILIAS!)
    else {
      // Fetch normal object if ilObjectLP can't help us...
      $factory = new \ilObjectFactory();
      $obj     = $factory->getInstanceByObjId($objId);

      // Special case for tests required
      if ($obj instanceof \ilObjTest) {
        // Fetch participants and only extract user-ids
        $participants = $obj->getTestParticipants();
        $mapped       = array();
        foreach($participants as $participant)
          $mapped[] = intval($participant['usr_id']);

        // Return mapped user-ids for test
        return $mapped;
      }
    }
    return array();
  }


  /**
   * Function: GetCollectionItems($object, $refId)
   *  Fetch list of children that play a part in calculating the learning progress.
   *
   * Parameters:
   *  $object <ilObjectLP> Object to fetch items for
   *  $refId <Numeric> Reference-Id of object to fetch learning-prgress realted information for
   *
   * Returns:
   *  <Array> Reference-Ids of children objects which are part of the learning progress calculation
   */
  protected static function GetCollectionItems($object, $refId) {
    global $ilAccess;

    // Early exit
    if (!in_array($object->getCurrentMode(), array(
      \ilLPObjSettings::LP_MODE_COLLECTION,
      \ilLPObjSettings::LP_MODE_COLLECTION_TLT,
      \ilLPObjSettings::LP_MODE_COLLECTION_MOBS,
      \ilLPObjSettings::LP_MODE_COLLECTION_MOBS
    )))
      return null;

    // Fetch collection object
    $collection = $object->getCollectionInstance();

    // Prefare the table-data method as is also fetches Scorm items
    if (isset($collection)) {
      $items = $collection->getItems();
      $items = array_map('intval', $items);
      return array_filter($items, function ($item) use($ilAccess) {
        return $ilAccess->checkAccess('visible', '', $item);
      });
    }

    // Fallback
    return null;
  }


  /**
   * Function: GetObjectives($objId)
   *  Returns information about all learning objectives for given ILIAS object.
   *
   * Parameters:
   *  object <ilObjectLP> Object to fetch objectives of
   *  objId <Numeric> Object-Id of ILIAS object
   *
   * Return:
   *  <Array> List of learning objectives with additional information indexed by objective-id
   *   [objective_id]
   *    id <Numeric> Numeric id of learning-objective
   *    title <String> Title of learning-objective
   *    description <String> Description of learning-objective
   *    initial <Numeric> Reference-Id of initial test required for learning-objective
   *    qualified <Numeric> Reference-Id of qualified test required for learning-objective
   */
  protected static function GetObjectives($object, $objId) {
    // Early exit
    if (!in_array($object->getCurrentMode(), array(
      \ilLPObjSettings::LP_MODE_OBJECTIVES
    )))
      return null;

    // Fetch learning objective information and helper to query attached tests
    $ass    = \ilLOTestAssignments::getInstance($objId);
    $class  = \ilLPStatusFactory::_getClassById($objId);
    $lp     = new $class($objId);
    $status = $lp->_getStatusInfo($objId);

    // Iterate over all objectives and extract information about it
    $result = array();
    if (is_array($status) && array_key_exists('objectives', $status))
      foreach ($status['objectives'] as $objectiveId) {
        $initial   = intval($ass->getTestByObjective($objectiveId, \ilLOSettings::TYPE_TEST_INITIAL));
        $qualified = intval($ass->getTestByObjective($objectiveId, \ilLOSettings::TYPE_TEST_QUALIFIED));

        $result[intval($objectiveId)] = array(
          'id'          => intval($objectiveId),
          'title'       => $status['objective_title'][$objectiveId],
          'description' => $status['objective_description'][$objectiveId],
          'initial'     => ($initial === 0) ? null : $initial,
          'qualified'   => ($qualified === 0) ? null : $qualified,
        );
      }
    // There are no objectives to be found?!
    else
      return null;

    // Return final result
    return $result;
  }


  /**
   * Function: GetSCOs($object, $refId)
   *  Fetch list of scorm shareable comtemt objects that play a part in calculating the learning progress.
   *
   * Parameters:
   *  $object <ilObjectLP> Object to fetch items for
   *  $refId <Numeric> Reference-Id of object to fetch learning-prgress realted information for
   *
   * Returns:
   *  <Array> Object-Ids of shareable comtemt objects which are part of the learning progress calculation
   */
  protected static function GetSCOs($object, $refId) {
    // Early exit
    if (!in_array($object->getCurrentMode(), array(
      \ilLPObjSettings::LP_MODE_SCORM,
      \ilLPObjSettings::LP_MODE_SCORM_PACKAGE,
    )))
      return null;

    // Fetch collection object
    $collection = $object->getCollectionInstance();

    // Prefare the table-data method as is also fetches Scorm items
    if (isset($collection) && method_exists($collection, 'getTableGUIData')) {
      $items = $collection->getTableGUIData($refId);
      return array_map(function($item) {
        return intval(($item['ref_id'] != 0) ? $item['ref_id'] : $item['id']);
      }, $items);
    }

    // Fallback
    return null;
  }


  /**
   * Function: GetProgressResults($refId, $userId)
   *  Returns the full learning progress information of the given object
   *  for all users.
   *
   * Parameters:
   *  $refId <Numeric> Refernce-Id of object to fetch learning-progress results for
   *  $userId <Numeric> [Optional] Only select learning-progress result for given user
   *
   * Returns:
   *  <Array> Learning progress result of all users index by user-id
   *   [user-id] <Array>
   *    status <Numeric> Learning-progress status of user for given object (@See ilLPStatus -> LP_STATUS_*)
   *    percent <Numeric> Percentage value of learning-progress of user for given object (if supported by object)
   *    status_changed <String> Time when status was (last) updated (ISO 8601 - Date & Time)
   *    access_time <String> Time when the user last accessed the attached object (ISO 8601 - Date & Time)
   *    read_count <Numeric> ???
   *    spent_seconds <Numeric> Time spend by user on improving learning-progress (if supported by object)
   *    visits <Numeric> Number of 'visits' (attempts?) by user on learning-progress (if supported by object)
   *    mark <String> Mark for learning progress given to user (if supported by object and enabled)
   *    comment <String> Comment for learning progress given to user (if supported by object and enabled)
   */
  public static function GetProgressResults($refId, $userId_ = null) {
    global $ilAccess, $ilObjDataCache;

    // Validate input before any further processing (throws on error)
    self::ValidateInput($refId);
    self::CheckWrite($refId);

    // Fetch sub-item status
    $objId      = \ilObject::_lookupObjectId($refId);
    $type       = \ilObject::_lookupType($objId);
    $object     = \ilObjectLP::getInstance($objId);
    $collection = $object->getCollectionInstance();
    $mode       = $object->getCurrentMode();

    // Fetch (and filter) members
    $members    = self::GetParticipants($object, $objId);
    $members    = (isset($userId_) && in_array($userId, $members)) ? array($userId) : $members;

    // Return empty result
    if (!is_array($members))
      return array();

    // Fetch status of all users for this object
    $results = array();
    foreach ($members as $userId) {
      // Fetch access-time via Learning-Progress
      $progress = \ilLearningProgress::_getProgress($userId, $objId);
      $status   = \ilTrQuery::getObjectsStatusForUser($userId, array($objId => intval($refId)));

      // Build status information for this user
      $status = reset($status);
      $results[$userId] = array(
        'status'         => (isset($status['status'])) ? $status['status'] : intval(\ilLPStatus::_lookupStatus($objId, $userId)),
        'percent'        => (self::HasPercentage($mode)) ? intval($status['percentage']) : null,
        'status_changed' => isset($status['status_changed']) ? (new \DateTime($status['status_changed']))->format(\DateTime::ISO8601) : null,
        'access_time'    => (isset($progress['access_time'])) ? date(\DateTime::ISO8601, intval($progress['access_time'])) : null,
        'read_count'     => $status['read_count'],
        'spent_seconds'  => $status['spent_seconds'],
        'visits'         => isset($status['visits']) ? intval($status['visits']) : null,
        'mark'           => $status['mark'],
        'comment'        => $status['comment'],
      );
    }

    // Squash array if selecting data for user and/or sub-item
    if (isset($userId_))
      $results = array_key_exists($userId_, $results) ? $results[$userId_] : array();

    // Return final compilation of values
    return $results;
  }


  /**
   * Function: GetObjectiveResults($refId, $userId, $objectiveId)
   *  Returns the learning objectvive result information of all users in given course
   *  for all objectives or an empty array if no information is available.
   *
   * Parameters:
   *  refId <Numeric> Reference-Id of ILIAS object to fetch learning objective result information for
   *  userId <Numeric> [Optional] User-Id to fetch learning objective result information for
   *  objectiveId <Numeric> [Optional] Learning objective id to fetch information for
   *
   * Return:
   *  <Array> List of learning objective result information for all users, indexed by user_id
   *   [user_id] => array(
   *    [objective_id] => array(
   *     initial <Array> [Optional] Contains learning objective results for the initial test
   *      status <Numeric> 0 Unknown, 1 Completed, 2 Failed (@See ilLOUserResults,  STATUS_*)
   *      result <Numeric> Percent value achieved for learning objective
   *      limit <Numeric> Percent value need to succeed learning objective
   *      tries <Numeric> Number of tries for learning objective
   *      final <Boolean> True wether learning objective was finalized (no further tries allowed)
   *     qualified <Array> [Optional] Contains learning objective results for the qualified test
   *      [...] @See 'initial'
   *    )
   *   )
   */
  public static function GetObjectiveResults($refId, $userId_ = null, $objectiveId_ = null) {
    // Validate input before any further processing (throws on error)
    self::ValidateInput($refId);
    self::CheckWrite($refId);

    // Fech objects members/participants to iterate over
    $result = array();
    $objId  = intval(Libs\RESTilias::getObjId($refId));
    $object = \ilObjectLP::getInstance($objId);

    // Early exit
    if (!in_array($object->getCurrentMode(), array(
      \ilLPObjSettings::LP_MODE_OBJECTIVES
    )))
      return array();

    // Fetch list of users to process
    $members = self::GetParticipants($object, $objId);
    if (isset($userId_) && !in_array($userId_, $members))
      return array();
    $members = (isset($userId_)) ? array($userId_) : $members;

    // Fetch learning-objective progress for all or single member
    foreach ($members as $userId) {
      // Fetch learning objective(s) for given user/member
      $lur = new \ilLOUserResults($objId, $userId);
      $lp  = $lur->getCourseResultsForUserPresentation();

      // We like to convert data for each objective
      $result[$userId] = array();

      // Exit if user is not a learning member
      if (isset($objectiveId_) && !array_key_exists($objectiveId_, $lp))
        return array();

      // Fetch learning-objective progress for all or single objective
      $lp = (isset($objectiveId_)) ? array($objectiveId_ => $lp[$objectiveId_]) : $lp;
      foreach ($lp as $objectiveId => $status) {
        // Extract information for initial and qualified test
        $initial   = $status[\ilLOUserResults::TYPE_INITIAL];
        $qualified = $status[\ilLOUserResults::TYPE_QUALIFIED];

        // We'll return information under string key instead of numeric ones
        // Also convert from ILIAS lp format
        $result[$userId][$objectiveId] = array();
        if (isset($initial))
          $result[$userId][$objectiveId]['initial']   = self::ConvertResults($initial);
        if (isset($qualified))
          $result[$userId][$objectiveId]['qualified'] = self::ConvertResults($qualified);
      }
    }

    // Squash array if selecting data for user and/or objective
    if (isset($userId_)) {
      $result = array_key_exists($userId_, $result) ? $result[$userId_] : array();
      if (isset($objectiveId_)) {
        $result = array_key_exists($objectiveId_, $result) ? $result[$objectiveId_] : array();
      }
    }
    return $result;
  }





  /**
   * Function: GetSCOResults($refId, $userId, $scoId)
   *  Fetch learning progress information for shareable content objects that are part of a scorm module object.
   *
   * Parameters:
   *  refId <Numeric> Reference-Id of ILIAS scorm object to fetch learning objective result information for
   *  userId <Numeric> [Optional] User-Id to fetch learning objective result information for
   *  scoId <Numeric> [Optional] Internal id for shareable content object to fetch learning objective result information for
   *
   * Return:
   *  <Array> Array containing learning-objective information indexed by user-ids
   *   [user-id] <Array> Array containing learning-objective information indexed by sco-ids
   *    [sco-id] <Array> Array containing the actual learning progess information for this user on this sco
   *     status <Numeric> Status of user for sco in ILIAS scorm object (@See ilLPStatus -> LP_STATUS_*_NUM)
   *     score <Numeric> Score that was reached by user for sco in ILIAS scorm object
   */
  public static function GetSCOResults($refId, $userId_ = null, $scoId_ = null) {
    // Validate input before any further processing (throws on error)
    self::ValidateInput($refId);
    self::CheckWrite($refId);

    // Fech objects members/participants to iterate over
    $result = array();
    $objId  = intval(Libs\RESTilias::getObjId($refId));
    $object = \ilObjectLP::getInstance($objId);
    $scoIds = self::GetSCOs($object, $refId);

    // Early exit
    if (!in_array($object->getCurrentMode(), array(
      \ilLPObjSettings::LP_MODE_SCORM,
      \ilLPObjSettings::LP_MODE_SCORM_PACKAGE,
    )) || !is_array($scoIds))
      return array();

    // Fetch list of users to process
    $members = self::GetParticipants($object, $objId);
    if (isset($userId_) && !in_array($userId_, $members))
      return array();
    $members = (isset($userId_)) ? array($userId_) : $members;

    // Map string to numeric identifier
    $statusMapping = array(
      \ilLPStatus::LP_STATUS_NOT_ATTEMPTED => \ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM,
      \ilLPStatus::LP_STATUS_IN_PROGRESS   => \ilLPStatus::LP_STATUS_IN_PROGRESS_NUM,
      \ilLPStatus::LP_STATUS_COMPLETED     => \ilLPStatus::LP_STATUS_COMPLETED_NUM,
      \ilLPStatus::LP_STATUS_FAILED        => \ilLPStatus::LP_STATUS_FAILED_NUM,
    );

    // Fetch learning-objective progress for all or single member
    $result = array();
    foreach ($members as $userId) {
      $userStatus = array();

      // Map the status to be more exportable format
      $status = \ilTrQuery::getSCOsStatusForUser($userId, $objId, $scoIds);
      foreach ($status as $scoId => $scoStatus)
        $userStatus[intval($scoId)] = array(
          'status' => $statusMapping[$scoStatus['status']],
          'score'  => $scoStatus['score'],
        );

      $result[intval($userId)] = $userStatus;
    }

    // Squash array if selecting data for user and/or objective
    if (isset($userId_)) {
      $result = array_key_exists($userId_, $result) ? $result[$userId_] : array();
      if (isset($scoId_)) {
        $result = array_key_exists($scoId_, $result) ? $result[$scoId_] : array();
      }
    }
    return $result;
  }


  /**
   * Function: HasPercentage($mode)
   *  Checks wether the current mode supports percentage values.
   *
   * Parameter:
   *  $mode <Numeric> Internal numeric leraning-progress code (@ee ilLPObjSettings)
   *
   * Returns:
   *  <Boolean>  True wether the mode supports percentage values
   */
  protected static function HasPercentage($mode) {
    return in_array($mode, array(
      \ilLPObjSettings::LP_MODE_TLT,
      \ilLPObjSettings::LP_MODE_VISITS,
      \ilLPObjSettings::LP_MODE_SCORM,
      \ilLPObjSettings::LP_MODE_VISITED_PAGES,
      \ilLPObjSettings::LP_MODE_TEST_PASSED
    ));
  }


  /**
   * Function: ConvertResults($results)
   *  Utility message to convert from ILIAS internal learning objective result
   *  representation to a more output-firendly format.
   *
   * Parameters:
   *  <Array> The learning-objective result information array as stored in database with keys
   *          (status, result_perc, limit_perc, tries, is_final)
   *
   * Return:
   *  <Array>
   *   status <Numeric> 0 Unknown, 1 Completed, 2 Failed (@See ilLOUserResults,  STATUS_*)
   *   result <Numeric> Percent value achieved for learning-objective
   *   limit <Numeric> Percent value need to succeed learning-objective
   *   tries <Numeric> Number of tries for learning-objective
   *   final <Boolean> True wether learning-objective was finalized (no further tries allowed)
   */
  protected static function ConvertResults($results) {
    return array(
      'status' => intval($results['status']),
      'result' => intval($results['result_perc']),
      'limit'  => intval($results['limit_perc']),
      'tries'  => intval($results['tries']),
      'final'  => boolval($results['is_final']),
    );
  }
}
