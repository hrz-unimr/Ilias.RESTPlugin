<?php
/*
 * Admin REST routes for TestPool and TestQuestion
 */

$app->group('/admin', function () use ($app) {

    $app->get('/testpool', 'authenticateILIASAdminRole', function () use ($app) {

    });


    $app->get('/testquestion/:question_id', 'authenticateILIASAdminRole', function ($question_id) use ($app) {
        $request = new ilRESTRequest($app);
        $response = new ilRESTResponse($app);

        $model = new ilTestQuestionModel();
        $data = $model->getQuestion($question_id);
        $response->setData('question',$data);
        $response->setMessage('Success.');
        $response->send();
    });
});
?>
