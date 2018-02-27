<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;


/**
 * The feedback endpoints allow for sending information regarding a mobile application.
 * E.g. user-feedback, debug-messages from the app
 */
$app->group('/v1/m', function () use ($app) {

    /**
     * Allows for submission of a new feedback entry via GET.
     */
    $app->get('/feedbackdrop/', RESTAuth::checkAccess(RESTAuth::PERMISSION) ,  function () use ($app) {
        $request = $app->request();
        try {
            $s_msg = $request->getParameter('message','',true);
            $s_env = $request->getParameter('env','',true);

            $accessToken = $app->request->getToken();
            $s_uid = $accessToken->getTokenString();

            $model = new MobileFeedbackModel();
            $id = $model->createFeedbackItem($s_uid, $s_msg, $s_env);
            $app->success(array("msg"=>"Created new feedback entry.", "id"=>$id));
        } catch (Libs\Exceptions\Parameter $e) {
            $e->send(400);
        }
    });

    /**
     * Allows for submission of a new feedback entry via POST.
     */
     $app->post('/feedbackdrop/', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function () use ($app) {
         $request = $app->request();
         try {
             $s_msg = $request->getParameter('message','',true);
             $s_env = $request->getParameter('env','',true);

             $accessToken = $app->request->getToken();
             $s_uid = $accessToken->getTokenString();

             $model = new MobileFeedbackModel();
             $id = $model->createFeedbackItem($s_uid, $s_msg, $s_env);
             $app->success(array("msg"=>"Created new feedback entry.", "id"=>$id));
         } catch (Libs\Exceptions\Parameter $e) {
             $e->send(400);
         }

     });


    /**
     * Allows for retrieval of single feedback entries.
     */
     $app->get('/feedbackread/:id', RESTAuth::checkAccess(RESTAuth::ADMIN),  function ($id) use ($app) {
         $model = new MobileFeedbackModel();
         $data = $model->getFeedbackItem($id);
         $app->success($data);
     });

    /**
     * Allows for deletion of single feedback entries.
     */
    $app->delete('/feedbackdel/:id', RESTAuth::checkAccess(RESTAuth::ADMIN),  function ($id) use ($app) {
        $model = new MobileFeedbackModel();
        $model->deleteFeedbackItem($id);
        $app->success(array("msg"=>"Sucessfully deleted item ","id"=>$id));
    });


    /**
     * Initializes the feedback extension, i.e. creates the database tables.
     */
    $app->get('/feedbackinit', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
        $model = new MobileFeedbackModel();
        $hasCreated = $model->createMobileFeedbackDatabaseTable();
        if ($hasCreated == true) {
            $app->success(array("msg"=>"Created new feedback database."));
        } else {
            $app->success(array("msg"=>"Feedback database already exists. Nothing changed."));
        }

    });

});
