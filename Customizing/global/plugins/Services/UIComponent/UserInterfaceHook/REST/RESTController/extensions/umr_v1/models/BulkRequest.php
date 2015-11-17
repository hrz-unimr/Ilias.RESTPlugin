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
class BulkRequest extends Libs\RESTModel {
  /**
   *
   */
  protected static function fetchDataRecursive($accessToken, $objects) {
    // Iterate over all objects (to find all children refIds)
    $children = array();
    foreach ($objects as $object)
      // Fetch all children with yet unknown refIds
      if ($object['children']) {
        $newChildren  = array_diff($object['children'], $objects);
        $children     = array_unique(array_merge($children, $newChildren), SORT_NUMERIC);
      }

    // Fetch data for all (new) children
    if (count($children) > 0) {
      try {
        $childrenData = Objects::getData($accessToken, $children);
        $newData      = self::fetchDataRecursive($accessToken, $childrenData);
      }
      // Fail silently (but use errorObjects as data)
      catch (Exceptions\Object $e) {
        $newData = $e->getData();
      }

      // Append data
      if (is_array($newData))
        $objects  = $objects + $newData;
    }

    // Return complete data
    return $objects;
  }


  /**
   *
   */
  public static function getBulk($accessToken) {
    // Use models to fetch data
    $calendars  = Calendars::getAllCalendars($accessToken);
    $contacts   = Contacts::getAllContacts($accessToken);
    $events     = Events::getAllEvents($accessToken);
    $user       = UserInfo::getUserInfo($accessToken);
    $cag        = MyCoursesAndGroups::getMyCoursesAndGroups($accessToken);
    $desktop    = PersonalDesktop::getPersonalDesktop($accessToken);
    $news       = News::getAllNews($accessToken);

    // Fetch data for refIds
    $refIds     = array_merge($cag['group_ids'], $cag['course_ids'], $desktop['ref_ids']);
    $refIds     = array_unique($refIds, SORT_NUMERIC);
    if (count($refIds)>0) {
      $objects = Objects::getData($accessToken, $refIds);
      $objects = self::fetchDataRecursive($accessToken, $objects);
    } else {
      $objects = array();
    }

    // Output result
    return array(
      'calendars'  => $calendars,
      'contacts'   => $contacts,
      'events'     => $events,
      'user'       => $user,
      'cag'        => $cag,
      'desktop'    => $desktop,
      'objects'    => $objects,
      'news'       => $news
    );
  }
}
