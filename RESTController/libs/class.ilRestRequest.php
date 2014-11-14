<?php


class ilRestRequest {

    var $app;
    var $content_type;
    var $json_arr;
    var $json_decoded;

    public function ilRestRequest($app) {
        $this->app = $app;
        $this->slimReq = $app->request();
        $this->content_type = $app->request()->headers()->get('Content-Type');
        $this->json_arr = null;
        $this->json_decoded = false;
    }

    /**
     * Tight wrapper around Slim's params() method.
     * If a parameter is not found, try to json-decode the request body
     * and look for the parameter there.
     */
    public function getParam($param) {
        if( ($ret = $this->app->request()->params($param)) == null){
            $this->decodeJson();

            if ($this->json_arr != null and isset($this->json_arr[$param])) {
                    $ret = $this->json_arr[$param];
            } else {
                throw new Exception("Parameter $param not present.");
            }
            
        }
        return $ret;
    }

    /**
     * Try to json decode the request body, only once.
     * @return associative array if successful
     */
    private function decodeJson() {
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
            throw new Exception("No JSON data present");
        }
    }

    public function getRaw() {
        return $this->app->request()->getBody();
    }
}
?>

