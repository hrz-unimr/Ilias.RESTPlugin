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
class Token {
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
        if (is_string($token))
            $tokenArray = self::deserialize($token);
        else
            $tokenArray = $token;

        if (is_array($tokenArray) && count($tokenArray) == 7) {
            $this->tokenArray = $tokenArray;
            $this->tokenString = self::serialize($tokenArray);
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
     * Checks if the generic token is valid by comparing its hash with its stored hash.
     *
     * @param $token - Token to check
     * @return bool - True if  provided token is valid, false else
     */
    public function isValid() {
        // Rehash token and compare to stored (in ['h']) value
        $rehash = $this->getHash($this->tokenArray);
        if($rehash != $this->tokenArray["h"])
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
        if ($this->isValid() && intval($this->tokenArray['ttl']) > time())
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
        if (!$this->isValid())
            return 0;

        $current = time();
        if (intval($this->tokenArray['ttl']) > $current)
            return intval($this->tokenArray['ttl']) - $current;
        else
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
        //
        if (!$this->isValid())
            return null;

        //
        $user = $this->tokenArray['user'];
        $api_key = $this->tokenArray['api_key'];
        $type = $this->tokenArray['type'];
        $misc = $this->tokenArray['misc'];

        //
        $token = $this->generateTokenArray($user, $api_key, $type, $misc, $this->tokenTTL);
        $this->setToken($token);
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
    public static function newToken($salt, $ttl, $user, $api_key, $type, $misc, $lifetime) {
        // Create a new token
        $tokenArray = $this->generateTokenArray($user, $api_key, $type, $misc, $lifetime);
        return new self($tokenArray, $salt, $ttl);
    }


    /**
     *
     */
    protected function generateTokenArray($user, $api_key, $type, $misc, $lifetime) {
        // Generate random string to make re-hashing token "difficult"
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 25);

        // Generate token (Examples: bearer, generic, refresh)
        $tokenArray = array(
            'user'      => $user;
            'api_key'   => $api_key;
            'type'      => $type;
            'misc'      => $misc;
            'ttl'       => strval(time() + ($lifetime * 60));
            's'         => $randomStr;
        );

        // Generate hash for token
        $tokenArray['h'] = $this->getHash($tokenArray);

        // Create a new token
        return $tokenArray;
    }


    /**
     * Generates a hash of the given token using SHA256 and prepending a
     * variable (but fixed!) salt to the token-string.
     *
     * @param $token - Token that should be hashed
     * @return string - Calcuated hash
     */
    protected function getHash($tokenArray) {
        $tokenHashStr = $tokenArray['user'] . '/' .
                        $tokenArray['api_key'] . '/' .
                        $tokenArray['type'] . '/' .
                        $tokenArray['misc'] . '/' .
                        $tokenArray['ttl'] . '/'.
                        $tokenArray['s'];
        return hash('sha256', $this->salt . $tokenHashStr);
    }


    /**
     * This method serializes or packs a generic token for transport over the web.
     *
     * @param $token
     * @return string
     */
    protected static function serializeToken($tokenArray) {
        // Note: Potential attacker could try to slip a "," into any $token value (best candidate seems to be 'api_key'), thus making deserializeToken vunerable!
        $tokenStr = $tokenArray['user'].",".
                    $tokenArray['api_key'].",".
                    $tokenArray['type'].",".
                    $tokenArray['misc'].",".
                    $tokenArray['ttl'].",".
                    $tokenArray['s'].",".
                    $tokenArray['h'];
        // Return serialized token-array
        return urlencode(base64_encode($tokenStr));
    }


    /**
     * This methods unpacks a received generic token.
     *
     * @param $serializedToken
     * @return array
     */
    protected static function deserializeToken($tokenString) {
        // Deserialize token-string
        $tokenPartArray = explode(",", base64_decode(urldecode($tokenString)));

        // Note: Potential attacker could have slipped a "," into any $token value, thus making this vunerable without at least a simple check! ...
        if (count($tokenPartArray) == 7) {
            return array(
                'user'      =>  $tokenPartArray[0];
                'api_key'   =>  $tokenPartArray[1];
                'type'      =>  $tokenPartArray[2];
                'misc'      =>  $tokenPartArray[3];
                'ttl'       =>  $tokenPartArray[4];
                's'         =>  $tokenPartArray[5];
                'h'         =>  $tokenPartArray[6];
            );
        }

        // ... Returning a null-token should make any code trying to use this token error-out.
        return null;
    }
}
