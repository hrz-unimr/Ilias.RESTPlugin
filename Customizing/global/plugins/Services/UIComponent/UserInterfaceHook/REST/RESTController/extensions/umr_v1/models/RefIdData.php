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
  /**
   *
   */
  public static function getRefIds($refIdString) {
    $refIds       = explode(',', $refIdString);
    foreach($refIds as $key => $refId)
      $refIds[$key] = (is_numeric($refId)) ? intval($refId) : null;
    $refIds = array_filter($refIds, function($value) { return !is_null($value); });

    // TODO: Throw exceptions on !is_numeric($refId)

    return $refIds;
  }


  /**
   *
   */
  public static function getData($accessToken, $refIds) {
    if (!is_array($refIds))
      $refIds = array($refIds);

    $result = array();
    foreach ($refIds as $refId) {
      $ilObject = \ilObjectFactory::getInstanceByRefId($refId, false);


      //print_r(get_class_methods($ilObject));
      //print_r(get_object_vars($ilObject));
      //die;

      // TODO: throw outside loop!

      // TODO: Check access-rights!

      if (!$ilObject)
        throw new \Exception('IMP ME');

      if(\ilObject::_isInTrash($refId))
        throw new \Exception('IMP ME');

      // Todo: Für crs, group, folder, file, etc. mehr (spezielle) daten liefern

      $result[] = array(
          'title' => $ilObject->getTitle(),
          'desc' => $ilObject->getDescription(),
          'owner' => $ilObject->getOwner(),
          'createDate' => $ilObject->getCreateDate(),
          'lastUpdate' => $ilObject->getLastUpdateDate(),
          'importId' => $ilObject->getImportId()
          //type, (desc/long_desc), untranslatedTitle, objectList, id (!= refId)
          //getAllOwnedRepositoryObjects, getLongDescriptions,getGroupedObjTypes, gotItems, getContainerDirectory, getMemberObject, getMembersObject, getSubItems, getOfflineStatus
          // CHILDREN?
      );
    }

    return $result;
  }
}


/*
$courseModel = new Courses\CoursesModel();
$my_courses = $courseModel->getCoursesOfUser($user_id);

$repository_items = array();
foreach ($my_courses as $course_refid)
{
    //$my_courses [] = $course_refid;
    $courseContents = $courseModel->getCourseContent($course_refid);
    $children_ref_ids = array();
    foreach ($courseContents as $item) {
        $children_ref_ids[] = $item['ref_id'];
        $repository_items[$item['ref_id']] = $item;
    }
    $course_item = $courseModel->getCourseInfo($course_refid);
    $course_item['children_ref_ids'] = $children_ref_ids;
    $repository_items[$course_refid] = $course_item;
}
$result['items'] = $repository_items;
*/
