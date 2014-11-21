<?php


$app->get('/v1/object/:ref', 'authenticateILIASAdminRole', function ($ref) use ($app) {

    $request = new ilRestRequest($app);
    $response = new ilRestResponse($app);
    $model = new ilObjectsModel();
    
    $model->getObject($ref, $resquest, $response);
    echo($response->toJSON());
    

});


?>
