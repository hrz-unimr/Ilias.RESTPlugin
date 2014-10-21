<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

/**
 * Gets the calendar events of user specified by $id.
 */
$app->group('/v1', function () use ($app) {
    $app->get('/cal/events/:id',  function ($id) use ($app) {
        $app = \Slim\Slim::getInstance();
        $env = $app->environment();
        $result = array();

        $model = new ilCalendarModel();
        $data = $model->getCalUpcomingEvents($id);
        //var_dump($data);
        $result['msg'] = "Upcoming events for user ".$id;
        $result['events'] = $data;
        echo json_encode($result);
    });

    /**
     * Gets the ICAL Url for a user specified by id
     */
    $app->get('/cal/icalurl/:id',  function ($id) use ($app) {
        $app = \Slim\Slim::getInstance();
        $env = $app->environment();
        $result = array();

        $model = new ilCalendarModel();
        $data = $model->getIcalAdress($id);
        //var_dump($data);
        $result['msg'] = "ICAL (ics) address for user ".$id;
        $result['ical_url'] = $data;
        echo json_encode($result);
    });
});
