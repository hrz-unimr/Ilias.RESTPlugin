<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\clients as Clients;
use \RESTController\libs as Libs;


/**
 * The token endpoint is used by the client to obtain an access token by presenting its authorization grant or refresh token
 * https://tools.ietf.org/html/rfc6749#section-3.2 - Token Endpoint
 *
 * Requires valid client-credentials - https://tools.ietf.org/html/rfc6749#section-2.3
 * grant_type
 * api_key (for grant_type = authorization_code)
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
    const MSG_NOT_ACTIVE = 'This token does not match any active refresh-token, try requesting a new one.';

    /**
     * Creates a bearer token for OAuth2 "user credentials" auth type.
     * TODO: encode ilias_client into the bearer token
     */
    public function userCredentials($api_key, $username, $password, $new_refresh = null, $ilias_client) {
        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Clients\RESTClient::checkClient($api_key))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_KEY);

        // Is this option enabled for this api-key?
        if (!Clients\Clients::is_oauth2_gt_resourceowner_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_UC_DISABLED);

        // Check whether user is allowed to use this api-key
        $allowed_users = Clients\Clients::getAllowedUsersForApiKey($api_key);
        $iliasUserId = (int) Libs\RESTLib::getUserId($username);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // Provided wrong username/password
        $isAuth = Util::authenticateViaIlias($username, $password);
        if (!$isAuth)
            throw new Exceptions\LoginFailed(self::MSG_AUTH_FAILED);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Tokens\Bearer::fromFields(self::tokenSettings('access'), $username, $api_key, null, $ilias_client);
        $accessToken = $bearerToken->getEntry('access_token');
        if (Clients\Clients::is_resourceowner_refreshtoken_enabled($api_key))
            $refreshToken = RefreshEndpoint::getRefreshToken($accessToken, $new_refresh);

        // [All] Return generated tokens
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            'refresh_token' => ($refreshToken) ? $refreshToken->getTokenString() : null,
            'ilias_client' => $ilias_client,
        );
    }


    /**
     *
     */
    public function clientCredentials($api_key, $api_secret, $ilias_client) {
        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Clients\RESTClient::checkClientCredentials($api_key, $api_secret))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_SECRET);

        // Is this option enabled for this api-key?
        if (!Clients\Clients::is_oauth2_gt_clientcredentials_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_CC_DISABLED);

        // -- [no] Check wether user is allowed to use this api-key --

        // Fetch username from api-key
        $uid = Clients\Clients::getClientCredentialsUser($api_key);
        $username = Libs\RESTLib::getUserName($uid);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Tokens\Bearer::fromFields(self::tokenSettings('access'), $username, $api_key, null, $ilias_client);
        $accessToken = $bearerToken->getEntry('access_token');
        // -- [no] Refresh-token --

        // [All] Return generated tokens
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            // -- [no] Refresh-token --
            'ilias_client' => $ilias_client,
        );
    }


    /**
     *
     */
    public function authorizationCode($api_key, $api_secret, $authCodeToken, $redirect_uri, $new_refresh = null) {
        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        if (!Clients\RESTClient::checkClientCredentials($api_key, $api_secret))
            throw new Exceptions\LoginFailed(self::MSG_NO_CLIENT_SECRET);

        // Is this option enabled for this api-key?
        if (!Clients\Clients::is_oauth2_gt_authcode_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_AC_DISABLED);

        // Check token
        if (!$authCodeToken->isValid())
            throw new Exceptions\TokenInvalid(Tokens\Generic::MSG_INVALID, Tokens\Generic::ID_INVALID);
        if ($authCodeToken->isExpired())
            throw new Exceptions\TokenInvalid(Tokens\Generic::MSG_EXPIRED, Tokens\Generic::ID_EXPIRED);

        // Compare token content to other request data
        if ($authCodeToken->getEntry('misc') != $redirect_uri || $authCodeToken->getApiKey() != $api_key)
            throw new Exceptions\LoginFailed(self::MSG_TOKEN_MISMATCH);

        // Check wether user is allowed to use this api-key
        $allowed_users = Clients\Clients::getAllowedUsersForApiKey($api_key);
        $userName = $authCodeToken->getUserName();
        $userId = $authCodeToken->getUserId();
        if (!in_array(-1, $allowed_users) && !in_array($userId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Tokens\Bearer::fromFields(self::tokenSettings('access'), $userName, $api_key, null, "");
        $accessToken = $bearerToken->getEntry('access_token');
        if (Clients\Clients::is_authcode_refreshtoken_enabled($api_key))
            $refreshToken = RefreshEndpoint::getRefreshToken($accessToken, $new_refresh);

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
    public function refresh2Access($refreshToken, $new_refresh = null) {
        // Check token
        if (!$refreshToken->isValid())
            throw new Exceptions\TokenInvalid(Tokens\Generic::MSG_INVALID, Tokens\Generic::ID_INVALID);
        if ($refreshToken->isExpired())
            throw new Exceptions\TokenInvalid(Tokens\Generic::MSG_EXPIRED, Tokens\Generic::ID_EXPIRED);

        // Check if present in DB
        if (!RefreshEndpoint::isTokenActive($refreshToken))
            throw new Exceptions\TokenInvalid(self::MSG_NOT_ACTIVE);

        //
        $user = $refreshToken->getUserName();
        $user_id = $refreshToken->getUserId();
        $api_key = $refreshToken->GetApiKey();
        $ilias_client = $refreshToken->getIliasClient();

        //
        $bearerToken = Tokens\Bearer::fromFields(self::tokenSettings('bearer'), $user, $api_key, null, $ilias_client);
        $accessToken = $bearerToken->getEntry('access_token');


        // TODO: Checken ob refresh-token in DB ist!


        // Generate new token or refresh old token
        if ($new_refresh)
            $refreshToken = RefreshEndpoint::getNewRefreshToken($accessToken); // TODO: Check "member has private access" IDE msg
        else
            RefreshEndpoint::updateTimestamp($user_id, $api_key);

        //
        return array(
            'access_token' => $accessToken->getTokenString(),
            'expires_in' => $bearerToken->getEntry('expires_in'),
            'token_type' => $bearerToken->getEntry('token_type'),
            'scope' => $bearerToken->getEntry('scope'),
            'refresh_token' => $refreshToken->getTokenString()
        );
    }
}
