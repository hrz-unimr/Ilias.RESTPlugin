<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\desktop_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;


$app->group('/v1', function () use ($app) {
    /**
     * Retrieves all items from the personal desktop of a user specified by its id.
     */
    $app->get('/desktop/overview/:id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth' , function ($id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $id || Libs\RESTLib::isAdminByUserId($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $model = new DesktopModel();
            $data = $model->getPersonalDesktopItems($id);

            $app->success($data);
        }
        else
            $app->halt(401, Libs\RESTLib::MSG_NO_ADMIN, Libs\RESTLib::ID_NO_ADMIN);
    });


    /**
     * Deletes an item specified by ref_id from the personal desktop of the user specified by $id.
     */
    $app->delete('/desktop/overview/:id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth',  function ($id) use ($app) {
        $request = $app->request();
        try {
            $ref_id = $request->params("ref_id");
            $model = new DesktopModel();
            $model->removeItemFromDesktop($id, $ref_id);

            $app->success();
        } catch (\Exception $e) {
            $app->halt(500, "Error: ".$e->getMessage(), -15);
        }
    });


    /**
     * Adds an item specified by ref_id to the users's desktop. The user must be the owner or at least has read access of the item.
     */
    $app->post('/desktop/overview/:id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth',  function ($id) use ($app) {
        $request = $app->request();
        try {
            $ref_id = $request->params("ref_id");
            $model = new DesktopModel();
            $model->addItemToDesktop($id, $ref_id);

            $app->success();
        } catch (\Exception $e) {
            $app->halt(500, "Error: ".$e->getMessage(), -15);
        }
    });


});
