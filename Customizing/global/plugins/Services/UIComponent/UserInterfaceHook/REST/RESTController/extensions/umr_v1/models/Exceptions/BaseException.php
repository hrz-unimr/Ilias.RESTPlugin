<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1\Exceptions;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 */
class BaseException extends Libs\RESTException {
  // Used to store additional data for this exception
  protected $data = null;


  // Add $data parameter to constructor
  public function __construct ($message, $restCode = 0, $data = null, $previous = NULL) {
    parent::__construct($message, $restCode, $previous);

    $this->data = $data;
  }


  // Fetch data for this exception
  public function getData() { return $this->data; }
}
