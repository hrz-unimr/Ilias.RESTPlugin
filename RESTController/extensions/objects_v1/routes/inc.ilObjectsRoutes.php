<?php


$app->get('/v1/object/:ref', 'authenticateILIASAdminRole', function ($ref) use ($app) {

    $request = new ilRESTRequest($app);
    $response = new ilRESTResponse($app);
    $model = new ilObjectsModel();
    
    $model->getObject($ref, $resquest, $response);
    echo($response->toJSON());
    

});


?>
