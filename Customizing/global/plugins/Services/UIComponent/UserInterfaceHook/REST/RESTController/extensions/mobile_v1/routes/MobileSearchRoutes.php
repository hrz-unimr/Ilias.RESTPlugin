<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/*
 * Mobile Search Routes
 */


$app->group('v1/m', function () use ($app) {
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

    $app->post('/search/',  function () use ($app) {
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
