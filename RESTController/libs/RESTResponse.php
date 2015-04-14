<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
//namespace RESTController\libs;
 
 
// Requires !!!
 
 
/**
 * This class eases the formatting of output and also ensures a standardized output schema of the API.
 * 2014 HRZ - Uni-Marburg
 */
class RESTResponse {
    public $_data = array();
    public $_msg = "";
    public $_code = "200";
    public $_format = "json";
    protected $app;

    public function RESTResponse($app) {
        $this->app = $app;
        $this->setHttpStatus(200);
    }

    /**
     * Adds data to the response object, creating or extending an array for $keyword.
     *
     * @param $keyword a string which describes an object in the resulting json
     * @param $data an array or a string which represents the data
     */
    public function addData($keyword, $data)
    {
        if (isset($this->_data[$keyword])) {
            $this->_data[$keyword][] = $data;
        } else {
            $this->setData($keyword, array($data));
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
     * Adds another string to the message part of the response.
     *
     * @param $message a string
     */
    public function addMessage($message)
    {
        if ($this->_msg == "") {
            $this->setMessage($message);
        } else {
            $this->setMessage($this->_msg.' '.$message);
        }
    }

    /**
     * Sets the code part of the JSON response.
     *
     * @param $code a string
     */
    public function setRESTCode($code)
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
        $this->setRestCode($statusCode);
    }

    /**
     * Sets the type of the output. Depending on the output format, the send method decides how the response message will be rendered.
     * Default setting: json.
     * @param $outputFormat - a string
     */
    public function setOutputFormat($outputFormat)
    {
        $this->_format = $outputFormat;
    }

    /**
     * Gets the currently selected type of the output format.
     */
    public function getOutputFormat()
    {
        return $this->_format;
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

    /**
     * Calls one of the output functions (toJSON, toXML, toRAW)
     * depending on the state of the internal variable "format".
     */
    public function send()
    {
        switch ($this->_format) {
            case "json": $this->toJSON(); break;
            default: $this->toJSON();
        }
    }
}
