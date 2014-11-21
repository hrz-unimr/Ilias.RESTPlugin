<?php


$app->get('/v1/roles', 'authenticateILIASAdminRole', function () use ($app) {

    $request = new ilRestRequest($app);
    $model = new ilRolesModel();
    
    $resp = new ilRestResponse($app);
    $model->getAllRoles($request, $resp);
    echo($resp->toJSON());
    

});


?>
