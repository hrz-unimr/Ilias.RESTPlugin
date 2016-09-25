<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\admin as Admin;
use \RESTController\extensions\users_v1 as Users;
use \RESTController\extensions\courses_v1 as Courses;
use \RESTController\extensions\desktop_v1 as Desktop;
use \RESTController\extensions\groups_v1 as Groups;
use \RESTController\extensions\contacts_v1 as Contacts;
use \RESTController\extensions\calendar_v1 as Calendar;

$app->group('/v1/m', function () use ($app) {

    /**
     * Starting point for mobile apps: loads a user's desktop
     * and the meta-data of its associated items.
     *
     * Loads the following data:
     * - User
     * - System information
     * - Personal courses, which are flagged "online"
     * - Meta-information about the courses
     * - Lists of contents of the personal courses // see of contacts > my courses, my groups
     * - Personal contacts
     * - Personal Calendar (Note: The ILIAS system calendar must be activated) with ICAL url
     *
     *  Version 15.6.15
     */
    $app->get('/desktop', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        $t_start = microtime();
        $result = array();

        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        Libs\RESTilias::initAccessHandling();

        $userModel = new Users\UsersModel();
        $userData = $userModel->getBasicUserData($user_id);
        $result['user'] = $userData;

        $courseModel = new Courses\CoursesModel();
        $my_courses = $courseModel->getCoursesOfUser($user_id);

        $repository_items = array();
        foreach ($my_courses as $course_refid)
        {
            //$my_courses [] = $course_refid;
            $courseContents = $courseModel->getCourseContent($course_refid);
            $children_ref_ids = array();
            foreach ($courseContents as $item) {
                $children_ref_ids[] = $item['ref_id'];
                $repository_items[$item['ref_id']] = $item;
            }
            $course_item = $courseModel->getCourseInfo($course_refid);
            $course_item['children_ref_ids'] = $children_ref_ids;
            $repository_items[$course_refid] = $course_item;
        }
        $result['items'] = $repository_items;

        $desktopModel = new Desktop\DesktopModel();
        $pditems = $desktopModel->getPersonalDesktopItems($user_id);
        $pdrefids = array();
        foreach ($pditems as $pditem) {
            $pdrefids[] = $pditem['ref_id'];
        }
        $result['mypersonaldesktop'] = $pdrefids;
        $result['mycourses'] = $my_courses;

        $grpModel = new Groups\GroupsModel();
        $my_groups = $grpModel->getGroupsOfUser($user_id);
        $result['mygroups'] = $my_groups;

        // Contacts
        $contactModel = new Contacts\ContactsModel();
        $data = $contactModel->getMyContacts($user_id);
        $result['contacts']['my_contacts'] = $data;
        // TODO: CourseContacts, GroupContacts

        // Calendar
        $calModel = new Calendar\CalendarModel();
        $data = $calModel->getIcalAdress($user_id);
        $result['calendar']['ical_url'] = $data;
        $data = $calModel->getCalUpcomingEvents($user_id);
        $result['calendar']['events'] = $data;

        $t_end = microtime();
        $result['meta']['duration'] = abs($t_end-$t_start);
        $result['meta']['tstamp'] = time();
        $resp = array("mdeskinit" => $result);
        $app->success($result);
    });

});
