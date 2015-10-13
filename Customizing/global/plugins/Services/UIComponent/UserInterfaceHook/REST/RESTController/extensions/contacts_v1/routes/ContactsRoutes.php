<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\contacts_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;


$app->group('/v1', function () use ($app) {
    /**
     * Returns the personal ILIAS contacts for a user specified by id.
     */
    $app->get('/contacts/:id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $id || Libs\RESTLib::isAdminByUserId($authorizedUserId)) { // only the user or the admin is allowed to access the data
            try {
                $model = new ContactsModel();
                $data = $model->getMyContacts($id);

                $app->success($data);
            } catch (Libs\ReadFailed $e) {
                $app->halt(404, 'Error: Could not retrieve data for user '.$id.".", -15);
            }
        }
        else
            $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);

    });


    /**
     * Returns the personal ILIAS contacts of the authenticated user.
     */
    $app->get('/contacts', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $authorizedUserId =  Libs\RESTLib::getUserIdFromUserName($user);

        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $id = $authorizedUserId;

                $model = new ContactsModel();
                $data = $model->getMyContacts($id);
                $app->success($data);

        }
        else {
            $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);
        }
    });
});
