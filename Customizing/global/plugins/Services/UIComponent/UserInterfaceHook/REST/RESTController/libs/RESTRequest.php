<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;

use
// Requires !!!


class RESTRequest extends \Slim\Http\Request {

    protected $app;
    protected $content_type;
    public $json_arr;
    protected $json_decoded;
    protected $accessToken;

    public function __construct ($app) {
        parent::__construct($app->environment());

        $this->app = $app;
        $this->slimReq = $this;
        $this->content_type = $this->headers()->get('Content-Type');
        $this->json_arr = null;
        $this->json_decoded = false;
    }

    /**
     * Tight wrapper around Slim's params() method.
     * If a parameter is not found, try to json-decode the request body
     * and look for the parameter there.

     * TODO Liefert Format-Einstellung-Body, RAW-Body, GET, HEAD
     * Einzelne Methoden um immer nur Format-Einstellung-Body, RAW-Body, JSON-Body, GET oder HEAD zu holen
     */
    public function getParam($param, $default = null, $throw = false) {
        $ret = $this->app->request()->params($param);
        if($ret == null){
            $this->decodeJson();

            if ($this->json_arr != null and isset($this->json_arr[$param]))
                return $this->json_arr[$param];
            else if ($throw)
                throw new Exceptions\MissingParameter('Mandatory data is missing, parameter %paramName% not set.', $param);

            return $default;
        }
        else
            return $ret;
    }

    /**
     * Try to json decode the request body, only once.
     * @return associative array if successful
     */
    protected function decodeJson() {
//        if ($this->content_type == 'application/json' and !$this->json_decoded) {
        if ( !$this->json_decoded ) { // try to decode regardless of content type
            $this->json_arr = json_decode($this->slimReq->getBody(), true);
            $this->json_decoded = true;
        }
    }

    /**
     * Get request as associative array.
     * Union of parameters (as provided by Slim) and json decoded body.
     */
    public function getObject() {
        $this->decodeJson();
        if($this->json_arr != null) {
            return array_merge($this->slimReq->params(), $this->json_arr);
        } else {
            throw new \Exception("No JSON data present");
        }
    }

    public function getRaw() {
        return $this->app->request()->getBody();
    }
}
