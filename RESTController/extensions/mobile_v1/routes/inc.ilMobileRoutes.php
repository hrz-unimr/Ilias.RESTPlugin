<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/m', function () use ($app) {

    $app->get('/courses/:id', function ($id) use ($app) {

        $app = \Slim\Slim::getInstance();
        $env = $app->environment();

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

    $app->get('/hello', function () use ($app) {
        $result = array();
        $result['msg'] = "hello";
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
        $user_id = 225;//6;//361; // testuser
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
            $course_info[$course_refid] = $courseModel->getCourseInfo($course_refid);
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

        $result['status'] = "ok";
        echo json_encode($result);
    });


    /**
     *  Retrieves the calendar from user
     */
    $app->get('/cal/:id', function ($id) use ($app) {
        $result = array();

       /* include_once('./Services/Calendar/classes/class.ilCalendarInboxSharedTableGUI.php');
        include_once('./Services/Calendar/classes/class.ilCalendarShared.php');

        $table = new ilCalendarInboxSharedTableGUI($this,'inbox');
        $table->setCalendars(ilCalendarShared::getSharedCalendarsForUser());
*/

        $result['msg'] = "calendar for user ".$id;
        echo json_encode($result);
    });

});
