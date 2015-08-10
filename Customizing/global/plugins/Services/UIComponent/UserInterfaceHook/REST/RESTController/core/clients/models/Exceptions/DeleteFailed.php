<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\clients\Exceptions;


/**
 *
 */
class Delete extends \Exception {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID = 'RESTController\\core\\clients\\Exceptions\\Delete';


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
        $this->id = $id;
    }

    /**
     * Get api-id of client that caused the DELETE issue
     */
    public function id() {
        return $this->id;
    }


    /**
     *
     */
    public function getMessage() {
        $message = parent::getMessage();
        $message = str_replace('%id%', $this->id, $message);
        return $message;
    }
}
