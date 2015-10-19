<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\experimental;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\core\clients as Clients;
use \RESTController\extensions\surveys_v1 as Surveys;

$app->group('/v1', function () use ($app) {

    /**
     * (Admin) Assigns random answers to the question of survey (ref_id) for user (user_id).
     */
    $app->post('/svy_rand', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
         $request = $app->request();
         $attribs = array("ref_id","user_id");
         $req_data = array();
         foreach($attribs as $a) {
             $req_data[$a] = $request->params($a);
         }
         $model = new Surveys\SurveyModel();
         $model->fillRandomAnswers($req_data['ref_id'], $req_data['user_id']);
         $app->success("ok");
    });

    /**
     * Returns a Json representation of the survey ref_id.
     */
    $app->get('/survey/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function($ref_id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user_id = $accessToken->getUserId();

        $model = new Surveys\SurveyModel();
        $result = array();
        $result['svy_id'] = $ref_id;
        $result['questions'] = $model->getJsonRepresentation($ref_id,$user_id);
        $app->success($result);
    });

    /**
     * Returns the answers of a survey (ref_id) of the authenticated user.
     */
    $app->get('/survey_answers/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function($ref_id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user_id = $accessToken->getUserId();

        $model = new Surveys\SurveyModel();
        $result = $model->getSurveyResultsOfUser($ref_id, $user_id);
        $app->success($result);
    });

    /**
     * Deletes answers of all users of a survey (ref_id).
     */
    $app->delete('/survey_answers/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function($ref_id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user_id = $accessToken->getUserId();
        $model = new Surveys\SurveyModel();
        $model->removeSurveyResultsOfAllUsers($ref_id, $user_id); // TODO deleteAllUserData
        $app->success(200);
    });

    /**
     * Stores the answers of one particular question of survey ref_id.
     * Note: Only question types SC and MPC are supported.
     *
     * Post-data:
     * {'nQuestions' => '5', 'q1_id'=>'1', 'q1_answer'=>'2,3',...}
     */
    $app->post('/survey_answers/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function($ref_id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user_id = $accessToken->getUserId();

        $request = $app->request();
        $nQuest = $request->params('nQuestions');
        if (isset($nQuest) == true) {
            $nQuest = intval($nQuest);
            $answers = array();
            for ($i = 0; $i < $nQuest; $i++) {
                $ct_id = $request->params('q'.($i+1).'_id');
                $ct_answer_choices = $request->params('q'.($i+1).'_answer');
                $answers[] = array('id'=>$ct_id, 'answer'=>$ct_answer_choices);
            }

            $model = new Surveys\SurveyModel();
            $active_id = $model->beginSurvey($ref_id, $user_id);
            for  ($i=0;$i < count($answers); $i++) {
                $ct_question_id = $answers[$i]['id'];
                $ct_answers =  $answers[$i]['answer'];
                $model->saveQuestionAnswer($ref_id, $user_id, $active_id, $ct_question_id, $ct_answers);
                //$model->saveQuestionAnswer($ref_id, $user_id, $active_id, $question_id, "1");
            }
            $model->finishSurvey($ref_id,$user_id,$active_id);
            $result = $model->getSurveyResultsOfUser($ref_id,$user_id);
            $app->success(200, $result);
        } else {
            $app->success(422);
        }

    });

});