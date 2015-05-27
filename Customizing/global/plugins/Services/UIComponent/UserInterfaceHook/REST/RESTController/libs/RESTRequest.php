<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;


/**
 *
 */
class RESTRequest extends \Slim\Http\Request {
    /**
     *
     */
    public function getParam($param, $default = null, $throw = false) {
        $ret = $this->params($param);
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
     */
    protected function decodeJson() {
        //if ($this->content_type == 'application/json' and !$this->json_decoded) {
        if ( !$this->json_decoded ) { // try to decode regardless of content type
            $this->json_arr = json_decode($this->slimReq->getBody(), true);
            $this->json_decoded = true;
        }
    }
}



/**
 * Tight wrapper around Slim's params() method.
 * If a parameter is not found, try to json-decode the request body
 * and look for the parameter there.

 * TODO Liefert Format-Einstellung-Body, RAW-Body, GET, HEAD
 * Einzelne Methoden um immer nur Format-Einstellung-Body, RAW-Body, JSON-Body, GET oder HEAD zu holen
 */
