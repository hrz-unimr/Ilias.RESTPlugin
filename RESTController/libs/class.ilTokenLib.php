<?php

define("TOKEN_SALT", UUID);     // Used to lift entropy
define("DEFAULT_LIFETIME", 30); // 30 minutes

class ilTokenLib
{

    public static function generateDefaultBearerToken($user)
    {
        return self::generateBearerToken($user, "");
    }

    /**
     * Generates an OAuth2 Access Token (aka bearer token). It comprises
     * a generic token and additional fields, such as token type and scope.
     *
     * @param $user
     * @param $api_key - OAuth2 client (not ILIAS ilias-client)
     * @return array
     */
    public static function generateBearerToken($user, $api_key)
    {
        $token = self::generateToken($user, $api_key, "bearer", "", DEFAULT_LIFETIME);
        $ttl = self::getRemainingTime($token);
        $serializedToken = self::serializeToken($token);
        $result = array();
        $result['access_token'] = $serializedToken;
        $result['expires_in'] = $ttl;
        $result['token_type'] = 'bearer';
        $result['scope'] = null;
        return $result;
    }

    /**
     * Generates an OAuth2 Refresh Token
     * @param $user
     * @param $api_key
     * @return array
     */
    public static function generateOAuth2RefreshToken($user, $api_key)
    {
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,5);
        $refresh_token_array = ilTokenLib::generateToken($user, $api_key, "refresh", $randomStr, 5256000); //  ten years of validity
        return $refresh_token_array;
    }

    /**
     * Creates a generic token. The resulting token incorporates several fields, s.t.
     * it is not necessary to validate this kind of token without use of a database.
     *
     * @param $user
     * @param $api_key
     * @param $type
     * @param $misc
     * @param $lifetime
     * @return array
     */
    public static function generateToken($user, $api_key, $type, $misc, $lifetime)
    {
        $token = array();
        $token['user'] = $user;
        $token['api_key'] = $api_key;
        $token['type'] = $type;
        $token['misc'] = $misc;
        $token['ttl'] =  strval(time() + ($lifetime * 60));
        $token['s'] = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,25);
        $token['h'] = self::hash(self::getTokenString($token)); //  hash
        return $token;
    }

    /**
     * Checks if the generic token is valid.
     *
     * @param $token
     * @return bool
     */
    public static function tokenValid($token)
    {
        $rehash = self::hash(self::getTokenString($token));
        if($rehash != $token["h"]) {
			return false;
		}
        return true;
    }

    /**
     * Checks if the provided generic token is expired. Implicitly this method also checks if
     * the token is valid.
     *
     * @param $token
     * @return bool
     */
    public static function tokenExpired($token)
    {
        if (self::tokenValid($token)==false){
            return true;
        }else if (intval($token['ttl']) > time()){
            return false;
        }
        return true;
    }

    /**
     * This method delivers the residual life time of a (generic) token.
     *
     * @param $token
     * @return int - time in seconds
     */
    public static function getRemainingTime($token)
    {
        if (self::tokenValid($token)){
            if (intval($token['ttl']) > time()){
                return intval($token['ttl'])-time();
            }
        }
        return 0;
    }

    /**
     * This methods refreshes a generic token.
     *
     * @param $token
     * @return array
     */
    public static function tokenRefresh($token)
    {
        if (self::tokenValid($token)){
            $user = $token['user'];
            $api_key = $token['api_key'];
            $type = $token['type'];
            $misc = $token['misc'];
            return self::generateToken($user, $api_key, $type, $misc, DEFAULT_LIFETIME);
        }
        return $token;
    }

    /**
     * Helper method
     * @param $token
     * @return string
     */
    private static function getTokenString($token)
    {
        $tokenContent = $token['user'].'/'.$token['api_key'].'/'.$token['type'].'/'.$token['misc'].'/'.$token['ttl'].'/'.$token['s'];
        return $tokenContent;
    }

    /**
     * Helper method
     * @param $val
     * @return string
     */
    private static function hash($val)
    {
         //return md5(TOKEN_SALT . $val);
         return hash('sha256', TOKEN_SALT . $val);
    }

    /**
     * This method serializes or packs a generic token for transport over the web.
     * @param $token
     * @return string
     */
    public static function serializeToken($token)
    {
        $tokenStr = $token['user'].",".$token['api_key'].",".$token['type'].",".$token['misc'].",".$token['ttl'].",".$token['s'].",".$token['h'];
        return urlencode(base64_encode($tokenStr));
        //return $tokenStr;
    }

    /**
     * This methods unpacks a received generic token.
     *
     * @param $serializedToken
     * @return array
     */
    public static function deserializeToken($serializedToken)
    {
        $tokenStr = base64_decode(urldecode($serializedToken));
        //$tokenStr = $serializedToken;
        $a_token_parts = explode(",",$tokenStr);
        $token = array();
        $token['user']      =  $a_token_parts[0];
        $token['api_key']   =  $a_token_parts[1];
        $token['type']      =  $a_token_parts[2];
        $token['misc']      =  $a_token_parts[3];
        $token['ttl']       =  $a_token_parts[4];
        $token['s']         =  $a_token_parts[5];
        $token['h']         =  $a_token_parts[6];
        return $token;
    }
}

