<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2\Exceptions;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Exception: Denied($message, $restCode, $previous)
 *  This exception should be thrown, when the request was denied, either by the user
 *  or because of other settings.
 *  Details: https://tools.ietf.org/html/rfc6749#section-4.1.2
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class Denied extends Libs\RESTException {
  // Error-Type used for redirection
  // See https://tools.ietf.org/html/rfc6749#section-5.2
  protected static $errorType = 'access_denied';
}
