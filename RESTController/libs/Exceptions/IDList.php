<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs\Exceptions;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Exception: IDList($message, $restCode, $data, $previous)
 *  This exception should be thrown (or caught) when a list of
 *  IDs given as a GET string cannot be parsed.
 *  This is (mainly) thrown by RESTRequest...
 *
 * Parameters:
 *  @See RESTException for parameter description
 */
class IDList extends Libs\RESTException {
  const STATUS=422;
}
