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
  public static function getEvents($accessToken) {
    // Load classes required to access calendars and their appointments
    require_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
    require_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
    require_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');
    require_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');

    // Fetch user-id from access-token
    $userId = $accessToken->getUserId();

    // Initialize (global!) $ilUser object (will be used by ilCalendarCategories::_getInstance())
    $ilUser = \RESTController\Libs\RESTLib::loadIlUser($userId);
    \RESTController\Libs\RESTLib::initAccessHandling();

    // Fetch calendars (called categories here), initialize from database
    $categories = \ilCalendarCategories::_getInstance();
    $categories->initialize(\ilCalendarCategories::MODE_PERSONAL_DESKTOP_MEMBERSHIP);

    // Fetch internal ids for calendars
    $result       = array();
    $categoryIds  = $categories->getCategories(true);
    foreach($categoryIds as $categoryId) {
      // Fetch evvents (called appointment here)
      $appointmentIds = \ilCalendarCategoryAssignments::_getAssignedAppointments(array($categoryId));
      foreach($appointmentIds as $appointmentId) {
        // Fetch appointment object
        $appointment    = new \ilCalendarEntry($appointmentId);

        // Build recurrence-string (ICS-Formatted)
        $recurrenceStrings  = array();
        $recurrences        = \ilCalendarRecurrences::_getRecurrences($appointmentId);
        foreach($recurrences as $recurrence) {
          foreach(\ilCalendarRecurrenceExclusions::getExclusionDates($appointmentId) as $excl) {
            $recurrenceStrings[] = $excl->toICal();
          }
          $recurrenceStrings[] = $recurrence->toICal($userId);
        }
        $recurrenceString = (sizeof($recurrenceStrings) > 0) ? implode("\\n", $recurrenceStrings) : null;

        // Build result array
        $result[] = array(
          appointment_id  => $appointmentId,
          calendar_id     => $categoryId,
          title           => $appointment->getPresentationTitle(false),
          description     => $appointment->getDescription(),
          location        => $appointment->getLocation(),
          start           => $appointment->getStart()->get(IL_CAL_FKT_DATE, 'Ymd\THis\Z', \ilTimeZone::UTC),
          end             => $appointment->getEnd()->get(  IL_CAL_FKT_DATE, 'Ymd\THis\Z', \ilTimeZone::UTC),
          full_day        => $appointment->isFullday(),
          recurrence      => $recurrenceString
        );
      }
    }

    // Return appointments
    return $result;
  }
}
