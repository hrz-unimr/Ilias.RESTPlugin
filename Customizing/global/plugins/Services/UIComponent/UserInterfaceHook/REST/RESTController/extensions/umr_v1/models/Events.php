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
    require_once('./Services/Link/classes/class.ilLink.php');
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

        // Get referencing objects (should be groups/courses)
        $category = \ilCalendarCategory::getInstanceByCategoryId($categoryId);
        $refIds   = \ilObject::_getAllReferences($category->getObjId());

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
          recurrence      => $recurrenceString,
          ref_ids         => $refIds
        );
      }
    }

    // Return appointments
    return $result;
  }
}


/*
protected function createRecurrences($app)
{
  global $ilUser;

  include_once './Services/Calendar/classes/class.ilCalendarRecurrences.php';
  foreach(ilCalendarRecurrences::_getRecurrences($app->getEntryId()) as $rec) {
    foreach(ilCalendarRecurrenceExclusions::getExclusionDates($app->getEntryId()) as $excl) {
      $excl->toICal();
    }
    $rec->toICal($ilUser->getId());
  }
}
*/


/*
protected function buildAppointmentUrl(ilCalendarEntry $entry)
{
  $cat = ilCalendarCategory::getInstanceByCategoryId(
    current((array) ilCalendarCategoryAssignments::_lookupCategories($entry->getEntryId()))
  );

  if($cat->getType() != ilCalendarCategory::TYPE_OBJ)
  {
    $this->writer->addLine('URL;VALUE=URI:'.ILIAS_HTTP_PATH);
  }
  else
  {
    $refs = ilObject::_getAllReferences($cat->getObjId());

    include_once './Services/Link/classes/class.ilLink.php';
    $this->writer->addLine(
      'URL;VALUE=URI:'.ilLink::_getLink(current((array) $refs))
    );
  }
}
*/


/*
protected function createVEVENT($app)
{
  global $ilUser;

  $this->writer->addLine('BEGIN:VEVENT');
  // TODO only domain
  $this->writer->addLine('UID:'.ilICalWriter::escapeText(
    $app->getEntryId().'_'.CLIENT_ID.'@'.ILIAS_HTTP_PATH));

  $last_mod = $app->getLastUpdate()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',ilTimeZone::UTC);
  #$last_mod = $app->getLastUpdate()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',$ilUser->getTimeZone());
  $this->writer->addLine('LAST-MODIFIED:'.$last_mod);

  // begin-patch aptar
  include_once './Services/Calendar/classes/class.ilCalendarRecurrences.php';
  if($rec = ilCalendarRecurrences::_getFirstRecurrence($app->getEntryId()))
  {
    // Set starting time to first appointment that matches the recurrence rule
    include_once './Services/Calendar/classes/class.ilCalendarRecurrenceCalculator.php';
    $calc = new ilCalendarRecurrenceCalculator($app,$rec);

    $pStart = $app->getStart();
    $pEnd = clone $app->getStart();
    $pEnd->increment(IL_CAL_YEAR,5);
    $appDiff = $app->getEnd()->get(IL_CAL_UNIX) - $app->getStart()->get(IL_CAL_UNIX);
    $recs = $calc->calculateDateList($pStart, $pEnd);

    // defaults
    $startInit = $app->getStart();
    $endInit = $app->getEnd();
    foreach($recs as $dt)
    {
      $startInit = $dt;
      $endInit = clone($dt);
      $endInit->setDate($startInit->get(IL_CAL_UNIX) + $appDiff,IL_CAL_UNIX);
      break;
    }

  }
  else
  {
    $startInit = $app->getStart();
    $endInit = $app->getEnd();
  }


  if($app->isFullday())
  {
    // According to RFC 5545 3.6.1 DTEND is not inklusive.
    // But ILIAS stores inklusive dates in the database.
    #$app->getEnd()->increment(IL_CAL_DAY,1);
    $endInit->increment(IL_CAL_DATE,1);

    #$start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd\Z',ilTimeZone::UTC);
    #$start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd',$ilUser->getTimeZone());
    $start = $startInit->get(IL_CAL_FKT_DATE,'Ymd',$ilUser->getTimeZone());
    #$end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd\Z',ilTimeZone::UTC);
    #$end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd',$ilUser->getTimeZone());
    $endInit->increment(IL_CAL_DAY,1);
    $end = $endInit->get(IL_CAL_FKT_DATE,'Ymd',$ilUser->getTimeZone());

    $this->writer->addLine('DTSTART;VALUE=DATE:' . $start);
    $this->writer->addLine('DTEND;VALUE=DATE:'.$end);
  }
  else
  {
    if($this->getUserSettings()->getExportTimeZoneType() == ilCalendarUserSettings::CAL_EXPORT_TZ_UTC)
    {
      $start = $app->getStart()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',ilTimeZone::UTC);
      $end = $app->getEnd()->get(IL_CAL_FKT_DATE,'Ymd\THis\Z',ilTimeZone::UTC);
      $this->writer->addLine('DTSTART:'. $start);
      $this->writer->addLine('DTEND:'.$end);

    }
    else
    {
      $start = $startInit->get(IL_CAL_FKT_DATE,'Ymd\THis',$ilUser->getTimeZone());
      $end = $endInit->get(IL_CAL_FKT_DATE,'Ymd\THis',$ilUser->getTimeZone());
      $this->writer->addLine('DTSTART;TZID='.$ilUser->getTimezone().':'. $start);
      $this->writer->addLine('DTEND;TZID='.$ilUser->getTimezone().':'.$end);
    }
  }
  // end-patch aptar

  $this->createRecurrences($app);

  $this->writer->addLine('SUMMARY:'.ilICalWriter::escapeText($app->getPresentationTitle(false)));
  if(strlen($app->getDescription()))
    $this->writer->addLine('DESCRIPTION:'.ilICalWriter::escapeText($app->getDescription()));
  if(strlen($app->getLocation()))
    $this->writer->addLine('LOCATION:'.ilICalWriter::escapeText($app->getLocation()));

  // TODO: URL
  $this->buildAppointmentUrl($app);

  $this->writer->addLine('END:VEVENT');

}
*/
