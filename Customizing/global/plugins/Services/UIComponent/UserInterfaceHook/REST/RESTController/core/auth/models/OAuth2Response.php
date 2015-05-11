<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;
// Requires <$app = \RESTController\RESTController::getInstance()>


/**
 * This class eases the formatting of output for the OAuth2 mechanisms.
 * 2015 HRZ - Uni-Marburg
 */
class Oauth2Response {
    public $_response = array();
    public $_format = "json";
    protected $app;

    public function __construct($app) {
        $this->app = $app;
        $this->setHttpStatus(200);
    }

    /**
     * Adds data to the response object and overrides data if already described by the same keyword.
     *
     * @param $keyword string which describes an object in the resulting json
     * @param $data string|array or a string which represents the data
     */
    public function setField($keyword, $data)
    {
        $this->_response[$keyword] = $data;
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
        foreach($this->_response as $key=>$value) {
            $response[$key] = $value;
        }
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
            default: $this->setHttpHeader('Content-Type', 'text/plain'); break;
        }
    }
}
