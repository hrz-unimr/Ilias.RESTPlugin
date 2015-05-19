<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;


/*
 *
 */
class TokenSettings {
    protected $salt;
    protected $ttl;


    public function __construct($salt, $ttl) {
        if (!$salt)
            throw new \Exception('TokenSettings requires a valid salt-value.');
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
