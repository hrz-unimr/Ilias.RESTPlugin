<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Exceptions;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Exception: LoginFailed($message, $restCode, $previous)
 *  This exception should be thrown when
 *  the client does not provide the correct response_type
 *  value with his query.
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class GrantType extends Libs\RESTException { }
