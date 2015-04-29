<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\clients;


/**
 *
 */
class ClientsException extends \Exception {   
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */ 
    const _ID = "";

    
    /**
     *
     */
    protected int $id;

    
    /**
     *
     */
    public function __construct (string $message, int $code = 0, \Exception $previous = NULL) {
        parent::__construct ($message, code, $previous);
    }
    
    
    /**
     *
     */
    public function id() {
        return $this->id;
    }
    
    
    /**
     *
     */
    static public getDeleteException(string $message, int $id) {
        $exception = new ClientsException($message);
        $exception.id = $id;
        
        return $exception;
    }
}