<?php
/*
 * Admin REST routes for TestPool and TestQuestion
 */

$app->group('/admin', function () use ($app) {

    $app->get('/testpool', 'authenticateILIASAdminRole', function () use ($app) {

    });


    $app->get('/testquestion/:question_id', 'authenticateILIASAdminRole', function ($question_id) use ($app) {
        $request = new RESTRequest($app);
        $response = new RESTResponse($app);

        $model = new TestQuestionModel();
        $data = $model->getQuestion($question_id);
        $response->setData('question',$data);
        $response->setMessage('Success.');
        $response->send();
    });
});
?>
