<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\desktop_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;


/*
 * REST endpoints regarding the User Personal Desktop
 */
$app->group('/v1', function () use ($app) {
    /**
     * Retrieves all items from the personal desktop of a user specified by its id.
     */
    $app->get('/desktop/overview/:id', '\RESTController\libs\AuthMiddleware::authenticate' , function ($id) use ($app) {
        $env = $app->environment();
        $authorizedUserId =  RESTLib::loginToUserId($env['user']);

        $response = new RESTResponse($app);
        if ($authorizedUserId == $id || RESTLib::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $model = new DesktopModel();
            $data = $model->getPersonalDesktopItems($id);
            $response->addData('items', $data);
            $response->setMessage("Personal desktop items for user ".$id.".");
        } else {
            $response->setRESTCode("-13");
            $response->setMessage('User has no RBAC permissions to access the data.');
        }
        $response->toJSON();
    });

    /**
     * Deletes an item specified by ref_id from the personal desktop of the user specified by $id.
     */
    $app->delete('/desktop/overview/:id', '\RESTController\libs\AuthMiddleware::authenticate',  function ($id) use ($app) {
        $request = new RESTRequest($app);
        $response = new RESTResponse($app);
        try {
            $ref_id = $request->getParam("ref_id");
            $model = new DesktopModel();
            $model->removeItemFromDesktop($id, $ref_id);
            $response->setMessage("Item ".$ref_id." removed successfully from the users PD overview.");
        } catch (\Exception $e) {
            $response->setRESTCode("-13");
            $response->setMessage("Error: ".$e);
        }
        $response->toJSON();
    });

    /**
     * Adds an item specified by ref_id to the users's desktop. The user must be the owner or at least has read access of the item.
     */
    $app->post('/desktop/overview/:id', '\RESTController\libs\AuthMiddleware::authenticate',  function ($id) use ($app) {
        $request = new RESTRequest($app);
        $response = new RESTResponse($app);
        try {
            $ref_id = $request->getParam("ref_id");
            $model = new DesktopModel();
            $model->addItemToDesktop($id, $ref_id);
            $response->setMessage("Item ".$ref_id." added successfully to the users PD overview.");
        } catch (\Exception $e) {
            $response->setRESTCode("-13");
            $response->setMessage("Error: ".$e);
        }
        $response->toJSON();
    });


});
