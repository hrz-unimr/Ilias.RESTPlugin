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
class MiscEndpoint extends Libs\RESTModel {
    // Allow to re-use status-strings
    const MSG_RTOKEN_AUTH_FAILED = 'Failed to authenticate.';


    /**
     *
     */
    public function tokenInfo($accessToken) {
        // Check token
        if (!$accessToken)
            throw new Exceptions\TokenInvalid(Libs\TokenLib::MSG_NO_TOKEN);
        if ($accessToken->isExpired())
            throw new Exceptions\TokenInvalid(Libs\TokenLib::MSG_EXPIRED);

        // Generate info for (valid) token
        return array(
            'api_key' => $token['api_key'],
            'user' =>  $token['user'],
            'type' =>  $token['type'],
            'expires_in' => Libs\TokenLib::getRemainingTime($token),
            'scope' =>  $token['scope']
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
        if (!$this->checkSessionData($user_id, $rtoken, $session_id))
            throw new Exceptions\TokenInvalid(MSG_RTOKEN_AUTH_FAILED);

        // Generate token for user (via given api-key)
        $user = Libs\RESTLib::userIdtoLogin($user_id);
        $access_token = Libs\TokenLib::generateBearerToken($user, $api_key);
        return array(
            'user' => $user,
            'bearerToken' => $access_token
        );
    }
}
