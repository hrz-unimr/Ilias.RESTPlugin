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
class OAuth2Token extends Libs\RESTModel {
    /**
     *
     */
    public function userCredentials($api_key, $username, $password) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        $clientValid = Libs\AuthLib::checkOAuth2Client($api_key);
        if (!$clientValid)
            throw new Exceptions\LoginFailed('There is no client with this api-key.');

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_resourceowner_enabled($api_key))
            throw new Exceptions\LoginFailed('User-credentials grant-type is disabled for this client.');

        // Check wether user is allowed to use this api-key
        $allowed_users = $clients->getAllowedUsersForApiKey($api_key);
        $iliasUserId = (int) Libs\RESTLib::loginToUserId($username);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed('Given user is not allowed to use this api-key.');

        // Provided wrong username/password
        $isAuth = Libs\AuthLib::authenticateViaIlias($username, $password);
        if (!$isAuth)
            throw new Exceptions\LoginFailed('.');

        // [All] Generate bearer & refresh-token (if enabled)
        $bearer_token = Libs\TokenLib::generateBearerToken($username, $api_key);
        if ($clients->is_resourceowner_refreshtoken_enabled($api_key))
            $refresh_token = $this->getRefreshToken(Libs\TokenLib::deserializeToken($bearer_token['access_token']));

        // [All] Return generated tokens
        return array(
            'access_token' => $bearer_token['access_token'],
            'expires_in' => $bearer_token['expires_in'],
            'token_type' => $bearer_token['token_type'],
            'scope' => $bearer_token['scope'],
            'refresh_token' => $refresh_token
        );
    }


    /**
     *
     */
    public function clientCredentials($api_key, $api_secret) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        $clientValid = Libs\AuthLib::checkOAuth2ClientCredentials($api_key, $api_secret);
        if (!$clientValid)
            throw new Exceptions\LoginFailed('There is no client with this api-key & api-secret pair.');

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_clientcredentials_enabled($api_key))
            throw new Exceptions\LoginFailed('Client-credentials grant-type is disabled for this client.');

        // -- [no] Check wether user is allowed to use this api-key --

        // Fetch username from api-key
        $uid = $clients->getClientCredentialsUser($api_key);
        $username = Libs\RESTLib::userIdtoLogin($uid);

        // [All] Generate bearer & refresh-token (if enabled)
        $bearer_token = Libs\TokenLib::generateBearerToken($username, $api_key);
        // -- [no] Refresh-token --

        // [All] Return generated tokens
        return array(
            'access_token' => $bearer_token['access_token'],
            'expires_in' => $bearer_token['expires_in'],
            'token_type' => $bearer_token['token_type'],
            'scope' => $bearer_token['scope'],
            // -- [no] Refresh-token --
        );
    }


    /**
     *
     */
    public function authorizationCode($api_key, $api_secret, $token, $redirect_uri) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // [All] Client (api-key) is not allowed to use this grant-type or doesn't exist
        $clientValid = Libs\AuthLib::checkOAuth2ClientCredentials($api_key, $api_secret);
        if (!$clientValid)
            throw new Exceptions\LoginFailed('There is no client with this api-key & api-secret pair.');

        // Is this option enabled for this api-key?
        if (!$clients->is_oauth2_gt_authcode_enabled($api_key))
            throw new Exceptions\LoginFailed('Authorization-code grant-type is disabled for this client.');

        // Check if token is still valid
        $tokenArray = Libs\TokenLib::deserializeToken($token);
        if (Libs\TokenLib::tokenExpired($tokenArray))
            throw new Exceptions\TokenExpired('The provided token has expired.');

        // Compare token content to other request data
        $t_redirect_uri = $tokenArray['misc'];
        $t_user = $tokenArray['user'];
        $t_api_key = $tokenArray['api_key'];
        if ($t_redirect_uri != $redirect_uri || $t_api_key != $api_key)
            throw new Exceptions\LoginFailed('Token information does not match other request data.');

        // Check wether user is allowed to use this api-key
        $allowed_users = $clients->getAllowedUsersForApiKey($api_key);
        $iliasUserId = (int) Libs\RESTLib::loginToUserId($t_user);
        if (!in_array(-1, $allowed_users) && !in_array($iliasUserId, $allowed_users))
            throw new Exceptions\LoginFailed('Given user is not allowed to use this api-key.');

        // [All] Generate bearer & refresh-token (if enabled)
        $bearer_token = Libs\TokenLib::generateBearerToken($t_user, $api_key);
        if ($clients->is_authcode_refreshtoken_enabled($api_key))
            $refresh_token = $this->getRefreshToken(Libs\TokenLib::deserializeToken($bearer_token['access_token']));

        // [All] Return generated tokens
        return array(
            'access_token' => $bearer_token['access_token'],
            'expires_in' => $bearer_token['expires_in'],
            'token_type' => $bearer_token['token_type'],
            'scope' => $bearer_token['scope'],
            'refresh_token' => $refresh_token
        );
    }
}
