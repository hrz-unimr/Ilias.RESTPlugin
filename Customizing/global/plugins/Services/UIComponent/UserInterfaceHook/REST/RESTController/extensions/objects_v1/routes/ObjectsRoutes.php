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


$app->get('/v1/object/:ref', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($ref) use ($app) {
    try {
        $model = new ObjectsModel();
        $result = $model->getObject($ref);
        
        $app->success($result);
    }
    catch(\Exception $e) {
        $app->halt(422, $e->getMessage());
    }
});
