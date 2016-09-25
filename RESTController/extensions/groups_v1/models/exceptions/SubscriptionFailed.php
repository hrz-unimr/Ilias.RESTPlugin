<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Exceptions;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\Exceptions as LibExceptions;


/**
 * This exception should be thrown, when
 * the subscription to a course cannot be accomplished.
 */
class SubscriptionFailed extends LibExceptions {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\extensions\\groups_v1\\Exceptions\\SubscriptionFailed';


    /**
     * Constructor
     */
    public function __construct ($message, $restCode = 0, $previous = NULL) {
        parent::__construct ($message, ($restCode == 0) ? self::ID : $restCode, $previous);
    }
}
