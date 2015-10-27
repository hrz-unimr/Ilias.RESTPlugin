<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\objects_v1;
use \RESTController\libs\RESTAuth as RESTAuth;


$app->get('/v1/object/:ref', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($ref) use ($app) {
    try {
        $model = new ObjectsModel();
        $result = $model->getObject($ref);

        $app->success($result);
    }
    catch(\Exception $e) {
        $app->halt(422, $e->getMessage());
    }
});
