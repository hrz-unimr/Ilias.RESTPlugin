<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/experimental', function () use ($app) {

    $app->get('/courses/:ref_id', function ($ref_id) use ($app) {
        try {
            $app = \Slim\Slim::getInstance();
            $env = $app->environment();

            $result = array();
            // $result['usr_id'] = $user_id;
            $crs_model = new ilCoursesModel();
            $data1 =  $crs_model->getCourseContent($ref_id);
            $data2 =  $crs_model->getCourseInfo($ref_id);
            $result['course_description'] = $data2;
            $result['course_content'] = $data1;

            $app->response()->header('Content-Type', 'application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            $app->response()->status(400);
            $app->response()->header('X-Status-Reason', $e->getMessage());
        }
    });

    $app->post('/courses',  function() use ($app) {
        $app = \Slim\Slim::getInstance();
        $env = $app->environment();

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
        $user_id = 6; // root for testing purposes
        $new_ref_id =  $crs_model->createNewCourseAsUser($user_id, $parent_container_ref_id, $new_course_title, $new_course_description);
        $result['msg'] = "Created a new course with ref id ".$new_ref_id.". Parent ref_id: ".$parent_container_ref_id;


        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);


    });



    $app->delete('/courses/:id',  function ($id) use ($app) {
        $app = \Slim\Slim::getInstance();
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


});
?>