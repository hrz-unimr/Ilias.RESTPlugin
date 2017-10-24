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
 * Exception: ilObject($message, $restCode, $data, $previous)
 *  This exception should be thrown (or caught) when fetching
 *  and object via one of its ids (reference id or object id)
 *  did not return any match.
 *
 * Parameters:
 *  @See RESTException for parameter description
 */
class ilObject extends Libs\RESTException  {
  const STATUS=404;
}
