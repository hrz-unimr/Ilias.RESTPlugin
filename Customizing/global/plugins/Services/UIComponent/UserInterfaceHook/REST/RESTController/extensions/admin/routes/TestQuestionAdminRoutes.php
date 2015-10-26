<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuthFactory as AuthFactory;
use \RESTController\libs as Libs;


$app->group('/admin', function () use ($app) {
    /*
     // TODO: support for querying test question pools
    $app->get('/testpool', AuthFactory::checkAccess(AuthFactory::ADMIN), function () use ($app) {
        $app->halt(500, 'There be dragons!');
    });
    */

    /**
     * Returns a (json) representation of a test question given its question_id.
     */
    $app->get('/testquestion/:question_id', AuthFactory::checkAccess(AuthFactory::ADMIN), function ($question_id) use ($app) {
        $model = new TestQuestionModel();
        $data = $model->getQuestion($question_id);

        $app->success($data);
    });
});
?>
