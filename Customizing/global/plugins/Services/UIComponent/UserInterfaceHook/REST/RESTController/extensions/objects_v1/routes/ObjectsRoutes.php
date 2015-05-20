<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\objects_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


$app->get('/v1/object/:ref', '\RESTController\libs\OAuth2Middleware::TokenRouteAuthILIASAdminRole', function ($ref) use ($app) {

    $request = new RESTRequest($app);
    $response = new RESTResponse($app);
    $model = new ObjectsModel();

    $model->getObject($ref, $resquest, $response);
    echo($response->toJSON());


});


?>
