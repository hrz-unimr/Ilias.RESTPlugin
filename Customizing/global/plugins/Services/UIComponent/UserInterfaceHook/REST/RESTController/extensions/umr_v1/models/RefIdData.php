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


/**
 *
 */
class RefIdData {
  // Allow to re-use status-messages and status-codes
  const MSG_INVALID_OBJECT  = 'Object with refId %s does not exist.';
  const MSG_OBJECT_IN_TRASH = 'Object with refId %s has been moved to trash.';
  const MSG_NO_ACCESS       = 'Viewing-Access to object with RefId %s was rejected.';
  const ID_INVALID_OBJ      = 'RESTController\\extensions\\umr_v1\\RefIdData::ID_INVALID_OBJ';
  const ID_OBJECT_IN_TRASH  = 'RESTController\\extensions\\umr_v1\\RefIdData::ID_OBJECT_IN_TRASH';
  const ID_NO_ACCESS        = 'RESTController\\extensions\\umr_v1\\RefIdData::ID_NO_ACCESS';


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
      throw new Exceptions\RefIdData(sprintf(self::MSG_INVALID_OBJECT, $refId), self::ID_INVALID_OBJ);

    // Throw error if object was already deleted
    if(\ilObject::_isInTrash($refId))
      throw new Exceptions\RefIdData(sprintf(self::MSG_OBJECT_IN_TRASH, $refId), self::ID_OBJECT_IN_TRASH);

    // Throw error
    if(!$rbacsystem->checkAccess('read', $refId))
      throw new Exceptions\RefIdData(sprintf(self::MSG_NO_ACCESS, $refId), self::ID_NO_ACCESS);

    // Fetch Object data for different object-types
    // Object: Course or Group
    if (is_a($ilObject, 'ilObjCourse') || is_a($ilObject, 'ilObjGroup'))
      return self::getIlObjCourseOrGroupData($ilObject);
    // Object: File
    elseif (is_a($ilObject, 'ilObjFile'))
      return self::getIlObjFileData($ilObject);
    // Object: Folder
    elseif (is_a($ilObject, 'ilObjFolder'))
      return self::getIlData($ilObject);
    // Object: Bibliography
    elseif (is_a($ilObject, 'ilObjBibliographic'))
      return self::getIlData($ilObject);
    // Object: Blog
    elseif (is_a($ilObject, 'ilObjBlog'))
      return self::getIlData($ilObject);
    // Object: Media-Cast
    elseif (is_a($ilObject, 'ilObjMediaCast'))
      return self::getIlData($ilObject);
    // Object: Link to Resource
    elseif (is_a($ilObject, 'ilObjLinkResource'))
      return self::getIlData($ilObject);
    // Object: Forum
    elseif (is_a($ilObject, 'ilObjForum'))
      return self::getIlData($ilObject);
    // Object: Chatroom
    elseif (is_a($ilObject, 'ilObjChatroom'))
      return self::getIlData($ilObject);
    // Object: Wiki
    elseif (is_a($ilObject, 'ilObjWiki'))
      return self::getIlData($ilObject);
    // Object: Glossary
    elseif (is_a($ilObject, 'ilObjGlossary'))
      return self::getIlData($ilObject);
    // Object: Learning-Module (ILIAS)
    elseif (is_a($ilObject, 'ilObjLearningModule'))
      return self::getIlData($ilObject);
    // Object: Learning-Module (SCORM)
    elseif (is_a($ilObject, 'ilObjSAHSLearningModule'))
      return self::getIlData($ilObject);
    // Object: Learning-Module (HTML)
    elseif (is_a($ilObject, 'ilObjFileBasedLM'))
      return self::getIlData($ilObject);
    // Object: Exercise
    elseif (is_a($ilObject, 'ilObjExercise'))
      return self::getIlData($ilObject);
    // Object: Media-Pool
    elseif (is_a($ilObject, 'ilObjMediaPool'))
      return self::getIlData($ilObject);
    // Object: Session
    elseif (is_a($ilObject, 'ilObjSession'))
      return self::getIlData($ilObject);
    // Object: Item-Group
    elseif (is_a($ilObject, 'ilObjItemGroup'))
      return self::getIlData($ilObject);
    // Object: RSS/Atom Feed
    elseif (is_a($ilObject, 'ilObjExternalFeed'))
      return self::getIlData($ilObject);
    // Object: Data-Collection
    elseif (is_a($ilObject, 'ilObjDataCollection'))
      return self::getIlData($ilObject);
    // Object: Category
    elseif (is_a($ilObject, 'ilObjCategory'))
      return self::getIlData($ilObject);
    // Fallback solution
    else
      return self::getIlData($ilObject);
  }


  /**
   *
   */
  protected static function getIlData($ilObject) {
    // Return basic information about every ilObject (filter 'null' values)
    return array_filter(
      array(
        'obj_id'      => intval($ilObject->getId()),
        'ref_id'      => intval($ilObject->getRefId()),
        'type'        => $ilObject->getType(),
        'title'       => $ilObject->getTitle(),
        'desc'        => $ilObject->getDescription(),
        'long_desc'   => $ilObject->getLongDescription(),
        'owner'       => $ilObject->getOwner(),
        'createDate'  => $ilObject->getCreateDate(),
        'lastUpdate'  => $ilObject->getLastUpdateDate(),
        'children'    => self::getChildren($ilObject)
      ),
      function($value) { return !is_null($value); }
    );
  }
  protected static function getIlObjCourseOrGroupData($ilObjectCourse) {
    // Fetch basic ilObject information
    $result = self::getIlData($ilObjectCourse);


    // TODO:
    //  * Add reference to calendar (calendarId)
    require_once('./Services/Calendar/classes/class.ilCalendarCategory.php');
    $cat = \ilCalendarCategory::_getInstanceByObjId($result['obj_id']);
    $result['category_id'] = $cat->getCategoryID();


    return $result;
  }
  protected static function getIlObjFileData($ilObjectFile) {
    // Fetch basic ilObject information
    $result = self::getIlData($ilObjectFile);

    // Add additional file information
    $result['fileType'] = $ilObjectFile->getFileType();
    $result['fileSize'] = $ilObjectFile->getFileSize();
    $result['version']  = $ilObjectFile->getVersion();

    return $result;
  }


  /**
   *
   */
  protected static function getChildren($ilObject) {
    //
    if (!method_exists($ilObject, 'getSubItems'))
      return null;

    // Fetch sub-items
    $subItems = $ilObject->getSubItems(false, true);

    // Fetch child-items (ref_id only)
    $children = array();
    foreach($subItems['_all'] as $child)
      $children[] = $child['ref_id'];

    return $children;
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
    $result = array();
    foreach ($refIds as $refId)
      $result[] = self::getDataForId($accessToken, $refId);

    return $result;
  }
}
