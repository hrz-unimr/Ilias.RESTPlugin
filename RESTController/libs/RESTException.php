<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;


/**
 *
 */
class RESTRequest extends \Exception {
    protected string paramName = "";
    
    /**
     *
     */
    public function __construct (string $message, int $code = 0, \Exception $previous = NULL) {
        parent::__construct ($message, code, $previous);
    }
    
    
    public function paramName() {
        return $this->paramName;
    }
    
    
    /**
     *
     */
    static public getWrongParamException(string $message, string $param) {
        $exception = new RESTRequest($message);
        $exception.paramName = $param;
        
        return $exception;
    }
}