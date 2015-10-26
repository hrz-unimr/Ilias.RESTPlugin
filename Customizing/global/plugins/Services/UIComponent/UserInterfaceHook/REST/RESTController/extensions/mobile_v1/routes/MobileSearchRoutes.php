<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;
use \RESTController\libs\RESTAuthFactory as AuthFactory;


$app->group('v1/m',AuthFactory::checkAccess(AuthFactory::PERMISSION), function () use ($app) {
    $app->get('/search/',  function () use ($app) {
        $response = $app->request();

        try {
            $query = utf8_encode($request->params('q'));
        } catch (\Exception $e) {
            $query = '';
        }

        $model = new MobileSearchModel();
        $searchResults = $model->performSearch($query);

        $app->success($searchResults);
    });

    $app->post('/search/', AuthFactory::checkAccess(AuthFactory::PERMISSION), function () use ($app) {
        $response = $app->request();

        try {
            $query = utf8_encode($request->params('q'));
        } catch (\Exception $e) {
            $query = '';
        }

        $model = new MobileSearchModel();
        $searchResults = $model->performSearch($query);

        $app->success($searchResults);
    });
});
