<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * HTTP-Codes:
 * 400 bad request (wrong syntax)
 * 422 Unprocessable Entity (correct syntax, wrong/missing data)
 * 401 not auth. (needs token)
 * 404 not found (no route, wrog URI)
 * 500 server fault (eg. sql-query failed)
 *
 * REST-Codes:
 */


/**
 *
 */
class RESTResponse extends \Slim\Http\Response {
    protected $format;


    public function __construct($body = '', $status = 200, $headers = array()) {
        parent::__construct($body, $status, $headers);

        $this->setFormat('JSON');
    }


    public function setFormat($format) {
        $body = $this->getBody();
        $this->format = strtoupper($format);
        $this->setBody($body);
    }


    public function getFormat() {
        return $this->format;
    }


    public function getBody() {
        $body = parent::getBody();

        switch($this->format) {
            default:
            case 'JSON':
                return json_decode($body, true);
                break;
            case 'RAW':
                return $body;
                break;
        }

    }


    public function body($body = null) {
        if ($body)
            return parent::body($body);
        else
            return $this->getBody();
    }


    public function write($body, $replace = false) {
        if (isset($body) && $body != '') {
            switch($this->format) {
                default:
                case 'JSON':
                    $body_ = json_encode($body);

                    // If $body is a non-assoc array (with depth 1), json_encode produces a json array instead of an object
                    // Issue: Angular requires top JSON element to be an object
                    if (substr($body_, 0, 1) == '[' && substr($body_, -1, 1) == ']')
                      $body = json_encode($body, JSON_FORCE_OBJECT);
                    else
                      $body = $body_;

                    break;
                case 'RAW':
                    break;
            }

            parent::write($body, false);
        }
    }


    public function finalize()  {
        list($status, $headers, $body) = parent::finalize();

        switch($this->format) {
            default:
            case 'JSON':
                $headers->set('Content-Type', 'application/json');
                break;
            case 'RAW':
                $headers->set('Content-Type', 'text/plain');
                break;
        }

        if ($status == 401)
            $headers->set('WWW-Authenticate', 'Bearer realm="'.$_SERVER['SERVER_NAME'].'"');

        header_remove('Set-Cookie');
        return array($status, $headers, $body);
    }


    /**
     *
     */
    public function disableCache($reset = false) {
        if ($reset) {
            $this->headers->remove('Cache-Control');
            $this->headers->remove('Pragma');
            $this->headers->remove('Expires');
        }
        else
            $this->headers->replace(array(
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires ' => 0
            ));
    }
}
