<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;


/*
 * Mobile Search Routes
 */
 

$app->group('/m/v1', function () use ($app) {


    $app->get('/search/',  function () use ($app) {
        $request = new RESTRequest($app);
        $response = new RESTResponse($app);

        try {
            $query = utf8_encode($request->getParam('q'));
        } catch (\Exception $e) {
            $query = '';
        }

        $model = new MobileSearchModel();
        $searchResults = $model->performSearch($query);

        $response->addData('search_results', $searchResults);
        $response->setMessage('You have been searching for: "'.$query.'"');
        $response->send();
    });

    $app->post('/search/',  function () use ($app) {
        $request = new RESTRequest($app);
        $response = new RESTResponse($app);

        try {
            $query = utf8_encode($request->getParam('q'));
        } catch (\Exception $e) {
            $query = '';
        }

        $model = new MobileSearchModel();
        $searchResults = $model->performSearch($query);

        $response->addData('search_results', $searchResults);
        $response->setMessage('You have been searching for: "'.$query.'"');
        $response->send();
    });


});
