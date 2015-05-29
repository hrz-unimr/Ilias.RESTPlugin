<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/*
 * Admin REST routes for TestPool and TestQuestion
 */

$app->group('/admin', function () use ($app) {
    $app->get('/testpool', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {

    });


    $app->get('/testquestion/:question_id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($question_id) use ($app) {
        $model = new TestQuestionModel();
        $data = $model->getQuestion($question_id);

        $app->success($data);
    });
});
?>
