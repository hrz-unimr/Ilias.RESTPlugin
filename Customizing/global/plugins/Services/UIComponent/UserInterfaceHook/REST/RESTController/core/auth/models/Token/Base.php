<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Token;


/*
 *
 */
class Base {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_INVALID_FIELDS = 'RESTController\core\auth\Base::ID_INVALID_FIELDS';
    const ID_INVALID_SIZE = 'RESTController\core\auth\Base::ID_INVALID_SIZE';
    const ID_NO_TOKEN = 'RESTController\libs\OAuth2Middleware::ID_NO_TOKEN';

    // Allow to re-use status-strings
    const MSG_INVALID_FIELDS = 'Token contains invalid fields: %s';
    const MSG_INVALID_SIZE = 'Token needs to be an array of size %d.';
    const MSG_NO_TOKEN = 'No access-token provided or using invalid format.';

    //
    protected $tokenSettings;
    protected $tokenArray;


    //
    protected static $fields;


    /**
     *
     */
    protected function __construct($tokenSettings) {
        $this->tokenSettings = $tokenSettings;
    }


    /**
     *
     */
    public function setToken($tokenArray) {
        if (is_array($tokenArray) && count($tokenArray) == count(static::$fields)) {
            foreach ($tokenArray as $key => $value)
                if (!in_array($key, static::$fields))
                    throw new Exceptions\TokenInvalid(sprintf(self::MSG_INVALID_FIELDS, $key));

            $this->tokenArray = $tokenArray;
        }
        else
            throw new Exceptions\TokenInvalid(sprintf(self::MSG_INVALID_SIZE, count(static::$fields)));
    }


    /**
     *
     */
    public function getTokenArray() {
        return $this->tokenArray;
    }


    /**
     *
     */
    public function getEntry($field) {
        $field = strtolower($field);
        if (in_array($field, static::$fields))
            return $this->tokenArray[$field];
    }


    /**
     *
     */
    public function setEntry($field, $value) {
        $field = strtolower($field);
        if (in_array($field, static::$fields)) {
            $this->tokenArray[$field] = $value;
        }
    }
}
