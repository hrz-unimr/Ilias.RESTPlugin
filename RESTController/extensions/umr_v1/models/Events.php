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
  protected static function getRecurrenceString($eventId) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');

    // Will temporary store string elemts for imploding
    $recurrenceStrings  = array();

    // Fetch recurrence
    $recurrences        = \ilCalendarRecurrences::_getRecurrences($eventId);
    foreach($recurrences as $recurrence) {
      // Fetch exluded recurrence
      $excludes = \ilCalendarRecurrenceExclusions::getExclusionDates($eventId);
      foreach($excludes as $excluded)
        $recurrenceStrings[] = $excluded->toICal();

      // Fetch (included) recurrence
      $recurrenceStrings[] = $recurrence->toICal($userId);
    }

    // Each recurrence on a new line (mimicking ICS-formated)
    $recurrenceString = (sizeof($recurrenceStrings) > 0) ? implode('\\n', $recurrenceStrings) : null;

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
    $event    = new \ilCalendarEntry($eventId);

    // Build recurrence-string (ICS-Formatted)
    $recurrenceString = self::getRecurrenceString($eventId);

    // Build result array
    return array_filter(
      array(
        event_id        => intval($eventId),
        calendar_id     => intval($calendarId),
        title           => $event->getPresentationTitle(false),
        description     => $event->getDescription(),
        location        => $event->getLocation(),
        start           => $event->getStart()->get(IL_CAL_FKT_DATE, 'Ymd\THis\Z', \ilTimeZone::UTC),
        end             => $event->getEnd()->get(  IL_CAL_FKT_DATE, 'Ymd\THis\Z', \ilTimeZone::UTC),
        full_day        => $event->isFullday(),
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
    $eventIds = \ilCalendarCategoryAssignments::_getAssignedAppointments($subItems);
    foreach($eventIds as $eventId)
      $result[$eventId] = self::getEventInfo($calendarId, $eventId);

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
        $result[$eventId]             = Libs\RESTResponse::responseObject(sprintf(self::MSG_NO_EVENT_ID, $eventId), self::ID_NO_EVENT_ID);
        $result[$eventId]['event_id'] = $eventId;
      }
    }

    // Check access-rights
    foreach($calendars as $calendarId => $eventIds)
      if (!Calendars::hasCalendar($accessToken, $calendarId))
        // Unset all events of this calendar
        foreach($eventIds as $eventId) {
          $result[$eventId] = Libs\RESTResponse::responseObject(sprintf(self::MSG_NO_EVENT_ID, $eventId), self::ID_NO_EVENT_ID);
          $result[$eventId]['event_id'] = $eventId;

          --$success;
        }

    // If every request failed, throw instead
    if ($success == 0 && count($eventIds) > 0)
      throw new Exceptions\Events(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }

  /**
   * Deletes an event (ILIAS appointment)
   *
   * @param $accessToken
   * @param $eventId
   * @return bool
   */
  public static function deleteEvent($accessToken, $eventId) {
    global $ilLog;

    include_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');
    include_once('./Services/Calendar/classes/class.ilCalendarEntry.php');

    $calendarId             = current(\ilCalendarCategoryAssignments::_getAppointmentCalendars(array($eventId)));

    if (Calendars::isEditable($accessToken, $calendarId)==true) {
      $ilLog->write('Calendar '.$calendarId.' seems to be editable. Deleting event '.$eventId." ...");
      \ilCalendarCategoryAssignments::_deleteByAppointmentId($eventId);
      \ilCalendarEntry::_delete($eventId);
      return true;
    } else {
      $ilLog->write('Calendar '.$calendarId.' ist NOT editable for the current user.');
      return false;
    }
  }

  /**
   * Create a new event (ILIAS appointment) for the specified calendar.
   *
   * Note:
   *  Calendar notifications not supported yet!
   *  Calendar recurrences not supported yet!
   *
   * @param $accessToken
   * @param $cal_id
   * @param $title
   * @param $description
   * @return int
   */
  public static function addEvent($accessToken, $cal_id, $title, $description, $fullDayFlag, $startTime, $endTime) {

    if (Calendars::isEditable($accessToken, $cal_id)==true) {
      include_once('./Services/Calendar/classes/class.ilDate.php');
      include_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
      include_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');

      $a_app_id = 0;
      $app = new \ilCalendarEntry($a_app_id);

      if (!$a_app_id) {
        $tStart = mktime($startTime['hour'], $startTime['minute'], 0, $startTime['month'], $startTime['day'], $startTime['year']);
        $start = new \ilDate($tStart, IL_CAL_UNIX);
        $app->setStart($start);
        $tEnd = mktime($endTime['hour'], $endTime['minute'], 0, $endTime['month'], $endTime['day'], $endTime['year']);
        $seed_end = new \ilDate($tEnd, IL_CAL_UNIX);
        $app->setEnd($seed_end);

        if ($fullDayFlag == true) {
          $app->setFullday(1);
        } else {
          $app->setFullday(0);
        }

        $app->setTitle($title);
        $app->setDescription($description);
        $app->save();

        include_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');
        $ass = new \ilCalendarCategoryAssignments($app->getEntryId());
        $ass->addAssignment($cal_id);

        return $app->getEntryId();
      }
    }
    return -1;
  }

  /**
   * Updates title and description of an existing event (appointment).
   *
   * @param $accessToken
   * @param $event_id
   * @param $newTitle
   * @param $newDescription
   * @param $fullDayFlag
   * @param $startTime
   * @param $endTime
   * @return bool
   */
  public static function updateEvent($accessToken, $event_id, $newTitle, $newDescription, $fullDayFlag, $startTime, $endTime) {

    include_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');
    include_once('./Services/Calendar/classes/class.ilCalendarEntry.php');

    $calendarId             = current(\ilCalendarCategoryAssignments::_getAppointmentCalendars(array($event_id)));

    if (Calendars::isEditable($accessToken, $calendarId)==true) {
      $app = new \ilCalendarEntry($event_id);
      $app->setTitle($newTitle);
      $app->setDescription($newDescription);
      if ($startTime['hour'] != null) {
        $tStart = mktime($startTime['hour'], $startTime['minute'], 0, $startTime['month'], $startTime['day'], $startTime['year']);
        $start = new \ilDate($tStart, IL_CAL_UNIX);
        $app->setStart($start);
      }

      if ($endTime['hour'] != null) {
        $tEnd = mktime($endTime['hour'], $endTime['minute'], 0, $endTime['month'], $endTime['day'], $endTime['year']);
        $seed_end = new \ilDate($tEnd, IL_CAL_UNIX);
        $app->setEnd($seed_end);
      }

      if ($fullDayFlag != null) {
        if ($fullDayFlag == true) {
          $app->setFullday(1);
        } else {
          $app->setFullday(0);
        }
      }

      $app->update();
      return true;
    }
    return false;
  }
}
