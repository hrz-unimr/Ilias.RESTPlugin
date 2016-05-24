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
        // admin_v1
        $this->docs['get/v1/admin/files/:id'] = array(
            'route'         => '/v1/admin/files/:id',
            'verb'          => 'GET',
            'group'         => '/v1/admin',
            'description'   => 'Admin Route. Downloads a file with a given id (ref_id). If parameter is set to
                                true then only descriptions about a file in json format are provided.',
            'parameters'         => '{"meta_data":"true"}'
        );

        $this->docs['get/v1/admin/describe/:id'] = array(
            'route'         => '/v1/admin/describe/:id',
            'verb'          => 'GET',
            'group'         => '/v1/admin',
            'description'   => 'Returns a description of an object or user specified by its obj_id, ref_id, usr_id or file_id. Supported types: obj_id, ref_id, usr_id and file_id.',
            'parameters'         => '{"id_type":"ref_id"}'
        );

        $this->docs['get/v1/admin//desktop/overview/:id'] = array(
            'route'         => '/v1/admin//desktop/overview/:id',
            'verb'          => 'GET',
            'group'         => '/v1/admin',
            'description'   => 'Retrieves all items from the personal desktop of a user specified by its id.',
            'parameters'         => '{}'
        );

        $this->docs['delete/v1/admin//desktop/overview/:id'] = array(
            'route'         => '/v1/admin//desktop/overview/:id',
            'verb'          => 'DELETE',
            'group'         => '/v1/admin',
            'description'   => 'Deletes an item specified by ref_id from the personal desktop of the user specified by $id.',
            'parameters'         => '{"ref_id":"ID"}'
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
