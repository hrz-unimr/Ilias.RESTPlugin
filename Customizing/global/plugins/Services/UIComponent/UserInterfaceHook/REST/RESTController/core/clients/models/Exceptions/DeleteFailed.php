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
class DeleteFailed extends \Exception {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\core\\clients\\Exceptions\\DeleteFailed';


    /**
     * Stores api-id of client causing DELETE issue
     */
    protected $id;


    /**
     * Constructor
     *  Don't set member variables, since this is a Exception that handles multiple issues
     */
    public function __construct ($message, $id, $code = 0, $previous = NULL) {
        parent::__construct ($message, $code, $previous);
        $exception->id = $id;
    }

    /**
     * Get api-id of client that caused the DELETE issue
     */
    public function id() {
        return $this->id;
    }
}
