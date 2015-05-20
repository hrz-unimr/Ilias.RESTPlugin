<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth\Token;


/*
 *
 */
class Refresh extends Generic {
    /**
     *
     */
    public static function fromFields($tokenSettings, $user, $api_key, $type = null, $misc = null, $lifetime = null) {
        $refreshToken = new self($tokenSettings);
        $tokenArray = $refreshToken->generateTokenArray($user, $api_key);
        $refreshToken->setToken($tokenArray);

        if ($refreshToken->getTokenArray())
            return $refreshToken;
    }


    /**
     *
     */
    protected function generateTokenArray($user, $api_key, $type = null, $misc = null, $lifetime = null) {
        // Generate random string to make re-hashing token "difficult"
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 5);

        // Generate token and return it
        $tokenArray = parent::generateTokenArray($user, $api_key, "refresh", $randomStr, $this->tokenSettings->getTTL());
        return $tokenArray;
    }
}
