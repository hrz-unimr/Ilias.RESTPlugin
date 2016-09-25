<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;


class MobileSearchModel extends Libs\RESTModel
{
    /**
     * This method triggers an 'elastic search' on basis of repository titles by using the php client
     * elastica installed under 'addon'.
     * @param $query - a string
     * @return array $searchResults - an associative reaults of hits
     */
    public function performSearch($query) {

        // Using anonymous function PHP 5.3.0>=
        spl_autoload_register(function($class){
            if (file_exists($_SERVER['DOCUMENT_ROOT'].REST_PLUGIN_DIR.'/RESTController/extensions/mobile_v1/addon/' . $class . '.php')) {
                require_once($_SERVER['DOCUMENT_ROOT'].REST_PLUGIN_DIR.'/RESTController/extensions/mobile_v1/addon/' . $class . '.php');
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
        return $searchResults;
    }

}
