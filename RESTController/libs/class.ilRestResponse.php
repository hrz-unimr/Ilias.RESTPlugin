<?php
/**
 * This class eases the formatting of output and also ensures a standardized output schema of the API.
 * 2014 HRZ - Uni-Marburg
 */
class ilRestResponse {
    public $_data = array();
    public $_msg = "";
    public $_code = "200";
    private $app;

    public function ilRestResponse($app) {
        $this->app = $app;
        $this->setHttpStatus(200);
    }

    /**
     * Adds data to the response object.
     *
     * @param $keyword a string which describes an object in the resulting json
     * @param $data an array or a string which represents the data
     */
    public function addData($keyword, $data)
    {
        if (isset($this->_data[$keyword])) {
            if (is_array($this->_data[$keyword])) {
                $this->_data[$keyword][] = $data;
            } else {
                $oldVal = $this->_data[$keyword];
                $this->_data[$keyword] = array($oldVal, $data);
            }
        } else {
            $this->setData($keyword, $data);
        }
    }

    /**
     * Adds data to the response object and overrides data if already described by the same keyword.
     *
     * @param $keyword a string which describes an object in the resulting json
     * @param $data an array or a string which represents the data
     */
    public function setData($keyword, $data)
    {
        $this->_data[$keyword] = $data;
    }


    /**
     * Sets the message part of the JSON response.
     *
     * @param $message a string
     */
    public function setMessage($message)
    {
        $this->_msg = $message;
    }

    /**
     * Sets the code part of the JSON response.
     *
     * @param $code a string
     */
    public function setRestCode($code)
    {
        $this->_code = $code;
    }

    /**
     * Sets a http header speficied by key and value.
     * E.g. ('Cache-Control', 'no-store') or ('Pragma', 'no-cache')
     */
    public function setHttpHeader($key, $value)
    {
        $this->app->response()->header($key, $value);
    }

    /**
     * Sets the HTTP status code.
     * By default, it is set to 200 (OK).
     */
    public function setHttpStatus($statusCode)
    {
        $this->app->response()->status($statusCode);
    }

    /**
     * Creates a response in json format.
     * The method automatically sets the correct Content-Type: ('Content-Type', 'application/json')
     * @return a string in JSON format.
     */
    public function toJSON()
    {
        $this->setHttpHeader('Content-Type', 'application/json');
        $response = array();
        $response['data'] = $this->_data;
        $response['msg'] =  $this->_msg;
        $response['code'] = $this->_code;
        echo json_encode($response);
    }
}