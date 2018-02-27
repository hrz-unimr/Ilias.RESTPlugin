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
     * Gets the questions of a Questionpool
     * (OPTIONAL) Desired types of questions can be specified in the 'types' parameter of the request 
     * e.g. "1,2,8" for single choice, multiple choice and text questions only.
     */
    $app->get('/questionpools/getQuestions/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $types = $app->request->getParameter('types','*');

            $questionpool_model = new QuestionpoolModel();
            $questions = $questionpool_model->getQuestions($ref_id, $user_id, $types);

            $result = array(
                'questions' => $questions
            );
        
            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });
    /**
     * Gets the questions of a Questionpool including answers for questions of type 1,2,8. Other types still need to be implemented.
     * (OPTIONAL) Desired types of questions can be specified in the 'types' parameter of the request 
     * e.g. "1,2,8" for single choice, multiple choice and text questions only.
     */
    $app->get('/questionpools/getQuestionsWithAnswers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $types = $app->request->getParameter('types','*');

            $questionpool_model = new QuestionpoolModel();
            $questions = $questionpool_model->getQuestions($ref_id, $user_id, $types);

            //get answers for each question
            foreach($questions as &$question){
                switch($question['question_type_fi']){
                    case 1:
                        //single choice question
                        $question['answers'] = $questionpool_model->getSingleChoiceAnswers($question['question_id'], $user_id);
                        break;
                    case 2:
                        //multiple choice question
                        $question['answers'] = $questionpool_model->getMultipleChoiceAnswers($question['question_id'], $user_id);
                        break;
                    case 8:
                        //text question
                        $question['answers'] = $questionpool_model->getTextAnswers($question['question_id'], $user_id);
                        break;
                }
            }

            $result = array(
                'questions' => $questions
            );
        
            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
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
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });
    /**
     * Gets the answers for a question of type 1 = assSingleChoice
     */
    $app->get('/questionpools/getSingleChoiceAnswers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $questionpool_model = new QuestionpoolModel();
            $answers = $questionpool_model->getSingleChoiceAnswers($ref_id, $user_id);

            $result = array(
                'answers' => $answers
            );

            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });
    /**
     * Gets the answers for a question of type 2 = assMultipleChoice
     */
    $app->get('/questionpools/getMultipleChoiceAnswers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $questionpool_model = new QuestionpoolModel();
            $answers = $questionpool_model->getMultipleChoiceAnswers($ref_id, $user_id);

            $result = array(
                'answers' => $answers
            );

            $app->success($result);
        } catch (\Exception $e) {
            $app->halt(500, $e->getMessage());
        }
    });

});
