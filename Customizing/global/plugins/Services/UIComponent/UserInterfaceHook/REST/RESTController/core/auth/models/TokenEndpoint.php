<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T. Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
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
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_NO_REFRESH_LEFT = 'RESTController\\core\\auth\\TokenEndpoint::ID_NO_REFRESH_LEFT';


    // Allow to re-use status-strings
    const MSG_NO_CLIENT_KEY = 'There is no client with this api-key.';
    const MSG_NO_CLIENT_SECRET = 'There is no client with this api-key & api-secret pair.';
    const MSG_RESTRICTED_USERS = 'Given user is not allowed to use this api-key.';
    const MSG_AUTH_FAILED = 'Failed to authenticate via ILIAS username/password.';
    const MSG_TOKEN_MISMATCH = 'Token information does not match other request data.';
    const MSG_NO_REFRESH_LEFT = 'No renewals remaing for refresh-token.';

    /**
     *
     */
    public function userCredentials($api_key, $username, $password) {
        // Client-Model required
        $clients = new Clients();

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Util::checkClient($api_key))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_KEY);

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_resourceowner_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_UC_DISABLED);

        // Check wether user is allowed to use this api-key
        $allowed_users = $clients->getAllowedUsersForApiKey($api_key);
        $iliasUserId = (int) Libs\RESTLib::getIdFromUserName($username);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // Provided wrong username/password
        $isAuth = Libs\RESTLib::authenticateViaIlias($username, $password);
        if (!$isAuth)
            throw new Exceptions\LoginFailed(self::MSG_AUTH_FAILED);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings(), $username, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        if ($clients->is_resourceowner_refreshtoken_enabled($api_key)) {
            $refreshModel = new RefreshEndpoint();
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
        $clients = new Clients();

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Util::checkClientCredentials($api_key, $api_secret))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_SECRET);

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_clientcredentials_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_CC_DISABLED);

        // -- [no] Check wether user is allowed to use this api-key --

        // Fetch username from api-key
        $uid = $clients->getClientCredentialsUser($api_key);
        $username = Libs\RESTLib::getUserNameFromId($uid);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings(), $username, $api_key);
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
        $clients = new Clients();

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
        if ($authCodeToken->getEntry('misc') != $redirect_uri || $authCodeToken->getApiKey() != $api_key)
            throw new Exceptions\LoginFailed(self::MSG_TOKEN_MISMATCH);

        // Check wether user is allowed to use this api-key
        $allowed_users = $clients->getAllowedUsersForApiKey($api_key);
        $userName = $authCodeToken->getUserName();
        $userId = $authCodeToken->getUserId();
        if (!in_array(-1, $allowed_users) && !in_array($userId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings(), $userName, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        if ($clients->is_authcode_refreshtoken_enabled($api_key)) {
            $refreshModel = new RefreshEndpoint();
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
    public function refresh2Access($refreshToken) {
        // Check token
        if (!$refreshToken->isValid())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
        if ($refreshToken->isExpired())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

        //
        $modelRefresh = new RefreshEndpoint();
        $remainingRefreshs = $modelRefresh->getRemainingRefreshs($refreshToken);

        //
        if ($remainingRefreshs > 0) {
            //
            $user = $refreshToken->getUserName();
            $api_key = $refreshToken->GetApiKey();
            $modelRefresh->renewToken($user, $api_key, $refreshToken);

            //
            $bearerToken = Token\Bearer::fromFields(self::tokenSettings(), $user, $api_key);
            $accessToken = $bearerToken->getEntry('access_token');

            //
            return array(
                'access_token' => $accessToken->getTokenString(),
                'expires_in' => $bearerToken->getEntry('expires_in'),
                'token_type' => $bearerToken->getEntry('token_type'),
                'scope' => $bearerToken->getEntry('scope'),
                'refresh_token' => $refreshToken->getTokenString()
            );
        }
        //
        elseif ($remainingRefreshs)
            $modelRefresh->deleteToken($refreshToken);
    }
}
