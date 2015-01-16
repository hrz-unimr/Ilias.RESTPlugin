<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/v1', function () use ($app) {
    /**
     * Retrieves the content and a description of a course specified by ref_id.
     */
    $app->get('/courses/:ref_id', 'authenticate', function ($ref_id) use ($app) {
        $response = new ilRestResponse($app);
        $env = $app->environment();
        $authorizedUserId =  ilRestLib::loginToUserId($env['user']);
        try {
            $crs_model = new ilCoursesModel();
            $data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);
            $response->addData('coursecontents', $data1);
            $response->addData('courseinfo', $data2);
            $response->setMessage("Content of course " . $ref_id . ".");
        } catch (Exception $e) {
            $response->setRestCode("-15");
            $response->setMessage('Error: Could not retrieve data for user '.$id.".");
        }
        $response->toJSON();
    });

    $app->post('/courses', 'authenticate', function() use ($app) {
        $env = $app->environment();
        $authorizedUserId =  ilRestLib::loginToUserId($env['user']);

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
        $result = array();
        // $result['usr_id'] = $user_id;
        $crs_model = new ilCoursesModel();
        //$user_id = 6; // root for testing purposes
        $user_id = $authorizedUserId;

        $new_ref_id =  $crs_model->createNewCourseAsUser($user_id, $parent_container_ref_id, $new_course_title, $new_course_description);
        $result['msg'] = "Created a new course with ref id ".$new_ref_id.". Parent ref_id: ".$parent_container_ref_id;

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);
    });

    $app->delete('/courses/:id',  function ($id) use ($app) {
        $request = $app->request();
        $env = $app->environment();
        // todo: check permissions
        $result = array();
        $crs_model = new ilCoursesModel();
        $soap_result = $crs_model->deleteCourse($id);

        $result['msg'] = 'OP: Delete Course . '.$id;
        $result['soap_result'] = $soap_result;
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);
    });


    $app->get('/courses/join', 'authenticate', function () use ($app) {
        $env = $app->environment();
        $response = new ilRestResponse($app);
        $request = new ilRestRequest($app);
        $authorizedUserId =  ilRestLib::loginToUserId($env['user']);
        try {
            $ref_id = $request->getParam("ref_id");
            $crsreg_model = new ilCoursesRegistrationModel();
            $crsreg_model->joinCourse($authorizedUserId, $ref_id);
            /*$data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);
            $response->addData('coursecontents', $data1);
            $response->addData('courseinfo', $data2);*/
            $response->setMessage("User ".$authorizedUserId." subscribed to course with ref_id = " . $ref_id . " successfully.");
        } catch (Exception $e) {
            $response->setRestCode("-15");
            $response->setMessage("Error: Subscribing user ".$authorziedUserid." to course with ref_id = ".$ref_id." failed. Exception:".$e);
            //$response->setMessage('Error: Could not perform action for user '.$id.".".$e);
            $response->setMessage($e);
        }
        $response->toJSON();
    });

    $app->get('/courses/leave', 'authenticate', function () use ($app) {
        $env = $app->environment();
        $response = new ilRestResponse($app);
        $request = new ilRestRequest($app);
        $authorizedUserId =  ilRestLib::loginToUserId($env['user']);
        try {
            $ref_id = $request->getParam("ref_id");
            $crsreg_model = new ilCoursesRegistrationModel();
            $crsreg_model->leaveCourse($authorizedUserId, $ref_id);

            $response->setMessage("User ".$authorizedUserId." has left course with ref_id = " . $ref_id . ".");
        } catch (Exception $e) {
            $response->setRestCode("-15");
            $response->setMessage('Error: Could not perform action for user '.$authorizedUserId.".".$e);
            $response->setMessage($e);
        }
        $response->toJSON();
    });


});
?>