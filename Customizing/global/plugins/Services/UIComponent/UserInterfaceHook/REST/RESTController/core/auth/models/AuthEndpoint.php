<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\clients as Clients;
use \RESTController\libs as Libs;


/**
 *
 */
class AuthEndpoint extends EndpointBase {
    // Allow to re-use status-strings
    const MSG_RESPONSE_TYPE = 'Parameter response_type must be "code" or "token".';


    /**
     *
     */
    public static function allGrantTypes($api_key, $redirect_uri, $username, $password, $response_type, $authenticityToken) {
        // Check input
        if ($response_type != 'code' && $response_type != 'token')
            throw new Exceptions\ResponseType(self::MSG_RESPONSE_TYPE);

        // Client (api-key) is not allowed to use this grant-type or doesn't exist
        if ($response_type == 'code' && !Clients\Clients::is_oauth2_gt_authcode_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_AC_DISABLED);
        if ($response_type == 'token' && !Clients\Clients::is_oauth2_gt_implicit_enabled($api_key))
            throw new Exceptions\LoginFailed(Util::MSG_I_DISABLED);

        // Login-data (username/password) is provided, try to authenticate
        if ($username && $password) {
            // Provided wrong API-Key?
            $clientValid = Clients\RESTClient::checkClient($api_key);
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
            $isAuth = Util::authenticateViaIlias($username, $password);
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
            if(Clients\Clients::is_oauth2_consent_message_enabled($api_key)) {
                // Generate a temporary token that can be exchanged for bearer-token
                $tempAuthenticityToken = Token\Generic::fromFields(self::tokenSettings('access'), $username, $api_key, 'temporary', '', 10);
                $oauth2_consent_message = Clients\Clients::getOAuth2ConsentMessage($api_key);

                // Return data to route/other model
                return array(
                    'status' => 'showPermission',
                    'data' => array(
                        'api_key' => $api_key,
                        'redirect_uri' => $redirect_uri,
                        'response_type' => $response_type,
                        'authenticity_token' => $tempAuthenticityToken->getTokenString(),
                        'oauth2_consent_message' => $oauth2_consent_message
                    )
                );
            }
            // No need to show grant-permissions, goto redirect target
            else {
                // Generate a temporary token that can be exchanged for bearer-token
                if ($response_type == 'code') {
                    $authorizationToken = Token\Generic::fromFields(self::tokenSettings('access'), $username, $api_key, 'code', $redirect_uri, 10);
                    $url = $redirect_uri . '?code='.$authorizationToken->getTokenString();
                }
                elseif ($response_type == 'token') {
                    $bearerToken = Token\Bearer::fromFields(self::tokenSettings('access'), $username, $api_key);
                    $accessToken = $bearerToken->getEntry('access_token');
                    $url = $redirect_uri . '#access_token='.$accessToken->getTokenString().'&token_type=bearer'.'&expires_in='.$bearerToken->getEntry('expires_in').'&state=xyz';
                }

                // Return data to route/other model
                return array(
                    'status' => 'redirect',
                    'data' => $url
                );
            }
        }
        // Login-data (token) is provided, try to authenticate
        elseif ($authenticityToken) {
            // Check if token is still valid
            if (!$authenticityToken->isValid())
                throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
            if ($authenticityToken->isExpired())
                throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

            // Generate a temporary token that can be exchanged for bearer-token
            $tokenUser = $authenticityToken->getUserName();
            if ($response_type == 'code') {
                $authorizationToken = Token\Generic::fromFields(self::tokenSettings('access'), $tokenUser, $api_key, 'code', $redirect_uri, 10);
                $url = $redirect_uri . '?code='.$authorizationToken->getTokenString();
            }
            elseif ($response_type == 'token') {
                $bearerToken = Token\Bearer::fromFields(self::tokenSettings('access'), $tokenUser, $api_key);
                $accessToken = $bearerToken->getEntry('access_token');
                $url = $redirect_uri . '#access_token='.$accessToken->getTokenString().'&token_type=bearer'.'&expires_in='.$bearerToken->getEntry('expires_in').'&state=xyz';
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
