<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\docs_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


class DocumentationModel extends Libs\RESTModel
{

    public $docs = array();

    function __construct() {
        $this->docs['get/v1/example'] = array(
            'route'         => '/v1/example',
            'verb'          => 'GET',
            'group'         => '/v1/example',
            'description'   => 'Example description',
            'input'         => '{"param1":"value1","param2":"value2"}'
        );

        $this->docs['get/v1/example2'] = array(
            'route'         => '/v1/example2',
            'verb'          => 'POST',
            'group'         => '/v1/example2',
            'description'   => 'Example description 2',
            'input'         => '{"param1":"value1","param2":"value2"}'
        );

    }

    /**
     * Creates an internal (single-) key representation.
     * @param $route
     * @param $verb
     * @return string
     */
    private function getInternalKey($route, $verb)
    {
        $combinedKey = '';
        if (strlen($route)>0) {
            $loRoute = strtolower($route);
            $loVerb = strtolower($verb);
            if ($loRoute[0] == '/') {
                $combinedKey = $loVerb.$loRoute;
            } else {
                $combinedKey = $loVerb.'/'.$loRoute;
            }
        }
        return $combinedKey;
    }

    /**
     * Returns the documentation of a particular (route, verb) pair.
     * @param $route
     * @param $verb
     * @return array
     */
    function getDocumentation($route, $verb)
    {
        $result = array();
        $result [] = $this->docs[$this->getInternalKey($route, $verb)];
        return $result;
    }

    /**
     * Returns the documentation of all available (route, verb) pairs
     * @return array
     */
    function getCompleteApiDocumentation()
    {
        $result = array();
        foreach ($this->docs as $key => $value) {
            $result[] = $value;
        }
        return $result;
    }
    
}
