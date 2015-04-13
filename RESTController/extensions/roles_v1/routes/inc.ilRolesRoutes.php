<?php


$app->get('/v1/roles', 'authenticateILIASAdminRole', function () use ($app) {

    $request = new RESTRequest($app);
    $model = new ilRolesModel();
    
    $resp = new RESTResponse($app);
    $model->getAllRoles($request, $resp);
    echo($resp->toJSON());
    

});


?>
