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
 * Exception: UnauthorizedClient($message, $restCode, $previous)
 *  This exception should be thrown, when the client could not be authorized.
 *  Details: https://tools.ietf.org/html/rfc6749#section-4.1.2
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class UnauthorizedClient extends Libs\RESTException {
  // Error-Type used for redirection
  // See https://tools.ietf.org/html/rfc6749#section-5.2
  protected static $errorType = 'unauthorized_client';
}
