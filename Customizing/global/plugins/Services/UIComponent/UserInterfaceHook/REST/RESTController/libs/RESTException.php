<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 *
 *  $message <String> - A human-readable message about the cause of the exception
 *  $restCode <String> - [Optional] A machine-readable identifier for the cause of the exception
 *  $previous <Exception> - [Optional] Attach previous exception that caused this exception
 */
class RESTException extends \Exception {
  //
  protected $restCode = 0;


  /**
   *
   */
  public function __construct ($message, $restCode = 0, $previous = NULL) {
    parent::__construct ($message, 0, $previous);
    $this->restCode = $restCode;
  }


  /**
   *
   */
  public function getRestCode() {
    return $this->restCode;
  }
}
