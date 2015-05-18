<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;


/*
 * This class handles all Token (Bearer & Refresh) related tasks,
 * such as generating, deconstructing and validating.
 */
class TokenLib {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_EXPIRED = 'RESTController\libs\TokenLib::ID_EXPIRED';

    // Allow to re-use status-strings
    const MSG_EXPIRED = 'Token has expired.';


    //
    protected $salt = null;
    protected $tokenTTL = null;
    protected $refreshTTL = 315360000; //60*60*24*365*10 = 315360000 i.e. 10 years

    //
    protected $tokenArray = null;
    protected $tokenString = null;
    protected $type = null;


    /**
     *
     */
    public function __construct($token, $salt, $ttl = 30) {
        if (!isset($salt) || !is_string($salt) && !is_numeric($salt))
            throw new Exception('!!!');

        $this->salt = $salt;
        $this->tokenTTL = $ttl;
        $this->setToken($token);
    }


    /**
     *
     */
    public function setToken($token) {
        if (is_array($token) && count($token) == 7) {
            $this->tokenArray = $token;
            $this->tokenString = $this->serializeToken();
        }
        elseif (is_string($token)) {
            $this->tokenString = $token;
            $this->tokenArray = $this->deserializeToken();
        }
        else
            throw new \Exception('!!!');
    }


    /**
     *
     */
    public function getTokenArray() {
        return $this->tokenArray;
    }


    /**
     *
     */
    public function getTokenString() {
        return $this->tokenString;
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
    public static function generateToken($salt, $user, $api_key, $type, $misc, $lifetime) {
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
        $token['h'] = $this->hash($token);


    }


    /**
     * Checks if the generic token is valid by comparing its hash with its stored hash.
     *
     * @param $token - Token to check
     * @return bool - True if  provided token is valid, false else
     */
    public function isValid() {
        // Rehash token and compare to stored (in ['h']) value
        $rehash = $this->hash($token);
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
    public function isExpired() {
        if (!$this->isValid($token))
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
    public function getRemainingTime() {
        if ($this->isValid($token)) {
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
    public function refresh() {
        if ($this->isValid($token)) {
            $user = $token['user'];
            $api_key = $token['api_key'];
            $type = $token['type'];
            $misc = $token['misc'];

            return $this->generateToken($user, $api_key, $type, $misc, $this->tokenTTL);
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
    protected function hash() {
        $str = $token['user'] . '/' . $token['api_key'] . '/' . $token['type'] . '/' . $token['misc'] . '/' .$token['ttl'] . '/'.$token['s'];
        return hash('sha256', $this->salt . $str);
    }


    /**
     * This method serializes or packs a generic token for transport over the web.
     *
     * @param $token
     * @return string
     */
    public function serializeToken() {
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
    public function deserializeToken() {
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
}
