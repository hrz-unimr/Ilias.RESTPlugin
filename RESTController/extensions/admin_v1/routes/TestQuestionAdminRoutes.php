<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;


$app->group('/v1/admin', function () use ($app) {
    /*
     // TODO: support for querying test question pools
    $app->get('/testpool', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
        $app->halt(500, 'There be dragons!');
    });
    */

    /**
     * Returns a (json) representation of a test question given its question_id.
     */
    $app->get('/testquestion/:question_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($question_id) use ($app) {
        $model = new TestQuestionModel();
        $data = $model->getQuestion($question_id);

        $app->success($data);
    });
});
?>
