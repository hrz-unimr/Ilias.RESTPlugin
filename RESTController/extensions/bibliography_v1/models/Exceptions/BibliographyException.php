<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\bibliography_v1\Exceptions;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;

class BibliographyException extends Libs\RESTException {
    protected static $errorType = 'server_error';
}
