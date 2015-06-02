<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\courses_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\users_v1 as Users;


$app->group('/v1', function () use ($app) {
    /**
     * Retrieves the content and a description of a course specified by ref_id.
     */
    $app->get('/courses/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($ref_id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $id = $accessToken->getUserId();

        //try {
            $crs_model = new CoursesModel();
            $data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);

            $result = array(
                'coursecontents' => $data1,
                'courseinfo' => $data2
            );
            $app->success($result);
        /*} catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(500, 'Error: Could not retrieve data for user '.$id.".", -15);
        }*/
    });


    $app->post('/courses', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function() use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        $parent_container_ref_id = 1;
        $new_course_title = "";
        $new_course_description = "";

        $request = $app->request();
        if (count($request->post()) == 0) {
            $requestData = $app->request()->getBody(); // Gives php-array (with correct request content-type!!!)
            var_dump($requestData);
            die();
            $parent_container_ref_id = array_key_exists('ref_id', $requestData) ? $requestData['ref_id'] : null;
            $new_course_title = array_key_exists('new_course_title', $requestData) ? $requestData['title'] : null;
            $new_course_description= array_key_exists('new_course_description', $requestData) ? $requestData['description'] : null;
        } else {
            $parent_container_ref_id = $request->post('ref_id');
            $new_course_title = $request->post('title');
            $new_course_description = $request->post('description');
        }
        // $result['usr_id'] = $user_id;
        $crs_model = new CoursesModel();
        //$user_id = 6; // root for testing purposes
        $user_id = $authorizedUserId;

        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTLib::initAccessHandling();
        global $ilAccess;

        if(!$ilAccess->checkAccess("create_crs", "", $parent_container_ref_id))
            $app->halt(401, "Insufficient access rights");

        $new_ref_id =  $crs_model->createNewCourse($parent_container_ref_id, $new_course_title, $new_course_description);
        $app->success($new_ref_id);
    });


    $app->delete('/courses/:id',  function ($id) use ($app) {
        $request = $app->request();
        // todo: check permissions
        $result = array();
        $crs_model = new CoursesModel();
        $soap_result = $crs_model->deleteCourse($id);

        $app->success($soap_result);
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
    $app->post('/courses/enroll', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function() use ($app) {
        $request = $app->request();
        $mode = $request->params("mode");

        if($mode == "by_login") {
            $login = $request->params("login");
            $user_id = Libs\RESTLib::getIdFromUserName($login);
            if(empty($user_id)){
                $data = $request->params("data");
                $userData = array_merge(array(
                    "login" => "{$login}",
                    "auth_mode" => "ldap",
                ), $data);
                $um = new Users\UsersModel();
                $user_id = $um->addUser($userData);
            }
        }
        else if ($mode == "by_id")
            $user_id = $request->params("usr_id");
        else
            $app->halt(400, "Unsupported or missing mode: '$mode'. Use eiter 'by_login' or 'by_id'");

        $crs_ref_id = $request->params("crs_ref_id");
        try {
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->joinCourse($user_id, $crs_ref_id);
        } catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(400, "Error: Subscribing user ".$user_id." to course with ref_id = ".$crs_ref_id." failed. Exception:".$e->getMessage());
        }

        if($mode = "by_login")
            $app->success(null, "Enrolled user $login to course with id $crs_ref_id");
        else
            $app->success(null, "Enrolled user with id $user_id to course with id $crs_ref_id");
    });


    $app->get('/courses/join', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        $request = $app->request();
        try {
            $ref_id = $request->params("ref_id");
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->joinCourse($authorizedUserId, $ref_id);

            /*
            $data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);
            */
            $result = array(
                //'coursecontents' => $data1,
                //'courseinfo' => $data2,
            );
            $app->success($result, "User ".$authorizedUserId." subscribed to course with ref_id = " . $ref_id . " successfully.");
        } catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(400, "Error: Subscribing user ".$authorziedUserid." to course with ref_id = ".$ref_id." failed. Exception:".$e->getMessage(), -15);
        }
    });


    $app->get('/courses/leave', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        $request = $app->request();
        try {
            $ref_id = $request->params("ref_id", null, true);
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->leaveCourse($authorizedUserId, $ref_id);

            $app->success(null, "User ".$authorizedUserId." has left course with ref_id = " . $ref_id . ".");
        } catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(400, 'Error: Could not perform action for user '.$authorizedUserId.". ".$e->getMessage(), -15);
        }
    });


});
