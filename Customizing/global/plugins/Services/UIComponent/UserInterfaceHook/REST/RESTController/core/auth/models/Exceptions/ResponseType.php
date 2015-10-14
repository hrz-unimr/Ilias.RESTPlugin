<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Exceptions;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\Exceptions as LibExceptions;


/**
 * This exception should be thrown when
 * the client does not provide the correct response_type
 * value with his query.
 */
class ResponseType extends LibExceptions\RESTException {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\core\\auth\\Exceptions\\ResponseType::ID';

    // Allow to reuse status message
    const MSG = 'Parameter "response_type" needs to match "code" or "token".';


    /**
     * Constructor
     */
    public function __construct ($message, $restCode = 0, $previous = NULL) {
        parent::__construct ($message, ($restCode == 0) ? self::ID : $restCode, $previous);
    }
}
