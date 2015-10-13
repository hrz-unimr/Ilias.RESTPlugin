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
    const MSG_NOT_ACTIVE = 'This token does not match any active refresh-token, try requesting a new one.';

    /**
     *
     */
    public function userCredentials($api_key, $username, $password, $new_refresh = null) {
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
        $iliasUserId = (int) Libs\RESTLib::getUserIdFromUserName($username);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed(self::MSG_RESTRICTED_USERS);

        // Provided wrong username/password
        $isAuth = Libs\RESTLib::authenticateViaIlias($username, $password);
        if (!$isAuth)
            throw new Exceptions\LoginFailed(self::MSG_AUTH_FAILED);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings('access'), $username, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        if ($clients->is_resourceowner_refreshtoken_enabled($api_key)) {
            $refreshModel = new RefreshEndpoint();
            $refreshToken = $refreshModel->getRefreshToken($accessToken, $new_refresh);
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
        $username = Libs\RESTLib::getUserNameFromUserId($uid);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings('access'), $username, $api_key);
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
    public function authorizationCode($api_key, $api_secret, $authCodeToken, $redirect_uri, $new_refresh = null) {
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
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings('access'), $userName, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');
        if ($clients->is_authcode_refreshtoken_enabled($api_key)) {
            $refreshModel = new RefreshEndpoint();
            $refreshToken = $refreshModel->getRefreshToken($accessToken, $new_refresh);
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
    public function refresh2Access($refreshToken, $new_refresh = null) {
        // Check token
        if (!$refreshToken->isValid())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
        if ($refreshToken->isExpired())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

        // Check if present in DB
        $model = new RefreshEndpoint();
        if (!$model->isTokenActive($refreshToken))
            throw new Exceptions\TokenInvalid(self::MSG_NOT_ACTIVE);

        //
        $user = $refreshToken->getUserName();
        $user_id = $refreshToken->getUserId();
        $api_key = $refreshToken->GetApiKey();

        //
        $bearerToken = Token\Bearer::fromFields(self::tokenSettings('bearer'), $user, $api_key);
        $accessToken = $bearerToken->getEntry('access_token');


        // TODO: Checken ob refresh-token in DB ist!


        // Generate new token or refresh old token

        if ($new_refresh)
            $refreshToken = $model->getNewRefreshToken($accessToken);
        else
            $model->updateTimestamp($user_id, $api_key);

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
