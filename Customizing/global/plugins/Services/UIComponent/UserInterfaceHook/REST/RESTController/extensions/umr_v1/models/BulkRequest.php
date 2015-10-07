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
class BulkRequest {
  /**
   *
   */
  protected static function fetchDataRecursive($accessToken, $refIdData) {
    // Iterate over all objects (to find all children refIds)
    $children = array();
    foreach ($refIdData as $obj)
      // Fetch all children with yet unknown refIds
      if ($obj['children']) {
        $newChildren  = array_diff($obj['children'], $refIdData);
        $children     = array_unique(array_merge($children, $newChildren), SORT_NUMERIC);
      }

    // Fetch data for all (new) children
    if (count($children) > 0) {
      try {
        $childrenData = RefIdData::getData($accessToken, $children);
        $newData      = self::fetchDataRecursive($accessToken, $childrenData);
      }
      // Fail silently (but use errorObjects as data)
      catch (Exceptions\RefIdData $e) {
        $newData = $e->getData();
      }

      // Append data
      if (is_array($newData))
        $refIdData  = $refIdData + $newData;
    }

    // Return complete data
    return $refIdData;
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

    // Fetch data for refIds
    $refIds     = array_merge($cag, $desktop);
    $refIds     = array_unique($refIds, SORT_NUMERIC);
    $refIdData  = RefIdData::getData($accessToken, $refIds);
    $refIdData  = self::fetchDataRecursive($accessToken, $refIdData);

    // Output result
    return array(
      'calendars'  => $calendars,
      'contacts'   => $contacts,
      'events'     => $events,
      'user'       => $user,
      'cag'        => $cag,
      'desktop'    => $desktop,
      'refIdData'  => $refIdData
    );
  }
}
