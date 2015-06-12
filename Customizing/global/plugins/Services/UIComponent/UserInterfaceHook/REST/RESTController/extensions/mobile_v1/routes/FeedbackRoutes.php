<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
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
    $app->get('/feedbackdrop/', '\RESTController\libs\OAuth2Middleware::TokenAuth' ,  function () use ($app) {
        $request = $app->request();
        try {
            $s_msg = $request->params('message','',true);
            $s_env = $request->params('env','',true);
            $auth = new Auth\Util();
            $accessToken = $auth->getAccessToken();
            $s_uid = $accessToken->getTokenString();

            $model = new MobileFeedbackModel();
            $model->createFeedbackItem($s_uid, $s_msg, $s_env);
        } catch (Libs\Exceptions\MissingParameter $e) {
            $app->halt(422, $e->getMessage(), $e::ID);
        }
        $app->success("Created new feedback entry.");
    });

    /**
     * Allows for submission of a new feedback entry via POST.
     */
     $app->post('/feedbackdrop/', '\RESTController\libs\OAuth2Middleware::TokenAuth',  function () use ($app) {
         $request = $app->request();
         try {
             $s_msg = $request->params('message','',true);
             $s_env = $request->params('env','',true);
             $auth = new Auth\Util();
             $accessToken = $auth->getAccessToken();
             $s_uid = $accessToken->getTokenString();

             $model = new MobileFeedbackModel();
             $model->createFeedbackItem($s_uid, $s_msg, $s_env);
         } catch (Libs\Exceptions\MissingParameter $e) {
             $app->halt(422, $e->getMessage(), $e::ID);
         }
         $app->success("Created new feedback entry.");
     });


    /**
     * Allows for retrieval of single feedback entries.
     */
     $app->get('/feedbackread/:id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth',  function ($id) use ($app) {
         $model = new MobileFeedbackModel();
         $data = $model->getFeedbackItem($id);
         $app->success($data);
     });

    /**
     * Allows for deletion of single feedback entries.
     */
    $app->delete('/feedbackdel/:id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth',  function ($id) use ($app) {
        $model = new MobileFeedbackModel();
        $model->deleteFeedbackItem($id);
        $app->success("Sucessfully deleted item ".$id);
    });


    /**
     * Initializes the feedback extension, i.e. creates the database tables.
     */
    $app->get('/feedbackinit', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
        $model = new MobileFeedbackModel();
        $model->createMobileFeedbackDatabaseTable();
        $app->success("Created new feedback database.");
    });

});
