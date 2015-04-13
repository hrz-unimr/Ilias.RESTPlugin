<?php
/*
 * Route definitions for the REST Calendar API
 */

$app->group('/v1', function () use ($app) {
    /**
     * Returns the calendar events of user specified by $id.
     */
    $app->get('/cal/events/:id', 'authenticate', function ($id) use ($app) {
        $env = $app->environment();
        $response = new RESTResponse($app);
        $authorizedUserId =  RESTLib::loginToUserId($env['user']);

        if ($authorizedUserId == $id || RESTLib::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            try {
                $model = new ilCalendarModel();
                $data = $model->getCalUpcomingEvents($id);
                $response->setMessage("Upcoming events for user " . $id . ".");
                $response->addData('items', $data);
            } catch (Exception $e) {
                $response->setRESTCode("-15");
                $response->setMessage('Error: Could not retrieve any events for user '.$id.".");
            }
        } else {
            $response->setRESTCode("-13");
            $response->setMessage('User has no RBAC permissions to access the data.');
        }
        $response->toJSON();
    });

    /**
     * Returns the ICAL Url of the desktop calendar of user specified by $id
     */
    $app->get('/cal/icalurl/:id', 'authenticate' , function ($id) use ($app) {
        $env = $app->environment();
        $response = new RESTResponse($app);
        $authorizedUserId =  RESTLib::loginToUserId($env['user']);
        if ($authorizedUserId == $id || RESTLib::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            try {
                $model = new ilCalendarModel();
                $data = $model->getIcalAdress($id);
                $response->setMessage("ICAL (ics) address for user " . $id . ".");
                $response->addData('icalurl', $data);
            } catch (Exception $e) {
                $response->setRESTCode("-15");
                $response->setMessage('Error: Could not retrieve ICAL url for user '.$id.".");
            }
        } else {
            $response->setRESTCode("-13");
            $response->setMessage('User has no RBAC permissions to access the data.');
        }
        $response->toJSON();
    });
});
