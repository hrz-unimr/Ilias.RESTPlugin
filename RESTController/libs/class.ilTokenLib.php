<?php


class ilTokenLib {
    private static $tokenSalt = null;
    private static $tokenTTL = null;
    
    
    private static function loadSettings() {
        global $ilDB;
        
        $query = "SELECT setting_name, setting_value FROM ui_uihk_rest_config";
        $set = $ilDB->query($query);
        while ($set != null && $row = $ilDB->fetchAssoc($set)) {
            switch ($row['setting_name']) {
                case "token_salt" :
                    self::$tokenSalt = $row['setting_value'];
                    break;
                case "token_ttl" :
                    self::$tokenTTL = $row['setting_value'];
                    break;
            }
        }
        
        // Set default value if not found    
        if (!self::$tokenTTL) 
            self::$tokenTTL = 30;
        
        // Having a salt is rather important
        if (!self::$tokenSalt) 
            throw new Exception('ilTokenLib cannot load the token-salt from your database! Check that you have (token_salt, <VALUE>) in ui_uihk_rest_config.');
    }
    
    
    public static function generateDefaultBearerToken($user) {
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
    public static function generateBearerToken($user, $api_key) {
        if (!self::$tokenTTL) 
            self::loadSettings();
        
        $token = self::generateToken($user, $api_key, "bearer", "", self::$tokenTTL);
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
    public static function generateOAuth2RefreshToken($user, $api_key) {
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
    public static function generateToken($user, $api_key, $type, $misc, $lifetime) {
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
    public static function tokenValid($token) {
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
    public static function tokenExpired($token) {
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
    public static function getRemainingTime($token) {
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
    public static function tokenRefresh($token) {
        if (self::tokenValid($token)) {
            if (!self::$tokenTTL) 
                self::loadSettings();
            
            $user = $token['user'];
            $api_key = $token['api_key'];
            $type = $token['type'];
            $misc = $token['misc'];
            return self::generateToken($user, $api_key, $type, $misc, self::$tokenTTL);
        }
        return $token;
    }
    

    /**
     * Helper method
     * @param $token
     * @return string
     */
    private static function getTokenString($token) {
        $tokenContent = $token['user'].'/'.$token['api_key'].'/'.$token['type'].'/'.$token['misc'].'/'.$token['ttl'].'/'.$token['s'];
        return $tokenContent;
    }

    
    /**
     * Helper method
     * @param $val
     * @return string
     */
    private static function hash($val) {
        if (!self::$tokenSalt) 
            self::loadSettings();
        
        return hash('sha256', self::$tokenSalt . $val);
    }

    
    /**
     * This method serializes or packs a generic token for transport over the web.
     * @param $token
     * @return string
     */
    public static function serializeToken($token) {
        $tokenStr = $token['user'].",".$token['api_key'].",".$token['type'].",".$token['misc'].",".$token['ttl'].",".$token['s'].",".$token['h'];
        return urlencode(base64_encode($tokenStr));
    }
    

    /**
     * This methods unpacks a received generic token.
     *
     * @param $serializedToken
     * @return array
     */
    public static function deserializeToken($serializedToken) {
        $tokenStr = base64_decode(urldecode($serializedToken));
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
