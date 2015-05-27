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
     * Anforderungen
     *  - setFormat() -> RAW, JSON (default)
     *  - write($body, $replace = false) ODER finalize() überschreiben
     *  - disableCache() function
     *  - standard formate/codes definieren
     */


    /**
     *
     */
    public function noCache() {
        $this->headers->replace(array(
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires ' => 0
        ));
    }

    // WWW-Authenticate: OAuth realm="http://server.example.com/"
}
