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
class Calendars {
  /**
   *
   */
  protected static function getCategories($accessToken) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarCategories.php');

    // Fetch user-id from access-token
    $userId = $accessToken->getUserId();

    // Initialize (global!) $ilUser object
    $ilUser = Libs\RESTLib::loadIlUser($userId);
    Libs\RESTLib::initAccessHandling();

    // Fetch calendars (called categories here), initialize from database
    $calendarHandler = \ilCalendarCategories::_getInstance($userId);
    $calendarHandler->initialize(\ilCalendarCategories::MODE_MANAGE);

    // Fetch internal ids for calendars
    return $calendarHandler;
  }


  /**
   *
   */
  protected static function getCalendarInfo($categories, $calendarInfo) {
    // Fetch all sub calendars
    $calendarId     = $calendarInfo['cat_id'];
    $subCategories  = $categories->getSubitemCategories($calendarId);

    // Build calendar-info
    $object         = array(
      'calendar_id'   => intval($calendarId),
      'title'         => $calendarInfo['title']
    );

    // Add children (if available)
    if (count($subCategories) > 1) {
      // Convert all to integer
      $object['children'] = array_map(
        function($value) { return intval($value); },
        $subCategories
      );

      // Remove own calendarId from children
      if(($key = array_search($object['calendar_id'], $object['children'])) !== false)
        unset($object['children'][$key]);
    }

    return $object;
  }


  /**
   *
   */
  public static function getAllCalendars($accessToken) {
    // Fetch info for each calendar (called category here)
    $result     = array();
    $categories = self::getCategories($accessToken);
    foreach($categories->getCategoriesInfo() as $calendarInfo)
      $result[] = self::getCalendarInfo($categories, $calendarInfo);

    // Return appointments
    return $result;
  }


  /**
   *
   */
  public static function getCalendars($accessToken, $calendarIds) {
    // Convert to array
    if (!is_array($calendarIds))
      $calendarIds = array($calendarIds);

    // Fetch each contact from list
    $result         = array();
    $categories     = self::getCategories($accessToken);
    $calendarInfos  = $categories->getCategoriesInfo();
    foreach($calendarIds as $calendarId)
      $result[]       = self::getCalendarInfo($categories, $calendarInfos[$calendarId]);

    // Flatten simple output
    if (count($result) == 1)
      $result = $result[0];

    return $result;
  }


  /**
   *
   */
  public static function getAllEventsOfCalendar($accessToken, $calendarId) {
    // TODO: Check access-rights!

    return Events::getEventsForCalendar($calendarId);
  }


  /**
   *
   */
  public static function getAllEventsOfCalendars($accessToken, $calendarIds) {
    $result = array();
    foreach ($calendarIds as $calendarId)
      $result[] = self::getAllEventsOfCalendar($accessToken, $calendarId);
    return $result;
  }
}
