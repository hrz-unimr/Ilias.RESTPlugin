<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\calendar_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once("./Services/Database/classes/class.ilAuthContainerMDB2.php");
require_once("./Modules/File/classes/class.ilObjFile.php");
require_once("./Services/User/classes/class.ilObjUser.php");

class CalendarModel extends Libs\RESTModel
{

    /**
     * Retrieves all future appointments for a given user.
     * @param $user_id
     * @return array list of events
     */
    function getCalUpcomingEvents($user_id)
    {
        self::getApp()->log->debug('in getCalUpcomingEvents ...');
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        
        Libs\RESTilias::initGlobal("ilObjDataCache", "ilObjectDataCache",
            "./Services/Object/classes/class.ilObjectDataCache.php");

        // needed in ilObjectDefinition
        require_once("./Services/Xml/classes/class.ilSaxParser.php");

        Libs\RESTilias::initGlobal("objDefinition", "ilObjectDefinition",
            "./Services/Object/classes/class.ilObjectDefinition.php");
        global $ilObjDataCache, $objDefinition;

        include_once('./Services/Calendar/classes/class.ilCalendarSchedule.php');
        include_once('./Services/Calendar/classes/class.ilDate.php');


        // from class.ilCalendarPresentationGUI.php
        include_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
        $cats = \ilCalendarCategories::_getInstance($ilUser->getId());

        include_once('./Services/Calendar/classes/class.ilCalendarUserSettings.php');
        if(\ilCalendarUserSettings::_getInstance()->getCalendarSelectionType() == \ilCalendarUserSettings::CAL_SELECTION_MEMBERSHIP)
        {
            $cats->initialize(\ilCalendarCategories::MODE_PERSONAL_DESKTOP_MEMBERSHIP);
        }
        else
        {
            $cats->initialize(\ilCalendarCategories::MODE_PERSONAL_DESKTOP_ITEMS);
        }

        $schedule = new \ilCalendarSchedule(new \ilDate(time(),IL_CAL_UNIX), \ilCalendarSchedule::TYPE_INBOX);
        $schedule->setEventsLimit(100);
        $schedule->addSubitemCalendars(true);
        $schedule->calculate();
        // type inbox will show upcoming events (today or later)
        $events = $schedule->getScheduledEvents();
        include_once('./Services/Calendar/classes/class.ilCalendarEntry.php');
        include_once('./Services/Calendar/classes/class.ilCalendarRecurrences.php');

        foreach($events as $event)
        {
            $entry = $event['event'];
            //self::getApp()->log->debug('processing event : '.print_r($entry,true));
            $rec = \ilCalendarRecurrences::_getFirstRecurrence($entry->getEntryId());

            $tmp_arr['id'] = $entry->getEntryId();
            $tmp_arr['milestone'] = $entry->isMilestone();
            $tmp_arr['title'] = $entry->getPresentationTitle();
            $tmp_arr['description'] = $entry->getDescription();
            $tmp_arr['fullday'] = $entry->isFullday();
            //$tmp_arr['begin'] = $entry->getStart()->get(IL_CAL_UNIX);
            //$tmp_arr['end'] = $entry->getEnd()->get(IL_CAL_UNIX);

            $tmp_arr['begin'] = $event['dstart'];
            $tmp_arr['end'] = $event['dend'];

            $tmp_arr['duration'] =  $event['dend'] - $event['dstart'];
            if($tmp_arr['fullday'])
            {
                $tmp_arr['duration'] += (60 * 60 * 24);
            }
            if(!$tmp_arr['fullday'] and $tmp_arr['end'] == $tmp_arr['begin'])
            {
                $tmp_arr['duration'] = '';
            }

            $tmp_arr['last_update'] = $entry->getLastUpdate()->get(IL_CAL_UNIX);
            $tmp_arr['frequence'] = $rec->getFrequenceType();

            // see permalink code at ilCalendearAppointmentGUI l.804 (showInfoScreen())
            include_once('./Services/Calendar/classes/class.ilCalendarCategoryAssignments.php');
            $cat_id = \ilCalendarCategoryAssignments::_lookupCategory($entry->getEntryId());
            $cat_info = \ilCalendarCategories::_getInstance()->getCategoryInfo($cat_id);
            $refs = \ilObject::_getAllReferences($cat_info['obj_id']);
            $tmp_arr['reference'] = current($refs); // reference id
            $appointments[] = $tmp_arr;
        }
        return $appointments;
    }

    /**
     * Returns the URL to the ICAL file from the calendar of the personal desktop.
     * See also class.ilCalendarBlockGUI.php -> showCalendarSubscription()
     * @param $user_id
     * @return string a URL
     */
    function getIcalAdress($user_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        include_once('./Services/Http/classes/class.ilHTTPS.php');
        $https = new \ilHTTPS();
        if($https->isDetected())
        {
            $protocol = 'https://';
        }
        else
        {
            $protocol = 'http://';
        }
        $host = $_SERVER['HTTP_HOST'];


        include_once('./Services/Calendar/classes/class.ilCalendarAuthenticationToken.php');
        //mode : ilCalendarCategories::MODE_PERSONAL_DESKTOP_MEMBERSHIP;
        $selection = \ilCalendarAuthenticationToken::SELECTION_PD;
        $calendar = 0;

        if($hash = \ilCalendarAuthenticationToken::lookupAuthToken($ilUser->getId(), $selection, $calendar))
        {
        }
        else
        {
            $token = new \ilCalendarAuthenticationToken($ilUser->getId());
            $token->setSelectionType($selection);
            $token->setCalendar($calendar);
            $hash = $token->add();
        }
        $url = $protocol.$host.'/calendar.php?client_id='.CLIENT_ID.'&token='.$hash;

        return $url;

    }

}
