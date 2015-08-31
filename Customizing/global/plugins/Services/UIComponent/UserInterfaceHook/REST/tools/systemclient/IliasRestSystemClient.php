<?php
include('httpful.phar');

/**
 * ILIAS Rest System Client
 *
 * This client can be used in conjunction with the ILIAS REST Plugin for administrative tasks.
 * HRZ-UMR 2015 (D. Schaefer)
 *
 * Changelog:
 * 2015-08 - v.0.1 Init
 */
class IliasRestSystemClient
{
    public $host = "";
    public $oauth2_endpoint = "";
    private $oauth2LoginData = array();
    private $token = "";
    private $headers = array();

    function __construct() {
        /* Read Config */
        $ini_array = parse_ini_file("restsystemclient.ini");
        $this->host = $ini_array['host'];
        $this->oauth2_endpoint = $ini_array['oauth2_endpoint'];

        $this->oauth2LoginData = array(
            'grant_type' => $ini_array['grant_type'],
            'ilias_client_id' => $ini_array['ilias_client_id'],
            'api_key' => $ini_array['api_key'],
            'username' => $ini_array['username'],
            'password' => $ini_array['password']
        );

        $this->retrieveBearerToken(); // aka login
    }

    /**
     * This internal function uses the specified OAuth 2 type and credentials
     * to log in to the ILIAS REST Plugin and retrieves a Bearer Token.
     *
     * It sets the header for further REST requests accordingly. This function
     * is called by the constructor of this client (automatically).
     */
    private function retrieveBearerToken() {
        /* Get Bearer Token */
        $uri = $this->host.$this->oauth2_endpoint;
        $response = \Httpful\Request::post($uri)->body(json_encode($this->oauth2LoginData))->sendsJson()->send();
        $this->token = $response->body->access_token;

        $this->headers = array(
            'Authorization' => 'Bearer '.$this->token
        );
    }

    /**
     * Performs a GET request.
     * Note: $route  and .
     * @param $route - must begin with a trailing slash
     * @return mixed json response
     */
    public function get($route) {
        $uri = $this->host.$route;
        $response = \Httpful\Request::get($uri)->addHeaders($this->headers)->send();
        $jr = $response->raw_body;
        return $jr;
    }

    /**
     * Performs a PUT request.
     * Note: $route  and .
     * @param $route - must begin with a trailing slash
     * @param $data is an associative array
     * @return mixed json response
     */
    public function put($route, $data) {
        $uri = $this->host.$route;
        $response = \Httpful\Request::put($uri)->addHeaders($this->headers)->sendsJson()->body(json_encode($data))->send();
        $jr = $response->raw_body;
        return $jr;
    }

    /**
     * Performs a POST request.
     * Note: $route  and .
     * @param $route - must begin with a trailing slash
     * @param $data is an associative array
     * @return mixed json response
     */
    public function post($route, $data) {
        $uri = $this->host.$route;
        $response = \Httpful\Request::post($uri)->addHeaders($this->headers)->sendsJson()->body(json_encode($data))->send();
        $jr = $response->raw_body;
        return $jr;
    }

    /**
     * Performs a DELETE request.
     * Note: $route  and .
     * @param $route - must begin with a trailing slash
     * @param $data is an associative array
     * @return mixed json response
     */
    public function delete($route, $data) {
        $uri = $this->host.$route;
        $response = \Httpful\Request::delete($uri)->addHeaders($this->headers)->sendsJson()->body(json_encode($data))->send();
        $jr = $response->raw_body;
        return $jr;
    }
}