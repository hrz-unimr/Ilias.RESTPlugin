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

        $request = $app->request();
        $qst_id = $request->params('qst_id');
        $choice_csv_str = $request->params('choice_csv');
        $sel_answers = explode(',',$choice_csv_str);
        for ($i = 0; $i<count($sel_answers);$i++) {
            $sel_answers[$i] = $sel_answers[$i] - 1;
        }
        //$attribs = array("qst_id","choice_csv");
        /*$req_data = array();
        foreach($attribs as $a) {
            $req_data[$a] = $request->params($a);
        }*/

        $model = new Surveys\SurveyModel();
        $result = $model->getSurveyResultsOfUser($ref_id, $user_id);
        $app->success($result);
    });

    /**
     * Stores the answers of one particular question of survey ref_id.
     */
    $app->post('/survey_answer/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function($ref_id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user_id = $accessToken->getUserId();
        // TODO
        $model = new Surveys\SurveyModel();
        $result = $model->getSurveyResultsOfUser($ref_id,$user_id);
        $app->success($result);
    });

});