<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\extensions\courses_v1 as Courses;
use \RESTController\extensions\groups_v1 as Groups;

/**
 *
 */
class Objects extends Libs\RESTModel {
  // Allow to re-use status-messages and status-codes
  const MSG_INVALID_OBJECT  = 'Object with refId %d does not exist.';
  const MSG_OBJECT_IN_TRASH = 'Object with refId %d has been moved to trash.';
  const MSG_NO_ACCESS       = 'Viewing-Access to object with RefId %s was rejected.';
  const MSG_ALL_FAILED      = 'All requests failed, see data-entry for more information.';
  const ID_INVALID_OBJ      = 'RESTController\\extensions\\umr_v1\\Objects::ID_INVALID_OBJ';
  const ID_OBJECT_IN_TRASH  = 'RESTController\\extensions\\umr_v1\\Objects::ID_OBJECT_IN_TRASH';
  const ID_NO_ACCESS        = 'RESTController\\extensions\\umr_v1\\Objects::ID_NO_ACCESS';
  const ID_ALL_FAILED       = 'RESTController\\extensions\\umr_v1\\Objects::ID_ALL_FAILED';


  /**
   *
   */
  protected static function getIlObjData($ilObject) {
    // Return basic information about every ilObject (filter 'null' values)
    return array_filter(
      array(
        'obj_id'      => intval($ilObject->getId()),
        'ref_id'      => intval($ilObject->getRefId()),
        'owner'       => intval($ilObject->getOwner()),
        'type'        => $ilObject->getType(),
        'title'       => $ilObject->getTitle(),
        'desc'        => $ilObject->getDescription(),
        'long_desc'   => $ilObject->getLongDescription(),
        'create_date' => $ilObject->getCreateDate(),
        'last_update' => $ilObject->getLastUpdateDate(),
        'children'    => self::getChildren($ilObject)
      ),
      function($value) { return !is_null($value); }
    );
  }
  protected static function getIlObjCourseOrGroupData($ilObjectCourse) {
    // Fetch basic ilObject information
    $result = self::getIlObjData($ilObjectCourse);

    // Add course/group calendar (if available)
    require_once('./Services/Calendar/classes/class.ilCalendarCategory.php');
    $cat = \ilCalendarCategory::_getInstanceByObjId($result['obj_id']);
    if ($cat && $cat->getCategoryID())
      $result['calendar_id'] = intval($cat->getCategoryID());

    if (is_a($ilObjectCourse, 'ilObjCourse') == true) {
      $crs_model = new Courses\CoursesModel();
      $include_tutors_and_admins = true;
      $result['members'] = $crs_model->getCourseMembers($result['ref_id'], $include_tutors_and_admins);
    } else if (is_a($ilObjectCourse, 'ilObjGroup') == true) {
      $grp_model = new Groups\GroupsModel();
      $result['members'] = $grp_model->getGroupMembers($result['ref_id']);
    }
    return $result;
  }
  protected static function getIlObjFileData($ilObjectFile) {
    // Fetch basic ilObject information
    $result = self::getIlObjData($ilObjectFile);

    // Add additional file information
    $result['file_type'] = $ilObjectFile->getFileType();
    $result['file_size'] = $ilObjectFile->getFileSize();
    $result['version']   = $ilObjectFile->getVersion();

    return $result;
  }


  /**
   *
   */
  protected static function getChildren($ilObject) {
    // Exit if object does not have children (method)
    if (!method_exists($ilObject, 'getSubItems'))
      return null;

    // Fetch sub-items
    $subItems = $ilObject->getSubItems(false, true);
    self::$app->log->debug('subitems '.print_r($subItems,true));
    // Fetch child-items (ref_id only)
    $children = array();
    if (count($subItems) == 0) return null;

    foreach($subItems['_all'] as $child)
      $children[] = intval($child['ref_id']);

    return $children;
  }


  /**
   *
   */
  protected static function getDataForId($accessToken, $refId) {
    // User for access checking
    global $rbacsystem;

    // Fetch object with given refId
    $ilObject = \ilObjectFactory::getInstanceByRefId($refId, false);

    // Throw error if there is no such object
    if (!$ilObject)
      throw new Exceptions\Objects(sprintf(self::MSG_INVALID_OBJECT, $refId), self::ID_INVALID_OBJ);

    // Throw error if object was already deleted
    if(\ilObject::_isInTrash($refId))
      throw new Exceptions\Objects(sprintf(self::MSG_OBJECT_IN_TRASH, $refId), self::ID_OBJECT_IN_TRASH);

    // Throw error
    if(!$rbacsystem->checkAccess('read', $refId))
      throw new Exceptions\Objects(sprintf(self::MSG_NO_ACCESS, $refId), self::ID_NO_ACCESS);

    // Fetch Object data for different object-types
    // Object: Course or Group
    if (is_a($ilObject, 'ilObjCourse') || is_a($ilObject, 'ilObjGroup'))
      return self::getIlObjCourseOrGroupData($ilObject);
    // Object: File
    elseif (is_a($ilObject, 'ilObjFile'))
      return self::getIlObjFileData($ilObject);
    // Object: Folder
    elseif (is_a($ilObject, 'ilObjFolder'))
      return self::getIlObjData($ilObject);
    // Object: Bibliography
    elseif (is_a($ilObject, 'ilObjBibliographic'))
      return self::getIlObjData($ilObject);
    // Object: Blog
    elseif (is_a($ilObject, 'ilObjBlog'))
      return self::getIlObjData($ilObject);
    // Object: Media-Cast
    elseif (is_a($ilObject, 'ilObjMediaCast'))
      return self::getIlObjData($ilObject);
    // Object: Link to Resource
    elseif (is_a($ilObject, 'ilObjLinkResource'))
      return self::getIlObjData($ilObject);
    // Object: Forum
    elseif (is_a($ilObject, 'ilObjForum'))
      return self::getIlObjData($ilObject);
    // Object: Chatroom
    elseif (is_a($ilObject, 'ilObjChatroom'))
      return self::getIlObjData($ilObject);
    // Object: Wiki
    elseif (is_a($ilObject, 'ilObjWiki'))
      return self::getIlObjData($ilObject);
    // Object: Glossary
    elseif (is_a($ilObject, 'ilObjGlossary'))
      return self::getIlObjData($ilObject);
    // Object: Learning-Module (ILIAS)
    elseif (is_a($ilObject, 'ilObjLearningModule'))
      return self::getIlObjData($ilObject);
    // Object: Learning-Module (SCORM)
    elseif (is_a($ilObject, 'ilObjSAHSLearningModule'))
      return self::getIlObjData($ilObject);
    // Object: Learning-Module (HTML)
    elseif (is_a($ilObject, 'ilObjFileBasedLM'))
      return self::getIlObjData($ilObject);
    // Object: Exercise
    elseif (is_a($ilObject, 'ilObjExercise'))
      return self::getIlObjData($ilObject);
    // Object: Media-Pool
    elseif (is_a($ilObject, 'ilObjMediaPool'))
      return self::getIlObjData($ilObject);
    // Object: Session
    elseif (is_a($ilObject, 'ilObjSession'))
      return self::getIlObjData($ilObject);
    // Object: Item-Group
    elseif (is_a($ilObject, 'ilObjItemGroup'))
      return self::getIlObjData($ilObject);
    // Object: RSS/Atom Feed
    elseif (is_a($ilObject, 'ilObjExternalFeed'))
      return self::getIlObjData($ilObject);
    // Object: Data-Collection
    elseif (is_a($ilObject, 'ilObjDataCollection'))
      return self::getIlObjData($ilObject);
    // Object: Category
    elseif (is_a($ilObject, 'ilObjCategory'))
      return self::getIlObjData($ilObject);
    // Fallback solution
    else
      return self::getIlObjData($ilObject);
  }


  /**
   *
   */
  public static function getData($accessToken, $refIds) {
    // Convert to array
    if (!is_array($refIds))
      $refIds = array($refIds);

    // Initialize (global!) $ilUser object
    $userId = $accessToken->getUserId();
    $ilUser = Libs\RESTLib::loadIlUser($userId);
    Libs\RESTLib::initAccessHandling();

    // Return result for each refid
    $result     = array();
    $noSuccess  = true;
    foreach ($refIds as $refId) {
      try {
        $result[$refId] = self::getDataForId($accessToken, $refId);
        $noSuccess      = false;
      }
      catch (Exceptions\Objects $e) {
        // Add error-response for failed refIds
        $responseObject           = Libs\RESTLib::responseObject($e->getMessage(), $e->getRestCode());
        $responseObject['ref_id'] = $refId;
        $result[$refId]           = $responseObject;
      }
    }

    // If EVERY request failed, throw instead
    if ($noSuccess)
      throw new Exceptions\Objects(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }
}
