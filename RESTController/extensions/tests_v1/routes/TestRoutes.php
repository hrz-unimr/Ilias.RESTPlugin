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
use \RESTController\extensions\questionpools_v1 as Questionpools;

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

    /**
     * Retrieves all questions of a test.
     * (OPTIONAL) Desired types of questions can be specified in the 'types' parameter of the request 
     * e.g. "1,2,8" for single choice, multiple choice and text questions only.
     */
    $app->get('/tests/getQuestions/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $types = $app->request->getParameter('types','*');

            $test_model = new TestModel();
            $questions = $test_model->getQuestions($ref_id,$user_id, $types);

            $result = array(
                'questions' => $questions
            );

            $app->success($result);
        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(500, $e->getFormatedMessage());
        }
    });

    /**
     * Retrieves all questions of a test including answers for questions of type 1,2,8. Other types still need to be implemented.
     * (OPTIONAL) Desired types of questions can be specified in the 'types' parameter of the request 
     * e.g. "1,2,8" for single choice, multiple choice and text questions only.
     */
    $app->get('/tests/getQuestionsWithAnswers/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        try {
            $accessToken = $app->request->getToken();
            $user_id = $accessToken->getUserId();

            $types = $app->request->getParameter('types','*');

            $test_model = new TestModel();
            $questions = $test_model->getQuestions($ref_id,$user_id, $types);

            //get answers for each question
            $questionpool_model = new Questionpools\QuestionpoolModel();
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
        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(500, $e->getFormatedMessage());
        }
    });
});
