<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;


/**
 * 400 bad request (wrong syntax)
 * 422 Unprocessable Entity (correct syntax, wrong/missing data)
 * 401 not auth. (needs token)
 * 404 not found (no route, wrog URI)
 * 500 server fault (eg. sql-query failed)
 */


/**
 *
 */
class RESTResponse extends \Slim\Http\Response {
    /**
     *
     */
    public function noCache($reset = false) {
        if ($reset)
            $headers = array(
                'Cache-Control' => null,
                'Pragma' => null,
                'Expires' => null
            );
        else
            $headers = array(
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires ' => 0
            );

        $this->headers->replace($headers);
    }

    // WWW-Authenticate: OAuth realm="http://server.example.com/"
}
