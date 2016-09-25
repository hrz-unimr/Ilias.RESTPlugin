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
class Calendars extends Libs\RESTModel {
  // Allow to re-use status-messages and status-codes
  const MSG_NO_CALENDAR_ID  = 'Calendar with calendarId %s does not exist.';
  const MSG_ALL_FAILED      = 'All requests failed, see data-entry for more information.';
  const ID_NO_CALENDAR_ID   = 'RESTController\\extensions\\umr_v1\\Calendars::ID_NO_CALENDAR_ID';
  const ID_ALL_FAILED       = 'RESTController\\extensions\\umr_v1\\Calendars::ID_ALL_FAILED';


  // Buffer categories (once for each userId)
  protected static $calendars    = array();


  /**
   *
   */
  protected static function loadCalendar($accessToken) {
    $userId = $accessToken->getUserId();

    // Query information only ONCE
    if (!self::$calendars[$userId]) {
      // Load classes required to access calendars and their appointments
      require_once('./Services/Calendar/classes/class.ilCalendarCategories.php');

      // Fetch user-id from access-token
      $userId = $accessToken->getUserId();

      // Initialize (global!) $ilUser object
      $ilUser = Libs\RESTilias::loadIlUser($userId);
      Libs\RESTilias::initAccessHandling();

      // Fetch calendars (called categories here), initialize from database
      self::$calendars[$userId] = \ilCalendarCategories::_getInstance($userId);
      self::$calendars[$userId]->initialize(\ilCalendarCategories::MODE_MANAGE);
    }

    // Fetch internal ids for calendars
    return self::$calendars[$userId];
  }


  /**
   *
   */
  protected static function getCalendarInfo($calendars, $calendarInfo) {
    // Fetch all sub calendars
    $calendarId     = $calendarInfo['cat_id'];
    $subItems  = $calendars->getSubitemCategories($calendarId);

    // Build calendar-info
    $object         = array(
      'calendar_id'   => intval($calendarId),
      'title'         => $calendarInfo['title']
    );

    // Add children (if available)
    if (count($subItems) > 1) {
      // Convert all to integer
      $object['children'] = array_map(
        function($value) { return intval($value); },
        $subItems
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
  public static function hasCalendar($accessToken, $calendarId) {
    $calendars     = self::loadCalendar($accessToken);
    $calendarInfos  = $calendars->getCategoriesInfo();

    return ($calendarInfos[$calendarId] != null);
  }


  /**
   *
   */
  public static function getAllCalendars($accessToken) {
    // Fetch info for each calendar (called category here)
    $result     = array();
    $calendars = self::loadCalendar($accessToken);
    foreach($calendars->getCategoriesInfo() as $calendarInfo) {
      $info                 = self::getCalendarInfo($calendars, $calendarInfo);
      $calendarId           = $info['calendar_id'];
      $result[$calendarId]  = $info;
    }
    // Return appointments
    return $result;
  }

  public static function isEditable($accessToken, $calendarId) {
    //$calendars     = self::loadCalendar($accessToken);
    $calendars = self::loadCalendar($accessToken);
    $calendarInfos  = $calendars->getCategoriesInfo();
    //return ($calendarInfos[$calendarId] != null);

    require_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
    $categories = \ilCalendarCategories::_getInstance();
    $cat_id = $calendarId;//$a_category_id;
    return $categories->isEditable($cat_id);
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
    $calendars     = self::loadCalendar($accessToken);
    $calendarInfos  = $calendars->getCategoriesInfo();
    $noSuccess      = true;
    foreach($calendarIds as $calendarId)
      // Does calendar with this id exist?
      if ($calendarInfos[$calendarId]) {
        $result[$calendarId]  = self::getCalendarInfo($calendars, $calendarInfos[$calendarId]);
        $noSuccess            = false;
      }
      // Add missing calendarId information to response
      else {
        $result[$calendarId]                = Libs\RESTResponse::responseObject(sprintf(self::MSG_NO_CALENDAR_ID, $calendarId), self::ID_NO_CALENDAR_ID);
        $result[$calendarId]['calendar_id'] = $calendarId;
      }

    // If every request failed, throw instead
    if ($noSuccess && count($calendarIds) > 0)
      throw new Exceptions\Calendars(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }


  /**
   *
   */
  public static function getAllEventsOfCalendar($accessToken, $calendarId) {
    // Check if calendar exists
    if (self::hasCalendar($accessToken, $calendarId))
      // Fetch events of calendar
      return Events::getEventsForCalendar($calendarId);
    else
      throw new Exceptions\Calendars(sprintf(self::MSG_NO_CALENDAR_ID, $calendarId), self::ID_NO_CALENDAR_ID, intval($calendarId));
  }


  /**
   *
   */
  public static function getAllEventsOfCalendars($accessToken, $calendarIds) {
    // Convert input to array
    if (!is_array($calendarIds))
      $calendarIds = array($calendarIds);

    // Fetch all calendars
    $result     = array();
    $noSuccess  = true;
    foreach ($calendarIds as $calendarId)
      // Try to fetch information
      try {
        $result[$calendarId]  = self::getAllEventsOfCalendar($accessToken, $calendarId);
        $noSuccess            = false;
      }
      // Add exception information if failure
      catch (Exceptions\Calendars $e) {
        $result[$calendarId]                = Libs\RESTResponse::responseObject($e->getRESTMessage(), $e->getRESTCode());
        $result[$calendarId]['calendar_id'] = $calendarId;
      }

    // If every request failed, throw instead
    if ($noSuccess && count($calendarIds) > 0)
      throw new Exceptions\Calendars(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }
}
