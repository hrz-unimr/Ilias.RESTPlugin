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
class RESTException extends \Exception {   
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */ 
    const MISSING_PARAM_ID = "RESTController\libs\RESTException::MISSING_PARAM_ID";
    
    
    /**
     *
     */
    protected string paramName = "";
    
    
    /**
     *
     */
    public function __construct (string $message, int $code = 0, \Exception $previous = NULL) {
        parent::__construct ($message, code, $previous);
    }
    
    
    /**
     *
     */
    public function paramName() {
        return $this->paramName;
    }
    
    
    /**
     *
     */
    static public getWrongParamException(string $message, string $param) {
        $exception = new RESTException($message);
        $exception.paramName = $param;
        
        return $exception;
    }
}