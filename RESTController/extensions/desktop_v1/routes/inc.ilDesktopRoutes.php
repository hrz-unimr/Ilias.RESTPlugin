<?php
/*
 * REST endpoints regarding the User Personal Desktop
 */
$app->group('/v1', function () use ($app) {
    /**
     * Retrieves all items from the personal desktop of a user specified by its id.
     */
    $app->get('/desktop/overview/:id', 'authenticate' , function ($id) use ($app) {
        $env = $app->environment();
        $authorizedUserId =  ilRestLib::loginToUserId($env['user']);

        $response = new ilRestResponse($app);
        if ($authorizedUserId == $id || ilRestLib::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            $model = new ilDesktopModel();
            $data = $model->getPersonalDesktopItems($id);
            $response->addData('items', $data);
            $response->setMessage("Personal desktop items for user ".$id.".");
        } else {
            $response->setRestCode("-13");
            $response->setMessage('User has no RBAC permissions to access the data.');
        }
        $response->toJSON();
    });

    /**
     * Deletes an item specified by ref_id from the personal desktop of the user specified by $id.
     */
    $app->delete('/desktop/overview/:id', 'authenticate',  function ($id) use ($app) {
        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);
        try {
            $ref_id = $request->getParam("ref_id");
            $model = new ilDesktopModel();
            $model->removeItemFromDesktop($id, $ref_id);
            $response->setMessage("Item ".$ref_id." removed successfully from the users PD overview.");
        } catch (Exception $e) {
            $response->setRestCode("-13");
            $response->setMessage("Error: ".$e);
        }
        $response->toJSON();
    });

    /**
     * Adds an item specified by ref_id to the users's desktop. The user must be the owner or at least has read access of the item.
     */
    $app->post('/desktop/overview/:id', 'authenticate',  function ($id) use ($app) {
        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);
        try {
            $ref_id = $request->getParam("ref_id");
            $model = new ilDesktopModel();
            $model->addItemToDesktop($id, $ref_id);
            $response->setMessage("Item ".$ref_id." added successfully to the users PD overview.");
        } catch (Exception $e) {
            $response->setRestCode("-13");
            $response->setMessage("Error: ".$e);
        }
        $response->toJSON();
    });


});
