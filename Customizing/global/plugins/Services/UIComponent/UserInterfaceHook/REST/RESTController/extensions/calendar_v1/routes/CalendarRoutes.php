<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\calendar_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;


$app->group('/v1', function () use ($app) {
    /**
     * Returns the calendar events of a user specified by its user_id.
     */
    $app->get('/cal/events/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($id) use ($app) {
        $accessToken = $app->request->getToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $id || Libs\RESTilias::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $model = new CalendarModel();
            $data = $model->getCalUpcomingEvents($id);
            $app->success($data);
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
    });


    /**
     * Returns the ICAL Url of the desktop calendar of a user specified by its user_id.
     */
    $app->get('/cal/icalurl/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION) , function ($id) use ($app) {
        $accessToken = $app->request->getToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $id || Libs\RESTilias::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $model = new CalendarModel();
            $data = $model->getIcalAdress($id);

            $app->success($data);
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
    });


    /**
     * Returns the calendar events of the authenticated user.
     */
    $app->get('/cal/events', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        $accessToken = $app->request->getToken();
        $user = $accessToken->getUserName();
        $authorizedUserId =  Libs\RESTilias::getUserName($user);

        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $id = $authorizedUserId;
            $model = new CalendarModel();
            $data = $model->getCalUpcomingEvents($id);

            $app->success($data);
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
    });


    /**
     * Returns the ICAL Url of the desktop calendar of the authenticated user.
     */
    $app->get('/cal/icalurl', RESTAuth::checkAccess(RESTAuth::PERMISSION) , function () use ($app) {
        $accessToken = $app->request->getToken();
        $user = $accessToken->getUserName();
        $authorizedUserId =  Libs\RESTilias::getUserName($user);

        if ($authorizedUserId > -1 ) { // only the user or the admin is allowed to access the data
            $id = $authorizedUserId;
            $model = new CalendarModel();
            $data = $model->getIcalAdress($id);

            $app->success($data);
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
    });
});
