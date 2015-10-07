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
class Events extends Libs\RESTModel {
  // Allow to re-use status-messages and status-codes
  const MSG_NO_EVENT_ID  = 'Event with eventId %s does not exist.';
  const MSG_ALL_FAILED   = 'All requests failed, see data-entry for more information.';
  const ID_NO_EVENT_ID   = 'RESTController\\extensions\\umr_v1\\Events::ID_NO_EVENT_ID';
  const ID_ALL_FAILED    = 'RESTController\\extensions\\umr_v1\\Events::ID_ALL_FAILED';


  /**
   *
   */
  protected static function getRecurrenceString($appointmentId) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');

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
  protected static function getEventInfo($calendarId, $eventId) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarEntry.php');

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
  public static function getAllEvents($accessToken) {
    // Fetch calendars
    $result     = array();
    $calendars  = Calendars::getAllCalendars($accessToken);
    foreach($calendars as $calendar) {
      // Look up events of calendar and all children
      $items    = ($calendar['children']) ?: array();
      $items[]  = $calendar['calendar_id'];

      // Fetch events
      $result  += self::getEventsForCalendar($calendar['calendar_id'], $items);
    }

    // Return appointments
    return $result;
  }


  /**
   * NOTE: Before calling this method, make sure whoever is requesting this data
   *       is allowed to view and/or publish this information!
   */
  public static function getEventsForCalendar($calendarId, $subItems = null) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');

    // Use calendarId if no subItems are given
    if (!$subItems)
      $subItems = array(intval($calendarId));

    // Fetch events (called appointment here)
    $result         = array();
    $appointmentIds = \ilCalendarCategoryAssignments::_getAssignedAppointments($subItems);
    foreach($appointmentIds as $appointmentId)
      $result[$appointmentId] = self::getEventInfo($calendarId, $appointmentId);

    // Return all collected events
    return $result;
  }


  /**
   *
   */
  public static function getEvents($accessToken, $eventIds) {
    // Convert to array
    if (!is_array($eventIds))
      $eventIds = array($eventIds);

    // Load classes required to appointments
    require_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');

    // Fetch each contact from list
    $result     = array();
    $calendars  = array();
    $success     = 0;
    foreach($eventIds as $eventId) {
      // Fetch calendarId
      $calendarId             = current(\ilCalendarCategoryAssignments::_getAppointmentCalendars(array($eventId)));

      // Does calendar exist
      if ($calendarId) {
        // Fetch event
        $result[$eventId] = self::getEventInfo($calendarId, $eventId);

        // Store events to check calendar access-rights later
        $calendars[$calendarId] = ($calendars[$calendarId]) ? $calendars[$calendarId][] = $eventId : array($eventId);
        ++$success;
      }
      // Calendar does not exist
      else {
        $result[$eventId]             = Libs\RESTLib::responseObject(sprintf(self::MSG_NO_EVENT_ID, $eventId), self::ID_NO_EVENT_ID);
        $result[$eventId]['event_id'] = $eventId;
      }
    }

    // Check access-rights
    foreach($calendars as $calendarId => $eventIds)
      if (!Calendars::hasCalendar($accessToken, $calendarId))
        // Unset all events of this calendar
        foreach($eventIds as $eventId) {
          $result[$eventId] = Libs\RESTLib::responseObject(sprintf(self::MSG_NO_EVENT_ID, $eventId), self::ID_NO_EVENT_ID);
          $result[$eventId]['event_id'] = $eventId;

          --$success;
        }

    // If every request failed, throw instead
    if ($success == 0)
      throw new Exceptions\Events(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }
}
