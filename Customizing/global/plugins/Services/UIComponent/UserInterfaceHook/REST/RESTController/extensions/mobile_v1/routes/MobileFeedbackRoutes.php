<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

/**
 * The feedback endpoints allow for sending information regarding a mobile application.
 * E.g. user-feedback, debug-messages from the app
 */
$app->group('v1/m', function () use ($app) {

    /**
     * Allows for submission of a new feedback entry via GET.
     */
    $app->get('/feedbackdrop/',  function () use ($app) {

    });

    /**
     * Allows for submission of a new feedback entry via POST.
     */
    $app->post('/feedbackdrop/',  function () use ($app) {
    });

    /**
    * Allows for retrieval of feedback entries.
    */
    $app->get('/feedbackread/',  function () use ($app) {
    });

    /**
     * Initializes the feedback extension, i.e. creates the database tables.
     */
    $app->get('/feedbackinit/',  function () use ($app) {
    });
});
