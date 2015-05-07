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
class SaveFailed extends \Exception {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const DELETE_FAILED_ID = "RESTController\core\clients\ClientsModel::DELETE_FAILED_ID";
    const POST_FAILED_ID = "RESTController\core\clients\ClientsModel::POST_FAILED_ID";
    const PUT_FAILED_ID = "RESTController\core\clients\ClientsModel::PUT_FAILED_ID";


    /**
     * Stores parameter name for the problematic parameter
     */
    protected $paramName;


    /**
     * Constructor
     *  Don't set member variables, since this is a Exception that handles multiple issues
     */
    public function __construct ($message, $code = 0, $previous = NULL) {
        parent::__construct ($message, $code, $previous);
    }


    /**
     * Get name of parameter that caused is causing problems during PUT
     */
    public function paramName() {
        return $this->paramName;
    }


    /**
     * Creates a new PUT exception, by creating a new SaveFailed exception
     * and storing the parameter name.
     */
    public static function getPutException($message, $paramName, $code = 0, $previous = NULL) {
        $exception = new SaveFailed($message, $code, $previous);
        $exception->paramName = $paramName;

        return $exception;
    }
}
