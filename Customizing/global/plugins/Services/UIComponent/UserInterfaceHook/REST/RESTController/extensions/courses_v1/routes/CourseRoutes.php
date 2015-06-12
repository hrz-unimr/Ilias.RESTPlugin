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
        try {
            $request = $app->request();
            $ref_id = $request->params('ref_id', null, true);
            $title = $request->params('title', null, true);
            $description = $request->params('description', '');

            Libs\RestLib::setupUserContext();
            if(!$GLOBALS['ilAccess']->checkAccess("create_crs", "", $ref_id))
                $app->halt(401, "Insufficient access rights");

            $crs_model = new CoursesModel();
            $new_ref_id =  $crs_model->createNewCourse($ref_id, $title, $description);

            $result = array('refId' => $new_ref_id);
            $app->success($result);
        }
        catch (LibExceptions\MissingParameter $e) {
            $app->halt(422, $e->getFormatedMessage(), $e::ID);
        }
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
            if(!$crsreg_model->joinCourse($user_id, $crs_ref_id)) {
                $app->halt(400, "Error: Subscribing user ".$user_id." to course with ref_id = ".$crs_ref_id." failed.");
            }
        } catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(400, "Error: Subscribing user ".$user_id." to course with ref_id = ".$crs_ref_id." failed. Exception:".$e->getMessage());
        }

        if($mode == "by_login")
            $app->success("Enrolled user $login to course with id $crs_ref_id");
        else
            $app->success("Enrolled user with id $user_id to course with id $crs_ref_id");
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
                'msg' => "User ".$authorizedUserId." subscribed to course with ref_id = " . $ref_id . " successfully.",
            );
            $app->success($result);
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

            $app->success("User ".$authorizedUserId." has left course with ref_id = " . $ref_id . ".");
        } catch (\Exception $e) {
            // TODO: Replace message with const-class-variable and error-code with unique string
            $app->halt(400, 'Error: Could not perform action for user '.$authorizedUserId.". ".$e->getMessage(), -15);
        }
    });


});
