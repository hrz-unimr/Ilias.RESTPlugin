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
 * Exception: RBAC($message, $restCode, $data, $previous)
 *  This exception should be thrown (or caught) when an
 *  oparation can not be executed because it is not permitted
 *  to do so by the RBAC-System.
 *
 * Parameters:
 *  @See RESTException for parameter description
 */
class RBAC extends Libs\RESTException {
  const STATUS=403;
}
