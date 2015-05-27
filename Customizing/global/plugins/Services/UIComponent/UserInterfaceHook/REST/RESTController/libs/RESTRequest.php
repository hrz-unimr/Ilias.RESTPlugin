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
    /*
     * Anforderungen:
     *  - params sollte body (JSON) und GET verstehen! [nur wenn content-type stimmt]
     *  - params muss THROW erlauben
     *
     * Notiz:
     *  - getAllHeaders() durch $app->request->headers->get('param-all'); ersetzten
     */
    protected $json_arr;


    /**
     *
     */
    public function getParam($param, $default = null, $throw = false) {
        $ret = $this->params($param);
        if($ret == null){
            $this->json_arr = $this->getBody();

            if ($this->json_arr != null and isset($this->json_arr[$param]))
                return $this->json_arr[$param];
            else if ($throw)
                throw new Exceptions\MissingParameter('Mandatory data is missing, parameter %paramName% not set.', $param);

            return $default;
        }
        else
            return $ret;
    }
}
