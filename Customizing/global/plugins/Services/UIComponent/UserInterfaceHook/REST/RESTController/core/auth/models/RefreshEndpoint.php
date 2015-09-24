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
    public function isTokenActive($refreshToken) {
        //
        $sql = Libs\RESTLib::safeSQL('
            SELECT id
            FROM ui_uihk_rest_oauth2
            WHERE refresh_token=%s',
            $refreshToken->getTokenString()
        );
        $numAffRows = self::$sqlDB->manipulate($sql);

        return ($numAffRows > 0);
    }


    /**
     *
     */
    public function hasRefreshKey($refreshToken) {
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
        $numAffRows = self::$sqlDB->manipulate($sql);

        return ($numAffRows > 0);
    }


    /**
     */
    public function getRefreshToken($accessToken, $renewToken) {
        // Check token
        if (!$accessToken->isValid())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
        if ($accessToken->isExpired())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

        // Reset key if existing
        if ($this->hasRefreshKey($accessToken) && !$renewToken) {
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
            $query = self::$sqlDB->query($sql);

            //
            if ($query != null && $entry = self::$sqlDB->fetchAssoc($query)) {
                // Convert refresh-token string to object
                $refresh_token = $entry['refresh_token'];
                $refreshToken = Token\Refresh::fromMixed(self::tokenSettings('refresh'), $refresh_token);

                // Update timestamp
                $this->updateTimestamp($user_id, $api_key);

                return $refreshToken;
            }
        }

        // Fallback solution (update/create new one)
        return $this->getNewRefreshToken($accessToken);
    }


    /**
     */
    private function getNewRefreshToken($accessToken) {
        //
        $user_name = $accessToken->getUserName();
        $user_id = $accessToken->getUserId();
        $api_key = $accessToken->getApiKey();
        $refreshToken = Token\Refresh::fromFields(self::tokenSettings('refresh'), $user_name, $api_key);

        // Reset key if existing
        if ($this->hasRefreshKey($accessToken))
            $this->resetToken($user_id, $api_key, $refreshToken);
        // Create a key without replacing existing one
        else
            $this->createToken($user_id, $api_key, $refreshToken);

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
    public function createToken($user_id, $api_key, $refreshToken) {
        //
        $clientsModel = new Clients();
        $api_id =  $clientsModel->getApiIdFromKey($api_key);
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
        self::$sqlDB->insert('ui_uihk_rest_oauth2', $a_columns);

        //
        return self::$sqlDB->getLastInsertId();
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
        $numAffRows = self::$sqlDB->manipulate($sql);

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
        $numAffRows = self::$sqlDB->manipulate($sql);

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
        $numAffRows = self::$sqlDB->manipulate($sql);

        return $numAffRows;
    }
}
