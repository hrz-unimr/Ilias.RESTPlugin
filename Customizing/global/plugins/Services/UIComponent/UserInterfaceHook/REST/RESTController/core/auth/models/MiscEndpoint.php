<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
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
    public static function tokenInfo($accessToken) {
        // Check token
        if (!$accessToken->isValid())
            throw new Exceptions\TokenInvalid(Libs\Generic::MSG_INVALID);
        if ($accessToken->isExpired())
            throw new Exceptions\TokenInvalid(Libs\Generic::MSG_EXPIRED);

        // Generate info for (valid) token
        return array(
            'api_key' => $accessToken->getApiKey(),
            'user' =>  $accessToken->getUserName(),
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
    public static function rToken2Bearer($api_key, $user_id, $rtoken, $session_id, $ilias_client) {
        // Check login-data
        if (!Util::checkSession($user_id, $rtoken, $session_id)) {
            throw new Exceptions\TokenInvalid(self::MSG_RTOKEN_AUTH_FAILED);
        }

        // Generate token for user (via given api-key)
        $user = Libs\RESTLib::getUserNameFromUserId($user_id);
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings('bearer'), $user, $api_key, null, $ilias_client);
        $accessToken = $bearerToken->getEntry('access_token');

        //
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            'ilias_client' => $ilias_client,
        );
    }
}
