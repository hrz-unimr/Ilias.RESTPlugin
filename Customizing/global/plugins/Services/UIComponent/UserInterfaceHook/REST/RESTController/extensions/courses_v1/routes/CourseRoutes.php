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
use \RESTController\extensions\users_v1 as Users;


/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/v1', function () use ($app) {
    /**
     * Retrieves the content and a description of a course specified by ref_id.
     */
    $app->get('/courses/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($ref_id) use ($app) {
        $response = new Libs\RESTResponse($app);
        $env = $app->environment();
        $authorizedUserId =  Libs\RESTLib::loginToUserId($env['user']);
        try {
            $crs_model = new CoursesModel();
            $data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);
            $response->addData('coursecontents', $data1);
            $response->addData('courseinfo', $data2);
            $response->setMessage("Content of course " . $ref_id . ".");
        } catch (\Exception $e) {
            $response->setRESTCode("-15");
            $response->setMessage('Error: Could not retrieve data for user '.$id.".");
        }
        $response->toJSON();
    });

    $app->post('/courses', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function() use ($app) {
        $env = $app->environment();
        $response = new Libs\RESTResponse($app);
        $authorizedUserId =  Libs\RESTLib::loginToUserId($env['user']);

        $parent_container_ref_id = 1;
        $new_course_title = "";
        $new_course_description = "";

        $reqBodyData = $app->request()->getBody(); // json
        $request = $app->request();

        if (count($request->post()) == 0) {
            $requestData = json_decode($reqBodyData, true);
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

        if(!$ilAccess->checkAccess("create_crs", "", $parent_container_ref_id)) {
            $response->setMessage("Insufficient access rights");
            $response->setHttpStatus(401);
            $response->send();
            return;
        }

        $new_ref_id =  $crs_model->createNewCourse($parent_container_ref_id, $new_course_title, $new_course_description);
        $response->setMessage("Created a new course with ref id ".$new_ref_id.". Parent ref_id: ".$parent_container_ref_id);
        $response->setData("newRefId", $new_ref_id);
        $response->send();
    });

    $app->delete('/courses/:id',  function ($id) use ($app) {
        $request = $app->request();
        $env = $app->environment();
        // todo: check permissions
        $result = array();
        $crs_model = new CoursesModel();
        $soap_result = $crs_model->deleteCourse($id);

        $result['msg'] = 'OP: Delete Course . '.$id;
        $result['soap_result'] = $soap_result;
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);
    });

    /**
     * Enroll an User to a Course.
     * Expects a "mode" parameter ("by_login"/"by_id") that determines the
     * lookup method for the user.
     * If "mode" is "by_login", the "login" parameter is used for the lookup.
     * If no user is found, a new LDAP user is created with attributes from
     * the "data" array.
     * If "mode" is "by_id", the parameter "usr_id" is used for the lookup.
     * The user is then enrolled in the course with "crs_ref_id".
     */
    $app->post('/courses/enroll', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function() use ($app) {
        $env = $app->environment();
        $response = new Libs\RESTResponse($app);
        $request = new Libs\RESTRequest($app);
        $mode = $request->getParam("mode");
        if($mode == "by_login") {
            $login = $request->getParam("login");
            $user_id = Libs\RESTLib::loginToUserId($login);
            if(empty($user_id)){
                $data = $request->getParam("data");
                $userData = array_merge(array(
                    "login" => "{$login}",
                    "auth_mode" => "ldap",
                ), $data);
                $um = new Users\UsersModel();
                $user_id = $um->addUser($userData);
            }
        } else if ($mode == "by_id") {
            $user_id = $request->getParam("usr_id");
        } else {
            $response->setHttpStatus(400);
            $response->setMessage("Unsupported or missing mode: '$mode'. Use eiter 'by_login' or 'by_id'");
            $response->toJSON();
            return;
        }
        $crs_ref_id = $request->getParam("crs_ref_id");
        try {
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->joinCourse($user_id, $crs_ref_id);
        } catch (\Exception $e) {
            $response->setMessage("Error: Subscribing user ".$user_id." to course with ref_id = ".$crs_ref_id." failed. Exception:".$e);
            $response->setHttpStatus(400);
            $response->toJSON();
            return;
        }
        if($mode = "by_login") {
            $response->setMessage("Enrolled user $login to course with id $crs_ref_id");
        } else {
            $response->setMessage("Enrolled user with id $user_id to course with id $crs_ref_id");
        }
        $response->toJSON();

    });

    $app->get('/courses/join', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
        $env = $app->environment();
        $response = new Libs\RESTResponse($app);
        $request = new Libs\RESTRequest($app);
        $authorizedUserId =  Libs\RESTLib::loginToUserId($env['user']);
        try {
            $ref_id = $request->getParam("ref_id");
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->joinCourse($authorizedUserId, $ref_id);
            /*$data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);
            $response->addData('coursecontents', $data1);
            $response->addData('courseinfo', $data2);*/
            $response->setMessage("User ".$authorizedUserId." subscribed to course with ref_id = " . $ref_id . " successfully.");
        } catch (\Exception $e) {
            $response->setRESTCode("-15");
            $response->setMessage("Error: Subscribing user ".$authorziedUserid." to course with ref_id = ".$ref_id." failed. Exception:".$e);
            //$response->setMessage('Error: Could not perform action for user '.$id.".".$e);
            $response->setMessage($e);
        }
        $response->toJSON();
    });

    $app->get('/courses/leave', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
        $env = $app->environment();
        $response = new Libs\RESTResponse($app);
        $request = new Libs\RESTRequest($app);
        $authorizedUserId =  Libs\RESTLib::loginToUserId($env['user']);
        try {
            $ref_id = $request->getParam("ref_id");
            $crsreg_model = new CoursesRegistrationModel();
            $crsreg_model->leaveCourse($authorizedUserId, $ref_id);

            $response->setMessage("User ".$authorizedUserId." has left course with ref_id = " . $ref_id . ".");
        } catch (\Exception $e) {
            $response->setRESTCode("-15");
            $response->setMessage('Error: Could not perform action for user '.$authorizedUserId.".".$e);
            $response->setMessage($e);
        }
        $response->toJSON();
    });


});
