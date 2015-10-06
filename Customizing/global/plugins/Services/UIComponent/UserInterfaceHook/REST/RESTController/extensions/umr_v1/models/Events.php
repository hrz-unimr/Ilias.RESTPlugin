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
class Events {
  /**
   *
   */
  protected static function getRecurrenceString($appointmentId) {
    // Will temporary store string elemts for imploding
    $recurrenceStrings  = array();

    // Fetch recurrence
    $recurrences        = \ilCalendarRecurrences::_getRecurrences($appointmentId);
    foreach($recurrences as $recurrence) {
      // Fetch exluded recurrence
      $excludes = \ilCalendarRecurrenceExclusions::getExclusionDates($appointmentId);
      foreach($excludes as $excluded)
        $recurrenceStrings[] = $excluded->toICal();

      // Fetch (included) recurrence
      $recurrenceStrings[] = $recurrence->toICal($userId);
    }

    // Each recurrence on a new line (mimicking ICS-formated)
    $recurrenceString = (sizeof($recurrenceStrings) > 0) ? implode("\\n", $recurrenceStrings) : null;

    // Return final result as string
    return $recurrenceString;
  }


  /**
   *
   */
  public static function getAllEvents($accessToken) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
    require_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
    require_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');
    require_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');

    // Fetch user-id from access-token
    $userId = $accessToken->getUserId();

    // Initialize (global!) $ilUser object
    $ilUser = Libs\RESTLib::loadIlUser($userId);
    Libs\RESTLib::initAccessHandling();

    // Fetch calendars (called categories here), initialize from database
    $categoryHandler = \ilCalendarCategories::_getInstance($userId);
    $categoryHandler->initialize(\ilCalendarCategories::MODE_MANAGE);

    // Fetch internal ids for calendars
    $result       = array();
    $categories   = $categoryHandler->getCategoriesInfo();
    foreach($categories as $category) {
      // Fetch all sub calendars
      $categoryId     = $category['cat_id'];
      $subCategories  = $categoryHandler->getSubitemCategories($categoryId);

      // Fetch events (called appointment here)
      $appointmentIds = \ilCalendarCategoryAssignments::_getAssignedAppointments($subCategories);
      foreach($appointmentIds as $appointmentId)
        $result[] = self::getEventInfo($categoryId, $appointmentId);
    }

    // Return appointments
    return $result;
  }


  /**
   *
   */
  protected static function getEventInfo($calendarId, $eventId) {
    // Fetch appointment object
    $appointment    = new \ilCalendarEntry($eventId);

    // Build recurrence-string (ICS-Formatted)
    $recurrenceString = self::getRecurrenceString($eventId);

    // Build result array
    return array_filter(
      array(
        event_id        => intval($eventId),
        calendar_id     => intval($calendarId),
        title           => $appointment->getPresentationTitle(false),
        description     => $appointment->getDescription(),
        location        => $appointment->getLocation(),
        start           => $appointment->getStart()->get(IL_CAL_FKT_DATE, 'Ymd\THis\Z', \ilTimeZone::UTC),
        end             => $appointment->getEnd()->get(  IL_CAL_FKT_DATE, 'Ymd\THis\Z', \ilTimeZone::UTC),
        full_day        => $appointment->isFullday(),
        recurrence      => $recurrenceString
      ),
      function($value) { return !is_null($value); }
    );
  }


  /**
   *
   */
  public static function getEvents($accessToken, $eventIds) {
    // Convert to array
    if (!is_array($eventIds))
      $eventIds = array($eventIds);

    // Extract user name
    $userId       = $accessToken->getUserId();

    // Load classes required to appointments
    require_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
    require_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');
    require_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');

    // Fetch each contact from list
    $result = array();
    foreach($eventIds as $eventId) {
      $calendarId = current(\ilCalendarCategoryAssignments::_getAppointmentCalendars(array($eventId)));
      $result[]   = self::getEventInfo($calendarId, $eventId);
    }

    // Flatten simple output
    if (count($result) == 1)
      $result = $result[0];

    return $result;
  }
}
