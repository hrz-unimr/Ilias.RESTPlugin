<?php


$app->get('/v1/roles', 'authenticateILIASAdminRole', function () use ($app) {

    $request = new ilRESTRequest($app);
    $model = new ilRolesModel();
    
    $resp = new ilRESTResponse($app);
    $model->getAllRoles($request, $resp);
    echo($resp->toJSON());
    

});


?>
