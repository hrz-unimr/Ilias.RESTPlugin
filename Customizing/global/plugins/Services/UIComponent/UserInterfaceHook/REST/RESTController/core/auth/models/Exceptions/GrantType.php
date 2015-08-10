<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Exceptions;


/**
 * This exception should be thrown when
 * the client does not provide the correct response_type
 * value with his query.
 */
class GrantType extends \Exception {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\core\\auth\\Exceptions\\GrantType::ID';

    // Allow to reuse status message
    const MSG = 'Parameter "grant_type" needs to match "password", "client_credentials", "authorization_code" or "refresh_token".';


    /**
     * Constructor
     */
    public function __construct ($message, $code = 0, $previous = NULL) {
        parent::__construct ($message, $code, $previous);
    }
}
