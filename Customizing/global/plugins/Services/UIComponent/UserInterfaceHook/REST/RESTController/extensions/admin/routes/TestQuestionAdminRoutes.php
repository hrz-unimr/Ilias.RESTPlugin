<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


$app->group('/admin', function () use ($app) {
    $app->get('/testpool', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
        $app->halt(500, 'There be dragons!');
    });


    $app->get('/testquestion/:question_id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($question_id) use ($app) {
        $model = new TestQuestionModel();
        $data = $model->getQuestion($question_id);

        $app->success($data);
    });
});
?>
