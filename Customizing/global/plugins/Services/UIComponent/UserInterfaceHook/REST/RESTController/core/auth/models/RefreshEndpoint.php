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
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\clients\Clients as Clients;


/**
 *
 */
class RefreshEndpoint extends EndpointBase {
    //
    const DATE_FORMAT = 'Y-m-d H:i:s';


    /**
     *
     */
    public static function isTokenActive($refreshToken) {
        //
        $sql = Libs\RESTLib::safeSQL('
            SELECT id
            FROM ui_uihk_rest_oauth2
            WHERE refresh_token=%s',
            $refreshToken->getTokenString()
        );
        $numAffRows = self::getDB()->manipulate($sql);

        return ($numAffRows > 0);
    }


    /**
     *
     */
    public static function hasRefreshKey($refreshToken) {
        //
        $user_id = $refreshToken->getUserId();
        $api_key = $refreshToken->GetApiKey();

        //
        $sql = Libs\RESTLib::safeSQL('
            SELECT ui_uihk_rest_oauth2.id
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key=%s',
            $user_id,
            $api_key
        );
        $numAffRows = self::getDB()->manipulate($sql);

        return ($numAffRows > 0);
    }


    /**
     */
    public static function getRefreshToken($accessToken, $renewToken) {
        // Check token
        if (!$accessToken->isValid())
            throw new Exceptions\TokenInvalid(Tokens\Generic::MSG_INVALID, Tokens\Generic::ID_INVALID);
        if ($accessToken->isExpired())
            throw new Exceptions\TokenInvalid(Tokens\Generic::MSG_EXPIRED, Tokens\Generic::ID_EXPIRED);

        // Reset key if existing
        if (self::hasRefreshKey($accessToken) && !$renewToken) {
            //
            $user_id = $accessToken->getUserId();
            $api_key = $accessToken->getApiKey();

            //
            $sql = Libs\RESTLib::safeSQL('
                SELECT refresh_token
                FROM ui_uihk_rest_oauth2
                JOIN ui_uihk_rest_keys
                ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
                AND ui_uihk_rest_oauth2.user_id=%d
                AND ui_uihk_rest_keys.api_key=%s',
                $user_id,
                $api_key
            );
            $query = self::getDB()->query($sql);

            //
            if ($query != null && $entry = self::getDB()->fetchAssoc($query)) {
                // Convert refresh-token string to object
                $refresh_token = $entry['refresh_token'];
                $refreshToken = Tokens\Refresh::fromMixed(self::tokenSettings('refresh'), $refresh_token);

                // Update timestamp
                self::updateTimestamp($user_id, $api_key);

                return $refreshToken;
            }
        }

        // Fallback solution (update/create new one)
        return self::getNewRefreshToken($accessToken);
    }


    /**
     */
    private static function getNewRefreshToken($accessToken) {
        //
        $user_name = $accessToken->getUserName();
        $user_id = $accessToken->getUserId();
        $api_key = $accessToken->getApiKey();
        $ilias_client = $accessToken->getIliasClient();
        $refreshToken = Tokens\Refresh::fromFields(self::tokenSettings('refresh'), $user_name, $api_key, $ilias_client);

        // Reset key if existing
        if (self::hasRefreshKey($accessToken))
            self::resetToken($user_id, $api_key, $refreshToken);
        // Create a key without replacing existing one
        else
            self::createToken($user_id, $api_key, $refreshToken);

        //
        return $refreshToken;
    }


    /**
     * Refresh Token Endpoint routine:
     * Creates a new Refresh-Token Entry (helper).
     *
     * @param $user_id
     * @param $api_key
     * @param $refresh_token
     * @return mixed the insertion id
     */
    public static function createToken($user_id, $api_key, $refreshToken) {
        //
        $api_id =  Clients::getApiIdFromKey($api_key);
        $refresh_token = $refreshToken->getTokenString();
        $now = date(self::DATE_FORMAT, time());

        //
        $a_columns = array(
            'user_id'                   => array('integer',     $user_id),
            'api_id'                    => array('text',        $api_id),
            'refresh_token'             => array('text',        $refresh_token),
            'last_refresh_timestamp'    => array('date',        $now),
            'init_timestamp'            => array('date',        $now),
            'num_resets'                => array('integer',     0)
        );
        self::getDB()->insert('ui_uihk_rest_oauth2', $a_columns);

        //
        return self::getDB()->getLastInsertId();
    }


    /**
     * Refresh Token Endpoint routine:
     * Deletes a Refresh Token Entry
     * @param $user_id
     * @param $api_key
     * @return mixed
     */
     public function deleteToken($user_id, $api_key) {
        $sql = Libs\RESTLib::safeSQL('
            DELETE ui_uihk_rest_oauth2
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON  ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key=%s',
            $user_id,
            $api_key
        );
        $numAffRows = self::getDB()->manipulate($sql);

        return $numAffRows;
    }


    /**
     * Refresh Token Endpoint routine:
     * Resets an existing refresh token entry:
     *  - Overwrites refresh token field
     *  - Increases field 'num_resets'
     *  - Overwrites field num_refresh_left
     *  - Overwrites last_refresh_timestamp
     */
    public function resetToken($user_id, $api_key, $refreshToken) {
        $refresh_token = $refreshToken->getTokenString();
        $now = date(self::DATE_FORMAT, time());

        $sql = Libs\RESTLib::safeSQL('
            UPDATE ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON  ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key=%s
            SET refresh_token = %s,
                num_resets = num_resets + 1,
                last_refresh_timestamp = %s,
                init_timestamp = %s',
            $user_id,
            $api_key,
            $refresh_token,
            $now,
            $now
        );
        $numAffRows = self::getDB()->manipulate($sql);

        return $numAffRows;
    }


    /**
     * Refresh Token Endpoint routine:
     * Returns the refresh token for an existing refresh token entry.
     * Updates the issuing time stamp.
     */
    public function updateTimestamp($user_id, $api_key) {
        $now = date(self::DATE_FORMAT, time());

        $sql = Libs\RESTLib::safeSQL('
            UPDATE ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON  ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key=%s
            SET ui_uihk_rest_oauth2.last_refresh_timestamp = %s',
            $user_id,
            $api_key,
            $now
        );
        $numAffRows = self::getDB()->manipulate($sql);

        return $numAffRows;
    }
}
