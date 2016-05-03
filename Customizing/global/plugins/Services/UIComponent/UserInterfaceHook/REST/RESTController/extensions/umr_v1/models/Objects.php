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
        'owner_name'  => \ilObjUser::_lookupFullname($ilObject->getOwner()),
        'type'        => $ilObject->getType(),
        'title'       => $ilObject->getTitle(),
        'desc'        => $ilObject->getDescription(),
        'create_date' => substr_replace($ilObject->getCreateDate(), 'T', 10, 1),
        'last_update' => substr_replace($ilObject->getLastUpdateDate(), 'T', 10, 1),
        'children'    => self::getChildren($ilObject)
      ),
      function($value) { return !is_null($value); }
    );
  }

  /**
   * Returns whether or not a course or a group has a customized page description.
   *
   * @param $obj_id
   * @return bool
   */
  protected static function containerPageExists($obj_id) {
    // (see also ilContainerGUI->getContainerPageHTML)
    include_once("./Services/Container/classes/class.ilContainer.php");

    // old page editor content
    $xpage_id = \ilContainer::_lookupContainerSetting($obj_id, "xhtml_page");

    if ($xpage_id > 0)
    {
      include_once("Services/XHTMLPage/classes/class.ilXHTMLPage.php");
      $xpage = new \ilXHTMLPage($xpage_id);
      $pageContent = $xpage->getContent();
      if (strlen($pageContent)>0) {
        return true;
      }
    }

    // if page does not exist, return nothing
    include_once("./Services/COPage/classes/class.ilPageUtil.php");
    if (!\ilPageUtil::_existsAndNotEmpty("cont", $obj_id))
    {
      return false;
    }

    return true;
  }

  protected static function getIlObjCourseData($ilObjectCourse) {
    // Fetch basic ilObject information
    $result = self::getIlObjData($ilObjectCourse);

    $hasPageDescription = self::containerPageExists($result['obj_id']);
    if ($hasPageDescription==true) {
      $result['page_customization'] = 1;
    } else {
      $result['page_customization'] = 0;
    }

    // Add course/group calendar
    $result['calendar_id'] = self::getCalender($result['obj_id']);

    // Fetch participants
    $result['participants'] = self::getCourseParticipants($ilObjectCourse);

    // Return object-information
    return $result;
  }
  
  protected static function getIlObjGroupData($ilObjectGroup) {
    // Fetch basic ilObject information
    $result = self::getIlObjData($ilObjectGroup);

    $hasPageDescription = self::containerPageExists($result['obj_id']);
    if ($hasPageDescription==true) {
      $result['page_customization'] = 1;
    } else {
      $result['page_customization'] = 0;
    }

    // Add course/group calendar
    $result['calendar_id'] = self::getCalender($result['obj_id']);

    // Fetch participants
    $result['participants'] = self::getGroupParticipants($ilObjectGroup);

    // Return object-information
    return $result;
  }
  protected static function getIlObjFileData($ilObjectFile) {
    // Fetch basic ilObject information
    $result = self::getIlObjData($ilObjectFile);

    // Add additional file information
    $result['file_type'] = $ilObjectFile->getFileType();
    $result['file_size'] = intval($ilObjectFile->getFileSize());
    $result['version']   = intval($ilObjectFile->getVersion());

    // Return object-information
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

    // Fetch child-items (ref_id only)
    $children = array();
    if ($subItems && $subItems['_all'])
      foreach($subItems['_all'] as $child)
        $children[] = intval($child['ref_id']);

    return $children;
  }


  /**
   *
   */
  protected static function getCourseParticipants($ilObjectCourse) {
    // Only add participants if enabled
    if ($ilObjectCourse->getShowMembers() == true) {
      // Fetch participants by group
      $participants = $ilObjectCourse->getMembersObject();

      // Convert to array (containing integer values)
      $members = array();
      foreach($participants->getMembers() as $member)
        $members[] = intval($member);
      $admins = array();
      foreach($participants->getAdmins() as $admin)
        $admins[] = intval($admin);
      $tutors = array();
      foreach($participants->getTutors() as $tutor)
        $tutors[] = intval($tutor);

      // Return participants
      return array(
        'members' => $members,
        'admins'  => $admins,
        'tutors'  => $tutors,
      );
    }

    // Not allowed to see participants
    return null;
  }
  protected static function getGroupParticipants($ilObjectGroup) {
    // Fetch for calculating difference (admins are in members, unlike with courses) [*sarcastic slow-clap*]
    $admins  = array();
    foreach($ilObjectGroup->getGroupAdminIds() as $admin)
      $admins[] = intval($admin);
    $members = array();
    foreach($ilObjectGroup->getGroupMemberIds() as $member)
      if (!in_array(intval($member), $admins))
        $members[] = intval($member);

    // Return members and admins (cleanly!)
    return array(
      'members'  => $members,
      'admins'   => $admins,
    );
  }


  /**
   *
   */
  protected static function getCalender($objectId) {
    // Add course/group calendar (if available)
    require_once('./Services/Calendar/classes/class.ilCalendarCategory.php');
    $category = \ilCalendarCategory::_getInstanceByObjId($objectId);
    if ($category && $category->getCategoryID())
      return intval($category->getCategoryID());

    // No calender for the given object-id
    return null;
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

    // Object: Course or Group
    if (is_a($ilObject, 'ilObjCourse'))
      return self::getIlObjCourseData($ilObject);
    if (is_a($ilObject, 'ilObjGroup'))
      return self::getIlObjGroupData($ilObject);
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
    $ilUser = Libs\RESTilias::loadIlUser($userId);
    Libs\RESTilias::initAccessHandling();

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
        $responseObject           = Libs\RESTResponse::responseObject($e->getRESTMessage(), $e->getRESTCode());
        $responseObject['ref_id'] = $refId;
        $result[$refId]           = $responseObject;
      }
    }

    // If EVERY request failed, throw instead
    if ($noSuccess && count($refIds) > 0)
      throw new Exceptions\Objects(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }
}
