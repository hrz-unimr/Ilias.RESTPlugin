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
 * Exception: TokenInvalid($message, $restCode, $previous)
 *  This exception should be thrown, when
 *  the client trys to authenticate via token
 *  but the token can't be used for authentification,
 *  eg. it expired or is invalid.
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class TokenInvalid extends Libs\RESTException {
  // Error-Type used for redirection
  // See https://tools.ietf.org/html/rfc6749#section-5.2
  protected static $errorType = 'unauthorized_client';
}
