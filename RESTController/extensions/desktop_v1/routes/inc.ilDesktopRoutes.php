<?php
/*
 * REST endpoints regarding the User Personal Desktop
 */
$app->group('/v1', function () use ($app) {
    $app->get('/desktop/items/:id',  function ($id) use ($app) {
        $env = $app->environment();
        $result = array();

        $model = new ilDesktopModel();
        $data = $model->getPersonalDesktopItems($id);
        $result['msg'] = "Personal desktop items for user ".$id;
        $result['items'] = $data;
        echo json_encode($result);
    });

    // proof of concept: DEL this route should be implemented as delete op
    $app->get('/desktop/remitem/:id',  function ($id) use ($app) {
        $result = array();
        $ref_id = 78;

        $model = new ilDesktopModel();
        $model->removeItemFromDesktop($id, $ref_id);
        $result['msg'] = "Removed ".$ref_id." from PD of user ".$id;
        //$result['items'] = $data;
        echo json_encode($result);
    });

    // proof of concept: ADD this route should be implemented as put op
    $app->get('/desktop/additem/:id',  function ($id) use ($app) {
        $result = array();
        $ref_id = 78;

        $model = new ilDesktopModel();
        $model->addItemToDesktop($id, $ref_id);
        $result['msg'] = "Item ".$ref_id." added to the PD of user ".$id;
        //$result['items'] = $data;
        echo json_encode($result);
    });


});
