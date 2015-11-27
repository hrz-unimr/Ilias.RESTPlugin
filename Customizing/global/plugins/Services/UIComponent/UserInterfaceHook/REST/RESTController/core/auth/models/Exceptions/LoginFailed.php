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
 *  This exception should be thrown, when the
 *  client provided enough login-information,
 *  but can't be authentificated because his
 *  data is wrong.
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class LoginFailed extends Libs\RESTException { }
