<?php
/*
 * ILIAS Search
 */

$app->group('/m/v1', function () use ($app) {

    $app->get('/search/', function () use ($app) {
        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);

        try {
            $query = $request->getParam('q');
        } catch (Exception $e) {
            $query = '';
        }

        // Using anonymous function PHP 5.3.0>=
        spl_autoload_register(function($class){
            if (file_exists($_SERVER['DOCUMENT_ROOT'].REST_PLUGIN_DIR.'/RESTController/extensions/search_m_v1/addon/' . $class . '.php')) {
                require_once($_SERVER['DOCUMENT_ROOT'].REST_PLUGIN_DIR.'/RESTController/extensions/search_m_v1/addon/' . $class . '.php');
            }
        });

        // Using elastica 1.3.4 (corresponding to elastic search 1.3.4)
        $elasticaClient = new \Elastica\Client();
        $esQuery = '{
            "query": {
                "fuzzy_like_this" : {
                    "fields" : ["title"],
                    "like_text" : "'.$query.'",
                    "max_query_terms" : 25
                }
            }
        }';
        $path = 'jdbc' . '/_search';

        $esResponse = $elasticaClient->request($path, \Elastica\Request::POST, $esQuery);
        $esResponseArray = $esResponse->getData();
        $searchResults = array();
        foreach ($esResponseArray['hits']['hits'] as $hit) {
            $searchResults[] = array('obj_id' => $hit['_source']['obj_id'],
                                     'type' => $hit['_source']['type'],
                                     'title' => $hit['_source']['title'],
                                     'age' => $hit['_source']['ageindays'],
                                     'score' => $hit['_score']);
        }

        $response->addData('search_results', $searchResults);
        $response->setMessage('You have been searching for: "'.$query.'"');
        $response->send();
    });

});
