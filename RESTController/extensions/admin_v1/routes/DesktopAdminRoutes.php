<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\extensions\desktop_v1 as Desktop;

$app->group('/v1/admin', function () use ($app) {
    /**
     * Retrieves all items from the personal desktop of a user specified by its id.
     */
    $app->get('/desktop/overview/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION) , function ($id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $id || Libs\RESTilias::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $model = new Desktop\DesktopModel();
            $data = $model->getPersonalDesktopItems($id);
            $ret = array("content" => $data);
            $app->success($ret);
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
    });


    /**
     * Deletes an item specified by ref_id from the personal desktop of the user specified by $id.
     */
    $app->delete('/desktop/overview/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function ($id) use ($app) {
        $request = $app->request();
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        //try {
        if ($authorizedUserId == $id || Libs\RESTilias::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $ref_id = $request->getParameter("ref_id",null,true);
            $model = new Desktop\DesktopModel();
            $model->removeItemFromDesktop($id, $ref_id);
            $app->success(array("msg"=>"Removed item with ref_id=".$ref_id." from desktop of user $id."));
        }

    });


    /**
     * Adds an item specified by ref_id to the users's desktop. The user must be the owner or at least has read access of the item.
     */
    $app->post('/desktop/overview/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function ($id) use ($app) {
        $request = $app->request();
        try {
            $ref_id = $request->getParameter("ref_id");
            $model = new Desktop\DesktopModel();
            $model->addItemToDesktop($id, $ref_id);

            $app->success(array("msg"=>"Added item with ref_id=".$ref_id." to the desktop."));
        } catch (\Exception $e) {
            $app->halt(500, "Error: ".$e->getMessage(), -15);
        }
    });





});
