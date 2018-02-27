<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\users_v1 as Users;
use \RESTController\extensions\courses_v1 as Courses;

$app->group('/v1', function () use ($app) {

    /**
     * Retrieves a list of all courses of the authenticated user and meta-information about them (no content).
     */
   $app->get('/courses', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();
        try {
        $crs_model = new CoursesModel();
        $data1 =  $crs_model->getAllCourses($user_id);

        $result = array(
            'courses' => $data1
        );
        $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

    /**
     * Retrieves the content and a description of a course specified by ref_id.
     */
    $app->get('/courses/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $app->log->debug('in course get ref_id= '.$ref_id);
        try {
            $crs_model = new CoursesModel();
            $data1 = $crs_model->getCourseContent($ref_id);
            $data2 = $crs_model->getCourseInfo($ref_id);
            $include_tutors_and_admints = true;
            $data3 = $crs_model->getCourseMembers($ref_id, $include_tutors_and_admints);

            $result = array(
                'contents' => $data1, // course contents
                'info' => $data2,     // course info
                'members' => $data3   // course members
            );
            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

    /**
     * Creates a new course. Please provide the ref_id of the parent repository object, title and description. Note that the new course will be offline initially.
     *
     */
    $app->post('/courses', RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        try {
            $request = $app->request();
            $ref_id = $request->getParameter('parent_ref_id', null, true);
            $title = $request->getParameter('title', null, true);
            $description = $request->getParameter('description', '');

            Libs\RESTilias::loadIlUser();
            Libs\RESTilias::initAccessHandling();
            if(!$GLOBALS['ilAccess']->checkAccess("create_crs", "", $ref_id))
                $app->halt(401, "Insufficient access rights");

            $crs_model = new CoursesModel();
            $new_ref_id =  $crs_model->createNewCourse($ref_id, $title, $description);

            $result = array('refId' => $new_ref_id);
            $app->success($result);
        }
        catch (Libs\Exceptions\Parameter $e) {
            $e->send(400);
        }
    });

    /**
     * Deletes a course specified by its ref_id.
     */
    $app->delete('/courses/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();
        global $ilUser;
        Libs\RESTilias::loadIlUser();
        $ilUser->setId((int)$user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        global $rbacsystem;

        if ($rbacsystem->checkAccess('delete',$ref_id)) {
            $result = array();
            $crs_model = new CoursesModel();
            $soap_result = $crs_model->deleteCourse($ref_id);
            $resp = array("course_deleted" => $soap_result);
            $app->success($resp);
        } else {
            $app->success(array("msg"=>"No Permission."));
        }

    });


    /**
     * Enroll a user to a course.
     * Expects a "mode" parameter ("by_login"/"by_id") that determines the
     * lookup method for the user.
     * If "mode" is "by_login", the "login" parameter is used for the lookup.
     * If no user is found, a new LDAP user is created with attributes from
     * the "data" array.
     * If "mode" is "by_id", the parameter "usr_id" is used for the lookup.
     * The user is then enrolled in the course with "crs_ref_id".
     */
    $app->post('/courses/enroll', RESTAuth::checkAccess(RESTAuth::ADMIN), function() use ($app) {
        $request = $app->request();
        $mode = $request->getParameter("mode");

        if($mode == "by_login") {
            $login = $request->getParameter("login");
            $user_id = Libs\RESTilias::getUserName($login);
            if(empty($user_id)){
                $data = $request->getParameter("data");
                $userData = array_merge(array(
                    "login" => "{$login}",
                    "auth_mode" => "ldap",
                ), $data);
                $um = new Users\UsersModel();
                $user_id = $um->addUser($userData);
            }
        }
        else if ($mode == "by_id")
            $user_id = $request->getParameter("usr_id");
        else
            $app->halt(400, "Unsupported or missing mode: '$mode'. Use either 'by_login' or 'by_id'");

        $crs_ref_id = $request->getParameter("crs_ref_id");
        try {
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->joinCourse($user_id, $crs_ref_id);
        } catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(400, "Error: Subscribing user ".$user_id." to course with ref_id = ".$crs_ref_id." failed. Exception:".$e->getMessage());
        }

        if($mode == "by_login")
            $app->success(array("msg"=>"Enrolled user $login to course with id $crs_ref_id"));
        else
            $app->success(array("msg"=>"Enrolled user with id $user_id to course with id $crs_ref_id"));
    });

    /**
     * Adds the authenticated user as a member to a course specified by the parameter ref_id.
     */
    $app->get('/courses/join/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        $request = $app->request();
        try {
            //$ref_id = $request->getParameter("ref_id");
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->joinCourse($authorizedUserId, $ref_id);

            $result = array(
                'msg' => "User ".$authorizedUserId." subscribed to course with ref_id = " . $ref_id . " successfully.",
            );
            $app->success($result);
        } catch (Exceptions\SubscriptionFailed $e) {
            $app->halt(400, "Error: Subscribing user ".$authorizedUserId." to course with ref_id = ".$ref_id." failed. Exception:".$e->getMessage(), -15);
        }
    });

    /**
     * Removes the authenticated user from a course specified by the GET parameter "ref_id".
     */
    $app->get('/courses/leave/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        try {
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->leaveCourse($authorizedUserId, $ref_id);
            $app->success(array("msg"=>"User ".$authorizedUserId." has left course with ref_id = " . $ref_id . "."));

        } catch (Exceptions\CancelationFailed $e) {
            $app->halt(400, 'Error: Could not perform action for user '.$authorizedUserId.". ".$e->getMessage(), -15);
        }
    });

    /**
     * Creates a course a new export file (i.e. a zip file with the course contents). The course must be specified by the GET parameter "ref_id".
     */
    $app->get('/courses/export/create/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $crs_model = new CoursesModel();
            $aSuccess = $crs_model->createNewCourseExportFile($ref_id);

            $app->success(array("msg"=>$aSuccess));
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

    /**
     * Download an export files for the course specified by "ref_id".
     * Note: Parameter "filename" must be specified (see /v1/courses/export/list/:ref_id) or the most recent export
     * file will be determined if it exists.
     */
    $app->get('/courses/export/download/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $request = $app->request();
        try {
            $filename = $request->getParameter("filename", null, false);
            $crs_model = new CoursesModel();
            if ($filename == null) {
                // try to determine the latest export file
                $filename = $crs_model->determineLatestCourseExportFile($ref_id);
                if ($filename == null) throw new Libs\RESTException("Parameter 'filename' is missing. Could not find an existing export file.",-1);
            }
            $crs_model->downloadExportFile($ref_id, $filename);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

    /**
     * List all available export files for the course specified by "ref_id".
     */
    $app->get('/courses/export/list/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        //$accessToken = $app->request->getToken();
        try {
            $crs_model = new CoursesModel();
            $xmlFiles =  $crs_model->listExportFiles($ref_id);
            $result = array(
                'export_files' => $xmlFiles
            );
            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

    /**
     * Retrieves all contents of a course.
     * (OPTIONAL) Results can be filtered by adding these parameters to the request:
     *      "types": comma seperated list of all desired types of objects, e.g. "fold, tst"
     *      "title": title of the objects
     *      "description": description of the objects
     */
    $app->get('/courses/searchCourse/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $app->log->debug('in course get ref_id= '.$ref_id);
        try {
            $crs_model = new CoursesModel();
            $contents = $crs_model->getCourseContent($ref_id);

            $folders = array();

            foreach($contents as $content){
                if($content['type'] == 'fold'){
                    array_push($folders, $content);
                }
            }

            while($folder = array_shift($folders)){
                $f_id = $folder['ref_id'];
                $childContents = $crs_model->getCourseContent($f_id);
                foreach($childContents as $childContent){
                    if($childContent['type'] == 'fold'){
                        array_push($folders, $childContent);
                    }
                    array_push($contents, $childContent);
                }
            }

            //get filter parameters
            $types = $app->request->getParameter('types','*');
            $title = $app->request->getParameter('title','*');
            $description = $app->request->getParameter('description','*');
            $filtered_contents = array();

            //filter for type
            $type_filter = array();
            if($types != '*' && $types != ''){
                $types = explode(',', $types);
                foreach($contents as $content){
                    if(in_array($content['type'], $types)){
                        array_push($type_filter, $content);
                    }
                }
            }
            else{
                $type_filter = $contents;
            }

            //filter for title
            $title_filter = array();
            if($title != '*' && $title != ''){
                $title = mb_strtolower($title, 'UTF-8');
                foreach($type_filter as $content){
                    $content_title = mb_strtolower($content['title'], 'UTF-8');
                    if(strpos($content_title, $title) !== false){
                        array_push($title_filter, $content);
                    }
                }
            }
            else{
                $title_filter = $type_filter;
            }

            //filter for description
            $description_filter = array();
            if($description != '*' && $description != ''){
                $description = mb_strtolower($description, 'UTF-8');
                foreach($title_filter as $content){
                    $content_description = mb_strtolower($content['description'], 'UTF-8');
                    if(strpos($content_description, $description) !== false){
                        array_push($description_filter, $content);
                    }
                }
            }
            else{
                $description_filter = $title_filter;
            }

            $filtered_contents = $description_filter;

            $result = array(
                'contents' => $filtered_contents, // course contents
            );
            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

});
