<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\clients\Exceptions;


/**
 *
 */
class PutFailed extends \Exception {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\core\\clients\\Exceptions\\PutFailed';


    /**
     * Stores parameter name for the problematic parameter
     */
    protected $paramName;


    /**
     * Constructor
     */
    public function __construct ($message, $paramName, $code = 0, $previous = NULL) {
        parent::__construct ($message, $code, $previous);
        $exception->paramName = $paramName;
    }


    /**
     * Get name of parameter that caused is causing problems during PUT
     */
    public function paramName() {
        return $this->paramName;
    }
}
