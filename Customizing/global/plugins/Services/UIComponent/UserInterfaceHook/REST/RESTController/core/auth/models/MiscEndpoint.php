<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 * Constructor requires $app.
 */
class MiscEndpoint extends EndpointBase {
    // Allow to re-use status-strings
    const MSG_RTOKEN_AUTH_FAILED = 'Failed to authenticate via ILIAS session.';


    /**
     *
     */
    public function tokenInfo($accessToken) {
        // Check token
        if (!$accessToken->isValid())
            throw new Exceptions\TokenInvalid(Libs\Generic::MSG_INVALID);
        if ($accessToken->isExpired())
            throw new Exceptions\TokenInvalid(Libs\Generic::MSG_EXPIRED);

        // Generate info for (valid) token
        return array(
            'api_key' => $accessToken->getEntry('api_key'),
            'user' =>  $accessToken->getEntry('user'),
            'type' =>  $accessToken->getEntry('type'),
            'expires_in' => $accessToken->getRemainingTime(),
            'scope' =>  $accessToken->getEntry('scope')
        );

    }


    /**
     * Further OAuth2 routines:
     * Allows for exchanging an ilias session to a bearer token.
     * This is used for administration purposes.
     * @param $app
     */
    public function rToken2Bearer($api_key, $user_id, $rtoken, $session_id) {
        // Check login-data
        if (!$this->checkSession($user_id, $rtoken, $session_id))
            throw new Exceptions\TokenInvalid(self::MSG_RTOKEN_AUTH_FAILED);

        // Generate token for user (via given api-key)
        $user = Libs\RESTLib::userIdtoLogin($user_id);
        $bearerToken = Token\Bearer::fromFields($this->tokenSettings(), $user, $api_key);
        return $bearerToken->getTokenArray();
    }
}
