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
 * Exception: TokenSettings($message, $restCode, $previous)
 *  This exception should be thrown, when the token-settings
 *  object is faulty, eg. has no valid salt-value.
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class TokenSettings extends Libs\RESTException { }
