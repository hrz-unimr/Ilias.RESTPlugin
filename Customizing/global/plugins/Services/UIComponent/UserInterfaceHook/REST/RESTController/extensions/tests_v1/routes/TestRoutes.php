<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\tests_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\tests_v1 as Tests;

$app->group('/v1', function () use ($app) {
    /**
     * Downloads a tests specified by its ref_id.
     */
    $app->get('/tests/download/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $test_model = new TestModel();
            $test_model->downloadTestExportFile($ref_id,$user_id);

        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(500, $e->getFormatedMessage());
        }
    });

    /**
     * Delivers a JSON representation of a test.
     */
    $app->get('/tests/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $test_model = new TestModel();
            $info = $test_model->getBasicInformation($ref_id, $user_id);
            $participants =  $test_model->getTestParticipants($ref_id, $user_id);

            $result = array(
                'test' => array($info,$participants)
            );

            $app->success($result);
        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(500, $e->getFormatedMessage());
        }
    });

});
