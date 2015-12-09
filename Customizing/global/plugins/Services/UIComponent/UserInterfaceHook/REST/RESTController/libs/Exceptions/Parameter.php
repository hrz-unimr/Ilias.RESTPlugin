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
 * Exception: Parameter($message, $restCode, $data, $previous)
 *  This exception should be thrown (or caught) when a rest request
 *  cannot be fullfilled because of missing request-parameters.
 *  This is (mainly) thrown by RESTRequest...
 *
 * Parameters:
 *  @See RESTException for parameter description
 */
class Parameter extends Libs\RESTException {
  // Error-Type used for redirection (only usefull for oauth2)
  protected static $errorType = 'invalid_request';
}
