<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\libs;
 
 
// Requires <$ilDB>


/*
 * This (fully static) class handles all Token (Bearer & Refresh) related tasks,
 * such as generating, deconstructing and validating.
 */
class TokenLib {
    // Variables fetched from database containing (fixed) salt and time-to-life
    protected static $tokenSalt = null;
    protected static $tokenTTL = null;
    protected static $refreshTTL = 60*60*24*365*10; // 10 years
    
    
    /**
     * Load all settings from database, could also load each value when
     * its required, but doing only one query should be better overall.
     * Sets $tokenSalt and $tokenTTL.
     */
    protected static function loadSettings() {
        global $ilDB;
        
        // Fetch key, value pairs from database
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
    }
    
    
    /**
     * Returns (fixed) token salt, that is used for generating "random-string" inside the token
     * Will load value from database IFF it isn't allready available.
     * 
     * @return (string) UUID used as salt-value
     */
    protected static function getSalt() {
        // Load salt
        if (!self::$tokenSalt) 
            self::loadSettings();
        // Fallback solution
        if (!self::$tokenSalt) 
            throw new \Exception('TokenLib can\'t find the token-salt inside the database! Check that there is a (token_salt, <VALUE>) entry in the ui_uihk_rest_config table.');
        
        return self::$tokenSalt;
    }
    
    
    /**
     * Returns time-to-life value for token.
     * Will load value from database IFF it isn't allready available.
     *
     * @return (number) time-to-life, in minutes
     */
    protected static function getTTL() {
        // Load ttl
        if (!self::$tokenTTL) 
            self::loadSettings();
        // Fallback solution
        if (!self::$tokenTTL) 
            self::$tokenTTL = 30;
        
        return self::$tokenTTL;
    }

    
    /**
     * Generates an OAuth2 Access Token (aka bearer token). It comprises
     * a generic token and additional fields, such as token type and scope.
     *
     * @param $user - ILIAS user
     * @param $api_key - OAuth2 client (not ILIAS ilias-client)
     * @param $scope - [Optional] Allowed scope
     * @return array - Bearer token
     */
    public static function generateBearerToken($user, $api_key, $scope=null) {
        // Generate generic token containing user and api-key
        $token = self::generateToken($user, $api_key, "bearer", "", self::getTTL());
        $ttl = self::getRemainingTime($token);
        $serializedToken = self::serializeToken($token);
        
        // Generate bearer-token containing the generic token and additional information
        $result = array();
        $result['access_token'] = $serializedToken;
        $result['expires_in'] = $ttl;
        $result['token_type'] = 'bearer';
        $result['scope'] = $scope;
        
        // Return bearer-token
        return $result;
    }

    
    /**
     * Generates an OAuth2 Refresh Token
     *
     * @param $user - ILIAS user
     * @param $api_key - OAuth2 client (not ILIAS ilias-client)
     * @return array - OAuth2 refresh-token
     */
    public static function generateOAuth2RefreshToken($user, $api_key) {
        // Generate random string to make re-hashing token "difficult"
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 5);
        
        // Generate token and return it
        $refresh_token_array = self::generateToken($user, $api_key, "refresh", $randomStr, self::$refreshTTL); 
        return $refresh_token_array;
    }

    
    /**
     * Creates a generic token. The resulting token incorporates several fields, s.t.
     * it is not necessary to validate this kind of token without use of a database.
     *
     * @param $user - ILIAS user
     * @param $api_key - OAuth2 client (not ILIAS ilias-client)
     * @param $type - [Optional] Type of token (bearer, generic, refresh)
     * @param $misc - [Optional] Additional text
     * @param $lifetime - Lifetime of token
     * @return array - A generic token (see $type for its type)
     */
    public static function generateToken($user, $api_key, $type, $misc, $lifetime) {
        // Generate random string to make re-hashing token "difficult"
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 25);
        
        // Generate token (Examples: bearer, generic, refresh)
        $token = array();
        $token['user'] = $user;
        $token['api_key'] = $api_key;
        $token['type'] = $type;
        $token['misc'] = $misc;
        $token['ttl'] =  strval(time() + ($lifetime * 60));
        $token['s'] = $randomStr;
        $token['h'] = self::hash($token);
        return $token;
    }
    

    /**
     * Checks if the generic token is valid by comparing its hash with its stored hash.
     *
     * @param $token - Token to check
     * @return bool - True if  provided token is valid, false else
     */
    public static function tokenValid($token) {
        // Rehash token and compare to stored (in ['h']) value
        $rehash = self::hash($token);
        if($rehash != $token["h"]) 
            return false;
        return true;
    }

    
    /**
     * Checks if the provided generic token is expired. 
     * Implicitly this method also checks if the token is valid.
     *
     * @param $token - Token to check
     * @return bool - True if  provided token has expired, false else
     */
    public static function tokenExpired($token) {
        if (!self::tokenValid($token))
            return true;
        if (intval($token['ttl']) > time())
            return false;
        return true;
    }

    
    /**
     * This method delivers the residual life time of a (generic) token.
     * Implicitly this method also checks if the token is valid.
     *
     * @param $token - Token to check
     * @return number - Remaining time in seconds [0, ...]
     */
    public static function getRemainingTime($token) {
        if (self::tokenValid($token)) {
            $current = time();
            
            if (intval($token['ttl']) > $current)
                return intval($token['ttl']) - $current;
        }
        return 0;
    }

    
    /**
     * This methods refreshes a generic token.
     * Implicitly this method also checks if the token is valid.
     *
     * @param $token - Token that should be refreshed
     * @return array - Generated refreshed token
     */
    public static function tokenRefresh($token) {
        if (self::tokenValid($token)) {            
            $user = $token['user'];
            $api_key = $token['api_key'];
            $type = $token['type'];
            $misc = $token['misc'];
            
            return self::generateToken($user, $api_key, $type, $misc, self::getTTL());
        }
        
        return $token;
    }

    
    /**
     * Generates a hash of the given token using SHA256 and prepending a
     * variable (but fixed!) salt to the token-string.
     *
     * @param $token - Token that should be hashed
     * @return string - Calcuated hash
     */
    protected static function hash($token) {
        $str = $token['user'] . '/' . $token['api_key'] . '/' . $token['type'] . '/' . $token['misc'] . '/' .$token['ttl'] . '/'.$token['s'];
        return hash('sha256', self::getSalt() . $str);
    }

    
    /**
     * This method serializes or packs a generic token for transport over the web.
     * 
     * @param $token
     * @return string
     */
    public static function serializeToken($token) {
        // Note: Potential attacker could try to slip a "," into any $token value (best candidate seems to be 'api_key'), thus making deserializeToken vunerable!
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
        $a_token_parts = explode(",", $tokenStr);
        
        // Note: Potential attacker could have slipped a "," into any $token value, thus making this vunerable without at least a simple check! ...
        if (count($a_token_parts) == 7) {
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
        // ... Returning a null-token should make any code trying to use this token error-out.
    }
    
    
    /**
     * Generates a token and serializes it.
     * 
     * @see generateToken($user, $api_key, $type, $misc, $lifetime)
     */
    public static function generateSerializedToken($user, $api_key, $type, $misc, $lifetime) {
        return self::serializeToken(self::generateToken($user, $api_key, $type, $misc, $lifetime));
    }
}
