<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\desktop_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;

$app->group('/v1', function () use ($app) {

    /**
     * Retrieves all items from the personal desktop of the authenticated user.
     */
    $app->get('/desktop/overview', RESTAuth::checkAccess(RESTAuth::PERMISSION) , function () use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        $model = new DesktopModel();
        $data = $model->getPersonalDesktopItems($authorizedUserId);
        $resp = array('desktop'=>$data);
        $app->success($resp);
    });


    /**
     * Deletes an item specified by ref_id from the personal desktop of the authenticated user.
     */
    $app->delete('/desktop/overview', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function () use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();
        $request = $app->request();
        try {
            $ref_id = $request->getParameter("ref_id",null,true);
            $model = new DesktopModel();
            $model->removeItemFromDesktop($authorizedUserId, $ref_id);
            $app->success(array("msg"=>"Removed item with ref_id=".$ref_id." from desktop."));
        } catch (Libs\RESTException $e) {
            $app->halt(401, "Error: ".$e->getRESTMessage(), -15);
        }
    });


    /**
     * Adds an item specified by ref_id to the users's desktop. The user must be the owner or at least has read access of the item.
     */
    $app->post('/desktop/overview', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function () use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();
        $request = $app->request();
        try {
            $ref_id = $request->getParameter("ref_id",null,true);
            $model = new DesktopModel();
            $model->addItemToDesktop($authorizedUserId, $ref_id);
            $app->success(array("msg"=>"Added item with ref_id=".$ref_id." to the desktop."));
        } catch (Libs\RESTException $e) {
            $app->halt(401, "Error: ".$e->getRESTMessage(), -15);
        }
    });


});
