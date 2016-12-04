<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\questionpools_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\questionpools_v1 as Questionpools;

$app->group('/v1', function () use ($app) {
    /**
     * Gets all questions of a Questionpool
     */
    $app->get('/questionpools/getQuestions/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $questionpool_model = new QuestionpoolModel();
            $questions = $questionpool_model->getQuestions($ref_id, $user_id);

            $result = array(
                'questions' => $questions
            );

            $app->success($result);
        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(500, $e->getFormatedMessage());
        }
    });
    /**
     * Gets the answers for a question of type 8 = assTextQuestion
     */
    $app->get('/questionpools/getTextAnswers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $questionpool_model = new QuestionpoolModel();
            $answers = $questionpool_model->getTextAnswers($ref_id, $user_id);

            $result = array(
                'answers' => $answers
            );

            $app->success($result);
        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(500, $e->getFormatedMessage());
        }
    });

});
