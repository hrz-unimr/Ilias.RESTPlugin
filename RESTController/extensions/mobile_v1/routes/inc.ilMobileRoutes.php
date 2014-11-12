<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/m', function () use ($app) {

    $app->get('/courses/:id', function ($id) use ($app) {


        $id_type = $app->request()->params("id_type");
        $id_type = $id_type = null ? 'ref_id' : $id_type;
        $obj_id = -1;
        if ($id_type == 'obj_id')
        {
            $obj_id = $id;
        } else
        {
            $obj_id = ilRestLib::refid_to_objid($id);
        }

        $result = array();
        $model = new ilDescribrModel();
        $a_descr = $model->describeIliasObject($obj_id);
        $result['object_description'] = $a_descr;

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);

    });


    /**
     *  Loads the following data:
     * - User / system information
     * - Personal courses, which are flagged "online"
     * - Meta-information about the courses
     * - Lists of contents of the personal courses // see of contacts > my courses, my groups
     * - People participating the courses
     * - Dates (Note: System Caldendar must be activated)
     *  - List
     *  - ICAL (ics) Feed Url
     */
    $app->get('/desk', function () use ($app) {
        $result = array();

        // TODO: extract user_id from valid token
        $user_id = 6;//225;//6;//361; // testuser
        //$user = ilRestLib::userIdtoLogin($user_id);

        // use case: load all available courses
        // later: split according to current semesters and
        // information on courses for older semesters are provided on demand (this should be the default case)

        // the mobile desktop is made up of
        // courses the user is participating
        // in addition to the course names also the
        // list of items for each course is sent, to enable better
        // UI interactions
        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initAccessHandling();

        $userModel = new ilUsersModel();
        $userData = $userModel->getBasicUserData($user_id);
        $result['user'] = $userData;

        $courseModel = new ilCoursesModel();
        $course_list = $courseModel->getCoursesOfUser($user_id);

        $course_contents = array();
        $course_info = array();
        foreach ($course_list as $course_refid)
        {
            $course_contents[$course_refid] = $courseModel->getCourseContent($course_refid);
            $children_ref_ids = array();
            foreach ($course_contents[$course_refid] as $item) {
                $children_ref_ids[] = $item['ref_id'];
            }
            //var_dump($children_ref_ids);
            $course_item = $courseModel->getCourseInfo($course_refid);
            $course_item['children_ref_ids'] = $children_ref_ids;
            $course_info[$course_refid] = $course_item;

            //var_dump($course_info[$course_refid]);
            $course_info[$course_refid]['content_length']= count($course_contents[$course_refid]);
        }
        $result['courses'] = $course_info;
        $result['contents'] = $course_contents;

        // Calendar
        $calModel = new ilCalendarModel();
        $data = $calModel->getIcalAdress($user_id);
        $result['ical_url'] = $data;
        $data = $calModel->getCalUpcomingEvents($user_id);
        $result['events'] = $data;

        // Contacts
        $contactModel = new ilContactsModel();
        $data = $contactModel->getMyContacts($user_id);
        $result['contacts']['mycontacts'] = $data;

        $result['status'] = "ok";
        echo json_encode($result);
    });

    /**
     *  Loads the following data:
     * - User / system information
     * - Personal courses, which are flagged "online"
     * - Meta-information about the courses
     * - Lists of contents of the personal courses // see of contacts > my courses, my groups
     * - People participating the courses
     * - Dates (Note: System Caldendar must be activated)
     *  - List
     *  - ICAL (ics) Feed Url
     *  In contrast to version 1, the json is structured in a better and more concise way.
     */
    $app->get('/desk2', function () use ($app) {
        $t_start = microtime();
        $result = array();

        // TODO: extract user_id from valid token
        $user_id = 6;//225;//6;//361; // testuser
        //$user = ilRestLib::userIdtoLogin($user_id);

        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initAccessHandling();

        $userModel = new ilUsersModel();
        $userData = $userModel->getBasicUserData($user_id);
        $result['user'] = $userData;

        $courseModel = new ilCoursesModel();
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
        $result['ritems'] = $repository_items;

        $desktopModel = new ilDesktopModel();
        $pditems = $desktopModel -> getPersonalDesktopItems($user_id);
        $pdrefids = array();
        foreach ($pditems as $pditem) {
            $pdrefids[] = $pditem['ref_id'];
        }
        $result['mypersonaldesktop'] = $pdrefids;
        $result['mycourses'] = $my_courses;

        $grpModel = new ilGroupsModel();
        $my_groups = $grpModel->getGroupsOfUser($user_id);
        $result['mygroups'] = $my_groups;

        // Contacts
        $contactModel = new ilContactsModel();
        $data = $contactModel->getMyContacts($user_id);
        $result['contacts']['my_contacts'] = $data;
        // -> todo: CourseContacts, GroupContacts

        // Calendar
        $calModel = new ilCalendarModel();
        $data = $calModel->getIcalAdress($user_id);
        $result['calendar']['ical_url'] = $data;
        $data = $calModel->getCalUpcomingEvents($user_id);
        $result['calendar']['events'] = $data;

        $t_end = microtime();
        $result['status']['duration'] = abs($t_end-$t_start);
        $result['status']['tstamp'] = time();
        echo json_encode($result);
    });
});
