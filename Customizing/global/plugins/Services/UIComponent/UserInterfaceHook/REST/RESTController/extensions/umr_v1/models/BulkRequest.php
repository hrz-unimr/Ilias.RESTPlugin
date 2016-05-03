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

    if (count($objects) > 0)
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
    $calendars    = Calendars::getAllCalendars($accessToken);
    $contacts     = Contacts::getAllContacts($accessToken);
    $events       = Events::getAllEvents($accessToken);
    $user         = UserInfo::getFullUserInfo($accessToken);
    $cag          = MyCoursesAndGroups::getMyCoursesAndGroups($accessToken);
    $desktop      = PersonalDesktop::getPersonalDesktop($accessToken);
    $news         = News::getAllNews($accessToken);

    // Contact-Information will be stored inside users-element, while contatcs only contains their user-ids
    $users        = $contacts;
    $contactIds   = array();
    foreach ($contacts as $contact)
      $contactIds[] = $contact['id'];

    // Also fetch Objects and users attached to news
    $newsRefIds = array();
    foreach ($news['ilias'] as $item) {
      // Make sure all objects attached to news will be fetched
      $newsRefIds[] = $item['ref_id'];

      // Add information for all news-owners
      $userId       = $item['user_id'];
      if ($userId && !$users[$userId])
        $users[$userId] = UserInfo::getUserInfo($userId);
    }

    // Fetch data for refIds
    $objects    = array();
    $refIds     = array_merge($newsRefIds, $cag['group_ids'], $cag['course_ids'], $desktop['ref_ids']);
    $refIds     = array_unique($refIds, SORT_NUMERIC);
    if (count($refIds) > 0) {
      $objects = Objects::getData($accessToken, $refIds);
      $objects = self::fetchDataRecursive($accessToken, $objects);
    }

    // Fetch user-information of all object owners and members
    $users[$user['id']] = $user;
    foreach($objects as $object) {
      // Fetch all owner user-information
      $userId       = $object['owner'];
      if ($userId && !$users[$userId])
        $users[$userId] = UserInfo::getUserInfo($userId);

      // Fetch all member user-information
      if ($object['participants'] && $object['participants']['members'])
        foreach($object['participants']['members'] as $userId)
          if (!$users[$userId])
            $users[$userId] = UserInfo::getUserInfo($userId);

      if ($object['participants'] && $object['participants']['admins'])
        foreach($object['participants']['admins'] as $userId)
          if (!$users[$userId])
            $users[$userId] = UserInfo::getUserInfo($userId);

      if ($object['participants'] && $object['participants']['tutors'])
        foreach($object['participants']['tutors'] as $userId)
          if (!$users[$userId])
            $users[$userId] = UserInfo::getUserInfo($userId);
    }

    // Output result
    return array(
      'calendars'  => $calendars,
      'contacts'   => $contactIds,
      'events'     => $events,
      'users'      => $users,
      'user'       => $user['id'],
      'cag'        => $cag,
      'desktop'    => $desktop,
      'objects'    => $objects,
      'news'       => $news
    );
  }
}
