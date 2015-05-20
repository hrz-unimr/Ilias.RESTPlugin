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
class AuthEndpoint extends Libs\RESTModel {
    // Allow to re-use status-strings
    const MSG_RESPONSE_TYPE = 'Parameter response_type must be "code" or "token".';


    /**
     *
     */
    public function allGrantTypes($api_key, $redirect_uri, $username, $password, $response_type, $authenticity_token) {
        // Client-Model required
        $clients = new Clients(null, $this->sqlDB);

        // Check input
        if ($response_type != 'code' && $response_type != 'bearerToken')
            throw new Exceptions\ResponseType(self::MSG_RESPONSE_TYPE);

        // Client (api-key) is not allowed to use this grant-type or doesn't exist
        if ($response_type == 'code' && !$clients->is_oauth2_gt_authcode_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_AC_DISABLED);
        if ($response_type == 'bearerToken' && !$clients->is_oauth2_gt_implicit_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_I_DISABLED);

        // Login-data (username/password) is provided, try to authenticate
        if ($username && $password) {
            // Provided wrong API-Key?
            $clientValid = Util::checkClient($api_key);
            if (!$clientValid)
                return array(
                    'status' => 'showLogin',
                    'data' => array(
                        'error_msg' => 'API-Key incorrect!',
                        'api_key' => $api_key,
                        'redirect_uri' => $redirect_uri,
                        'response_type' => $response_type
                    )
                );

            // Provided wrong username/password
            $isAuth = Libs\RESTLib::authenticateViaIlias($username, $password);
            if (!$isAuth)
                return array(
                    'status' => 'showLogin',
                    'data' => array(
                        'error_msg' => 'Username or password incorrect!',
                        'api_key' => $api_key,
                        'redirect_uri' => $redirect_uri,
                        'response_type' => $response_type
                    )
                );

            // Need to show grant-permission site?
            if($clients->is_oauth2_consent_message_enabled($api_key)) {
                // Generate a temporary token that can be exchanged for bearer-token
                $temp_authenticity_token = Libs\TokenLib::generateSerializedToken($username, $api_key, '', '', 10);
                $oauth2_consent_message = $clients->getOAuth2ConsentMessage($api_key);

                // Return data to route/other model
                return array(
                    'status' => 'showPermission',
                    'data' => array(
                        'api_key' => $api_key,
                        'redirect_uri' => $redirect_uri,
                        'response_type' => $response_type,
                        'authenticity_token' => $temp_authenticity_token,
                        'oauth2_consent_message' => $oauth2_consent_message
                    )
                );
            }
            // No need to show grant-permissions, goto redirect target
            else {
                // Generate a temporary token that can be exchanged for bearer-token
                if ($response_type == 'code') {
                    $authorization_code = Libs\TokenLib::generateSerializedToken($username, $api_key, 'code', $redirect_uri, 10);
                    $url = $redirect_uri . '?code='.$authorization_code;
                }
                elseif ($response_type == 'bearerToken') {
                    $bearerToken = Libs\TokenLib::generateBearerToken($username, $api_key);
                    $url = $redirect_uri . '#access_token='.$bearerToken['access_token'].'&token_type=bearer'.'&expires_in='.$bearerToken['expires_in'].'&state=xyz';
                }

                // Return data to route/other model
                return array(
                    'status' => 'redirect',
                    'data' => $url
                );
            }
        }
        // Login-data (token) is provided, try to authenticate
        elseif ($authenticity_token) {
            // Check if token is still valid
            $tokenArray = Libs\TokenLib::deserializeToken($authenticity_token);
            if (Libs\TokenLib::tokenExpired($tokenArray))
                throw new Exceptions\TokenExpired(Libs\TokenLib::MSG_EXPIRED);

            // Generate a temporary token that can be exchanged for bearer-token
            $tokenUser = $tokenArray['user'];
            if ($response_type == 'code') {
                $authorization_code = Libs\TokenLib::generateSerializedToken($tokenUser, $api_key, 'code', $redirect_uri, 10);
                $url = $redirect_uri . '?code='.$authorization_code;
            }
            elseif ($response_type == 'bearerToken') {
                $bearerToken = Libs\TokenLib::generateBearerToken($tokenUser, $api_key);
                $url = $redirect_uri . '#access_token='.$bearerToken['access_token'].'&token_type=bearer'.'&expires_in='.$bearerToken['expires_in'].'&state=xyz';
            }

            // Return data to route/other model
            return array(
                'status' => 'redirect',
                'data' => $url
            );
        }
        // No login-data (token or username/password) provided, render login-page
        else
            return array(
                'status' => 'showLogin',
                'data' => array(
                    'api_key' => $api_key,
                    'redirect_uri' => $redirect_uri,
                    'response_type' => $response_type
                )
            );
    }
}
