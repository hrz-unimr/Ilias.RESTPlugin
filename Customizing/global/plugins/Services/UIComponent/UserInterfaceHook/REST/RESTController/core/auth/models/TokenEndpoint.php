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
use \RESTController\core\clients\Clients as Clients;


/**
 *
 * Constructor requires $app & $sqlDB.
 */
class TokenEndpoint extends EndpointBase {
    // Allow to re-use status-strings
    const MSG_NO_CLIENT_KEY = 'There is no client with this api-key.';
    const MSG_NO_CLIENT_SECRET = 'There is no client with this api-key & api-secret pair.';
    const MSG_RESTRICTED_USERS = 'Given user is not allowed to use this api-key.';
    const MSG_AUTH_FAILED = 'Failed to authenticate via ILIAS username/password.';
    const MSG_TOKEN_MISMATCH = 'Token information does not match other request data.';

    /**
     *
     */
    public function userCredentials($api_key, $username, $password) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Util::checkClient($api_key))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_KEY);

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_resourceowner_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_UC_DISABLED);

        // Check wether user is allowed to use this api-key
        $allowed_users = $clients->getAllowedUsersForApiKey($api_key);
        $iliasUserId = (int) Libs\RESTLib::loginToUserId($username);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // Provided wrong username/password
        $isAuth = Libs\RESTLib::authenticateViaIlias($username, $password);
        if (!$isAuth)
            throw new Exceptions\LoginFailed(self::MSG_AUTH_FAILED);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields($this->tokenSettings(), $username, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        if ($clients->is_resourceowner_refreshtoken_enabled($api_key)) {
            $refreshModel = RefreshEndpoint::fromBase($this);
            $refreshToken = $refreshModel->getToken($accessToken);
        }

        // [All] Return generated tokens
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            'refresh_token' => ($refreshToken) ? $refreshToken->getTokenString() : null
        );
    }


    /**
     *
     */
    public function clientCredentials($api_key, $api_secret) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Util::checkClientCredentials($api_key, $api_secret))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_SECRET);

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_clientcredentials_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_CC_DISABLED);

        // -- [no] Check wether user is allowed to use this api-key --

        // Fetch username from api-key
        $uid = $clients->getClientCredentialsUser($api_key);
        $username = Libs\RESTLib::userIdtoLogin($uid);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields($this->tokenSettings(), $username, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        // -- [no] Refresh-token --

        // [All] Return generated tokens
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            // -- [no] Refresh-token --
        );
    }


    /**
     *
     */
    public function authorizationCode($api_key, $api_secret, $authCodeToken, $redirect_uri) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Util::checkClientCredentials($api_key, $api_secret))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_SECRET);

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_authcode_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_AC_DISABLED);

        // Check token
        if (!$authCodeToken->isValid())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
        if ($authCodeToken->isExpired())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

        // Compare token content to other request data
        if ($authCodeToken->getEntry('misc') != $redirect_uri || $authCodeToken->getEntry('api_key') != $api_key) 
            throw new Exceptions\LoginFailed(self::MSG_TOKEN_MISMATCH);

        // Check wether user is allowed to use this api-key
        $allowed_users = $clients->getAllowedUsersForApiKey($api_key);
        $iliasUserId = (int) Libs\RESTLib::loginToUserId($t_user);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields($this->tokenSettings(), $t_user, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        if ($clients->is_authcode_refreshtoken_enabled($api_key)) {
            $refreshModel = RefreshEndpoint::fromBase($this);
            $refreshToken = $refreshModel->getToken($accessToken);
        }

        // [All] Return generated tokens
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            'refresh_token' => ($refreshToken) ? $refreshToken->getTokenString() : null
        );
    }


    /**
     *
     */
    public function refresh2Bearer($refreshToken) {
        // Check token
        if (!$refreshToken->isValid())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
        if ($refreshToken->isExpired())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

        $user = $tokenArray['user'];
        $user_id = Libs\RESTLib::loginToUserId($user);
        $api_key = $tokenArray['api_key'];

        $modelRefresh = new RefreshEndpoint($this->app, $this->sqlDB, $this->plugin);
        $entry = $this->checkRefreshTokenEntry($user_id, $api_key); // !!!
        if ($entry == null) {
            return false;
        } else {
            if ($entry['num_refresh_left'] > 0 ) {
                if ($entry['refresh_token'] == $tokenArray) {
                    $this->issueExistingRefreshToken($user_id, $api_key); // !!!
                    $bearerToken = Token\Bearer::fromFields($this->tokenSettings(), $t_user, $api_key);
                    return $bearer_token;
                } else {
                    return false;
                }
            } else {
                $this->deleteRefreshTokenEntry($user_id, $api_key);// !!!
                return false;
            }
        }
    }
}
