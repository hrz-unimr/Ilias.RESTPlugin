<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\experimental;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\core\clients as Clients;
use \RESTController\extensions\surveys_v1 as Surveys;

$app->group('/v1', function () use ($app) {

    /**
     * (Admin) Provides an overview about all existing surveys.
     */
    $app->get('/surveys', RESTAuth::checkAccess(RESTAuth::ADMIN), function() use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        $model = new Surveys\SurveyModel();
        $result = array();
        $result['surveys'] = $model->getAllSurveys($user_id);
        $app->success($result);
    });

    /**
     * Returns a json representation of the survey ref_id.
     */
    $app->get('/surveys/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        $model = new Surveys\SurveyModel();
        $result = array();
        $result['svy_id'] = $ref_id;
        $result['questions'] = $model->getJsonRepresentation($ref_id,$user_id);
        $app->success($result);
    });

    /**
     * Returns the answers of the authenticated user of a survey specified by ref_id.
     */
    $app->get('/surveys/survey_answers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        $model = new Surveys\SurveyModel();
        $result = $model->getSurveyResultsOfUser($ref_id, $user_id);
        $app->success($result);
    });

    /**
     * Deletes the answers of all users of a survey  specified by ref_id.
     */
    $app->delete('/surveys/survey_answers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();
        $model = new Surveys\SurveyModel();
        $model->removeSurveyResultsOfAllUsers($ref_id, $user_id);
        $app->success(200);
    });

    /**
     * Submits the answers of one particular question of survey ref_id.
     * Note: Only question types SC and MPC are supported.
     *
     * Post-data:
     * {'nQuestions' => '5', 'q1_id'=>'1', 'q1_answer'=>'2,3',...}
     */
    /*$app->post('/surveys/survey_answers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        $request = $app->request();
        $nQuest = $request->getParameter('nQuestions');
        if (isset($nQuest) == true) {
            $nQuest = intval($nQuest);
            $answers = array();
            for ($i = 0; $i < $nQuest; $i++) {
                $ct_id = $request->getParameter('q'.($i+1).'_id');
                $ct_answer_choices = $request->getParameter('q'.($i+1).'_answer');
                $answers[] = array('id'=>$ct_id, 'answer'=>$ct_answer_choices);
            }

            $model = new Surveys\SurveyModel();
            $active_id = $model->beginSurvey($ref_id, $user_id);
            for  ($i=0;$i < count($answers); $i++) {
                $ct_question_id = $answers[$i]['id'];
                $ct_answers =  $answers[$i]['answer'];
                $model->saveQuestionAnswer($ref_id, $user_id, $active_id, $ct_question_id, $ct_answers);
            }
            $model->finishSurvey($ref_id,$user_id,$active_id);
            $result = $model->getSurveyResultsOfUser($ref_id,$user_id);
            $app->success(200, $result);
        } else {
            $app->success(422);
        }

    });*/

    /**
     * (Admin) Assigns random answers to the question of survey (ref_id) for user (user_id).
     */
    /*$app->post('/surveys/survey_answers_randfill/:ref_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($ref_id) use ($app) {
        $request = $app->request();
        $user_id = $request->getParameter("user_id");
        $model = new Surveys\SurveyModel();
        $model->fillRandomAnswers($$ref_id, $user_id);
        $app->success(200);
    });
    */

});
