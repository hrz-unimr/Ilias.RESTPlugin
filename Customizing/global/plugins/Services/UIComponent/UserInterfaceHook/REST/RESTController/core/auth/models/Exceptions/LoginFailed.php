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
 * This exception should be thrown, when the
 * client provided enough login-information,
 * but can't be authentificated because his
 * data is wrong.
 */
class LoginFailed extends LibExceptions\RESTException {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\core\\auth\\Exceptions\\LoginFailed';


    /**
     * Constructor
     */
    public function __construct ($message, $restCode = 0, $previous = NULL) {
        parent::__construct ($message, ($restCode == 0) ? self::ID : $restCode, $previous);
    }
}
