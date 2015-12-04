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
 * Exception: Authorize($message, $restCode, $previous)
 *  This exception should be thrown, when the resource-owner
 *  or client authorization failed during one of the
 *  Authorize routes.
 *
 * Parameters:
 *  @See Libs\RESTException for parameter description
 */
class Authorize extends Libs\RESTException { }
