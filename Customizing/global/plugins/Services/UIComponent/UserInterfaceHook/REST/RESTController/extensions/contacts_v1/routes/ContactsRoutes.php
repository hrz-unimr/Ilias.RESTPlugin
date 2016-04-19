<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\contacts_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;


$app->group('/v1', function () use ($app) {
    /**
     * Returns the personal ILIAS contacts for a user specified by id.
     */
    $app->get('/contacts/:id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $id || Libs\RESTilias::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            try {
                $model = new ContactsModel();
                $data = $model->getMyContacts($id);

                $app->success($data);
            } catch (Libs\ReadFailed $e) {
                $app->halt(404, 'Error: Could not retrieve data for user '.$id.".", -15);
            }
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);

    });


    /**
     * Returns the personal ILIAS contacts of the authenticated user.
     */
    $app->get('/contacts', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
       // $accessToken = Auth\Util::getAccessToken();
        $accessToken = $app->request->getToken();


        $authorizedUserId =  $accessToken->getUserId();

        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $id = $authorizedUserId;

                $model = new ContactsModel();
                $data = $model->getMyContacts($id);
                $app->success($data);

        }
        else {
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
        }
    });
});
