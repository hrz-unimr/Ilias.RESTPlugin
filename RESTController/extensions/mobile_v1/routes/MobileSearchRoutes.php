<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;
use \RESTController\libs\RESTAuth as RESTAuth;


$app->group('v1/m',RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    $app->get('/search/',  function () use ($app) {
        $response = $app->request();

        try {
            $query = utf8_encode($request->getParameter('q'));
        } catch (\Exception $e) {
            $query = '';
        }

        $model = new MobileSearchModel();
        $searchResults = $model->performSearch($query);

        $app->success($searchResults);
    });

    $app->post('/search/', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        $response = $app->request();

        try {
            $query = utf8_encode($request->getParameter('q'));
        } catch (\Exception $e) {
            $query = '';
        }

        $model = new MobileSearchModel();
        $searchResults = $model->performSearch($query);

        $app->success($searchResults);
    });
});
