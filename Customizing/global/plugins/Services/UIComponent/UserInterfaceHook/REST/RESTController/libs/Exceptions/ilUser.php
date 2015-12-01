<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs\Exceptions;


/**
 * Exception: ilUser($message, $restCode, $data, $previous)
 *  This exception should be thrown (or caught) when fetching
 *  and an ILIAS user via his user-id or user-name  did not
 *  return any match.
 *
 * Parameters:
 *  @See RESTException for parameter description
 */
class ilUser extends RESTException { }
