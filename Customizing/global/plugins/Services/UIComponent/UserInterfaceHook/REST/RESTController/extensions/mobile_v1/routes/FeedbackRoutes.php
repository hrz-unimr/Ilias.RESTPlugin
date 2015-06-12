<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\extensions\admin as Admin;
use \RESTController\extensions\users_v1 as Users;
use \RESTController\extensions\courses_v1 as Courses;
use \RESTController\extensions\desktop_v1 as Desktop;
use \RESTController\extensions\groups_v1 as Groups;
use \RESTController\extensions\contacts_v1 as Contacts;
use \RESTController\extensions\calendar_v1 as Calendar;

/**
 * The feedback endpoints allow for sending information regarding a mobile application.
 * E.g. user-feedback, debug-messages from the app
 */
$app->group('/v1/m', function () use ($app) {

    /**
     * Allows for submission of a new feedback entry via GET.
     */
    $app->get('/feedbackdrop/',  function () use ($app) {
        $request = $app->request();
        $s_msg = $request->params('message');
        $s_env = $request->params('env');
        $s_uid = $request->params('token');
        $model = new MobileFeedbackModel();
        $model->createFeedbackItem($s_uid, $s_msg, $s_env);
        $app->success("Created new feedback entry.");
    });

    /**
     * Allows for submission of a new feedback entry via POST.
     */
    /*    $app->post('/feedbackdrop',  function () use ($app) {
        });
    */

    /**
     * Allows for retrieval of feedback entries.
     */
    /*   $app->get('/feedbackread/:id',  function ($id) use ($app) {
       });
   */

    /**
     * Initializes the feedback extension, i.e. creates the database tables.
     */
    $app->get('/feedbackinit',  function () use ($app) {
        $model = new MobileFeedbackModel();
        $model->createMobileFeedbackDatabaseTable();
        $app->success("Created new feedback database.");
    });

});
