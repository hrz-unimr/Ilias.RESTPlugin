<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;


/*
 * Admin REST routes for TestPool and TestQuestion
 */

$app->group('/admin', function () use ($app) {
    $app->get('/testpool', '\RESTController\libs\OAuth2Middleware::TokenRouteAuthILIASAdminRole', function () use ($app) {

    });


    $app->get('/testquestion/:question_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuthILIASAdminRole', function ($question_id) use ($app) {
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
