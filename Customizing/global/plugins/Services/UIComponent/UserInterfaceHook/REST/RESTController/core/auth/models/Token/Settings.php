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
class Settings {
    protected $salt;
    protected $ttl;


    public function __construct($salt, $ttl) {
        if (!$salt)
            throw new \Exception('Token-Settings require a valid salt-value.');
        if (!$ttl)
            $ttl = 30;

        $this->salt = $salt;
        $this->ttl = $ttl;
    }


    public function getSalt() {
        return $this->salt;
    }


    public function getTTL() {
        return $this->ttl;
    }
}
