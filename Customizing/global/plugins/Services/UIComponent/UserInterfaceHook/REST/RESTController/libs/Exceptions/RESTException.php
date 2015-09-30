<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs\Exceptions;


/**
 *
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
