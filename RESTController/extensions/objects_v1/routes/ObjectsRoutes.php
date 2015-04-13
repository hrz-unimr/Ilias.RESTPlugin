<?php


$app->get('/v1/object/:ref', 'authenticateILIASAdminRole', function ($ref) use ($app) {

    $request = new RESTRequest($app);
    $response = new RESTResponse($app);
    $model = new ObjectsModel();
    
    $model->getObject($ref, $resquest, $response);
    echo($response->toJSON());
    

});


?>
