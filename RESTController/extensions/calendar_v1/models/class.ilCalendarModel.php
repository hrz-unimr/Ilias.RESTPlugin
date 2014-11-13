<?php
require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";
require_once "./Modules/File/classes/class.ilObjFile.php";
require_once "./Services/User/classes/class.ilObjUser.php";

class ilCalendarModel
{

    /**
     * Retrieves all future appointments for a given user.
     * @param $user_id
     */
    function getCalUpcomingEvents($user_id)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();


        ilRestLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();

        ilRestLib::initGlobal("ilObjDataCache", "ilObjectDataCache",
            "./Services/Object/classes/class.ilObjectDataCache.php");

        // needed in ilObjectDefinition
        require_once "./Services/Xml/classes/class.ilSaxParser.php";

        ilRestLib::initGlobal("objDefinition", "ilObjectDefinition",
            "./Services/Object/classes/class.ilObjectDefinition.php");
        global $ilObjDataCache, $objDefinition;

        //echo "username: ";
        //var_dump($ilUser->getLogin());
        include_once('./Services/Calendar/classes/class.ilCalendarSchedule.php');
        include_once('./Services/Calendar/classes/class.ilDate.php');


        // from class.ilCalendarPresentationGUI.php
        include_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
        $cats = ilCalendarCategories::_getInstance($ilUser->getId());
        //var_dump($cats);


        include_once './Services/Calendar/classes/class.ilCalendarUserSettings.php';
        if(ilCalendarUserSettings::_getInstance()->getCalendarSelectionType() == ilCalendarUserSettings::CAL_SELECTION_MEMBERSHIP)
        {
            //echo "there";
            $cats->initialize(ilCalendarCategories::MODE_PERSONAL_DESKTOP_MEMBERSHIP);
        }
        else
        {
           // echo "here";
            $cats->initialize(ilCalendarCategories::MODE_PERSONAL_DESKTOP_ITEMS);
        }

        $schedule = new ilCalendarSchedule(new ilDate(time(),IL_CAL_UNIX),ilCalendarSchedule::TYPE_INBOX);
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

            $rec = ilCalendarRecurrences::_getFirstRecurrence($entry->getEntryId());

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

            $appointments[] = $tmp_arr;
        }

        return $appointments;
    }

    /**
     * Returns the URL to the ICAL file from the calendar of the personal desktop.
     * See also class.ilCalendarBlockGUI.php -> showCalendarSubscription()
     * @param $user_id
     */
    function getIcalAdress($user_id)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();

        include_once './Services/Http/classes/class.ilHTTPS.php';
        $https = new ilHTTPS();
        if($https->isDetected())
        {
            $protocol = 'https://';
        }
        else
        {
            $protocol = 'http://';
        }
        $host = $_SERVER['HTTP_HOST'];


        include_once './Services/Calendar/classes/class.ilCalendarAuthenticationToken.php';
        //mode : ilCalendarCategories::MODE_PERSONAL_DESKTOP_MEMBERSHIP;
        $selection = ilCalendarAuthenticationToken::SELECTION_PD;
        $calendar = 0;

        if($hash = ilCalendarAuthenticationToken::lookupAuthToken($ilUser->getId(), $selection, $calendar))
        {
        }
        else
        {
            $token = new ilCalendarAuthenticationToken($ilUser->getId());
            $token->setSelectionType($selection);
            $token->setCalendar($calendar);
            $hash = $token->add();
        }
        //$url = ILIAS_HTTP_PATH.'/calendar.php?client_id='.CLIENT_ID.'&token='.$hash;
        $url = $protocol.$host.'/calendar.php?client_id='.CLIENT_ID.'&token='.$hash;

        return $url;

    }

}