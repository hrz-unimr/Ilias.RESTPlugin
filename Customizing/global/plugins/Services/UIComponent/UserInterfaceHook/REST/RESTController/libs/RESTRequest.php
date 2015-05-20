<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;


// Requires !!!


class RESTRequest extends \Slim\Http\Request {

    protected $app;
    protected $content_type;
    public $json_arr;
    protected $json_decoded;

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


    /**
     *
     */
    public function fetchAccessToken() {
        /*
        // Authentication by client certificate
        // (see: http://cweiske.de/tagebuch/ssl-client-certificates.htm)
        $client = ($_SERVER['SSL_CLIENT_VERIFY'] && $_SERVER['SSL_CLIENT_S_DN_CN'] && $_SERVER['SSL_CLIENT_I_DN_O']) ? $_SERVER['SSL_CLIENT_S_DN_CN'] : NULL;
        $secret = NULL;
        if ($client) {
            // ToDo: no secret is needed, its just the organisation name
            $secret = $_SERVER['SSL_CLIENT_I_DN_O'];
            $ret = Auth\Util::checkClientCredentials($client, $secret);

            // Stops everything and returns 401 response
            if (!$ret)
                $app->halt(401, self::MSG_INVALID_SSL, self::ID_INVALID_SSL);

            // Setup slim environment
            $env = $app->environment();
            $env['client_id'] = $client;
        }
        */


        // Fetch stored token
        $env = $this->app->environment();
        if ($env['accessToken'])
            return $env['accessToken'];

        // Fetch token from body GET/POST (json or plain)
        $request = $this->app->request();
        $tokenString = $request->getParam('token');

        // Fetch access_token from GET/POST (json or plain)
        if (is_null($tokenString))
            $tokenString = $request->getParam('access_token');

        // Fetch token from request header
        if (is_null($tokenString)) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'];

            // Found Authorization header?
            if ($authHeader != null) {
                $a_auth = explode(' ', $authHeader);
                $tokenString = $a_auth[1];        // With "Bearer"-Prefix
                if ($tokenString == null)
                    $tokenString = $a_auth[0];    // Without "Bearer"-Prefix
            }
        }

        // Decode token
        if (isset($tokenString))
            $accessToken = TokenLib::deserializeToken($tokenString);
        return $token;
    }
}
