<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Token;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth\Exceptions as Exceptions;


/*
 * This class handles all Token (Bearer & Refresh) related tasks,
 * such as generating, deconstructing and validating.
 */
class Generic extends Base {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const ID_EXPIRED = 'RESTController\core\auth\Generic::ID_EXPIRED';
    const ID_INVALID = 'RESTController\core\auth\Generic::ID_INVALID';

    // Allow to re-use status-strings
    const MSG_EXPIRED = 'Token has expired.';
    const MSG_INVALID = 'Token is invalid.';


    // Contents of TokenArray
    protected static $fields = array(
        'user',
        'api_key',
        'ilias_client',
        'type',
        'misc',
        'ttl',
        's',
        'h'
    );
    // Buffer UserId of given User
    protected $bufferedUserId = null;


    /**
     *
     */
    public static function fromMixed($tokenSettings, $tokenMixed) {
        $token = new self($tokenSettings);
        $token->setToken($tokenMixed);

        if ($token->getTokenArray())
            return $token;
    }
    public static function fromFields($tokenSettings, $user, $api_key, $type, $misc = null, $lifetime = null, $ilias_client) {
        $token = new self($tokenSettings);
        $tokenArray = $token->generateTokenArray($user, $api_key, $type, $misc, $lifetime);
        $token->setToken($tokenArray);

        if ($token->getTokenArray())
            return $token;
    }


    /**
     *
     */
    public function setToken($tokenMixed) {
        if (is_string($tokenMixed))
            $tokenArray = self::deserializeToken($tokenMixed);
        else
            $tokenArray = $tokenMixed;

        if (!$this->isValidTokenArray($tokenArray))
            throw new Exceptions\TokenInvalid(self::MSG_INVALID);

        parent::setToken($tokenArray);
    }


    /**
     *
     */
    public function getTokenString() {
        return self::serializeToken($this->tokenArray);
    }


    /**
     *
     */
    public function setEntry($field, $value) {
        if (strtolower($field) != 'h') {
            parent::setEntry($field, $value);
            $this->tokenArray['h'] = $this->getHash($this->tokenArray);
        }
    }


    /**
     *
     */
    public function getUserName() {
        return $this->tokenArray['user'];
    }
    public function getUserId() {
        if (!$this->bufferedUserId) {
            $user                 = $this->tokenArray['user'];
            $this->bufferedUserId = Libs\RESTLib::getUserIdFromUserName($user);
        }
        return $this->bufferedUserId;
    }
    public function getApiKey() {
        return $this->tokenArray['api_key'];
    }

    public function getIliasClient() {
         return $this->tokenArray['ilias_client'];
    }

    /**
     * Checks if the generic token is valid by comparing its hash with its stored hash.
     *
     * @param $token - Token to check
     * @return bool - True if  provided token is valid, false else
     */
    public function isValid() {
        return $this->isValidTokenArray($this->tokenArray);
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
        $current = time();
        if ($this->isValid() && intval($this->tokenArray['ttl']) > $current)
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
        $ilias_client = $this->tokenArray['ilias_client'];
        $type = $this->tokenArray['type'];
        $misc = $this->tokenArray['misc'];

        //
        $token = $this->generateTokenArray($user, $api_key, $type, $misc, null, $ilias_client);
        $this->setToken($token);
    }


    /**
     *
     */
    protected function generateTokenArray($user, $api_key, $type, $misc = null, $lifetime = null, $ilias_client = "") {
        if (!$lifetime)
            $lifetime = $this->tokenSettings->getTTL();
        if (!$misc)
            $misc = '';

        // Generate random string to make re-hashing token "difficult"
        $randomStr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 25);

        // Generate token (Examples: bearer, generic, refresh)
        $tokenArray = array(
            'user'          => $user,
            'api_key'       => $api_key,
            'ilias_client'  => $ilias_client,
            'type'          => $type,
            'misc'          => $misc,
            'ttl'           => strval(time() + ($lifetime * 60)),
            's'             => $randomStr
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
                        $tokenArray['ilias_client'] . '/' .
                        $tokenArray['type'] . '/' .
                        $tokenArray['misc'] . '/' .
                        $tokenArray['ttl'] . '/'.
                        $tokenArray['s'];
        return hash('sha256', $this->tokenSettings->getSalt() . $tokenHashStr);
    }


    /**
     *
     */
    protected function isValidTokenArray($tokenArray) {
        // Rehash token and compare to stored (in ['h']) value
        $rehash = $this->getHash($tokenArray);
        if($rehash != $tokenArray["h"])
            return false;
        return true;
    }


    /**
     * This method serializes or packs a generic token for transport over the web.
     *
     * @param $token
     * @return string
     */
     public static function serializeToken($tokenArray) {
        // Note: Potential attacker could try to slip a "," into any $token value (best candidate seems to be 'api_key'), thus making deserializeToken vulnerable!
        $tokenStr = $tokenArray['user'].",".
                    $tokenArray['api_key'].",".
                    $tokenArray['ilias_client'].",".
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
    public static function deserializeToken($tokenString) {
        // Deserialize token-string
        $tokenPartArray = explode(",", base64_decode(urldecode($tokenString)));

        // Note: Potential attacker could have slipped a "," into any $token value, thus making this vunerable without at least a simple check! ...
        if (count($tokenPartArray) == count(self::$fields)) {
            return array(
                'user'          =>  $tokenPartArray[0],
                'api_key'       =>  $tokenPartArray[1],
                'ilias_client'  =>  $tokenPartArray[2],
                'type'          =>  $tokenPartArray[3],
                'misc'          =>  $tokenPartArray[4],
                'ttl'           =>  $tokenPartArray[5],
                's'             =>  $tokenPartArray[6],
                'h'             =>  $tokenPartArray[7]
            );
        }

        // ... Returning a null-token should make any code trying to use this token error-out.
        return null;
    }
}
