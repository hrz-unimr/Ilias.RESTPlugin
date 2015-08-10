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


/**
 *
 */
class RefreshEndpoint extends EndpointBase {
    //
    const DEFAULT_RENEW_COUNT = 1000;
    const DATE_FORMAT = 'Y-m-d H:i:s';


    /**
     *
     */
    public function getRemainingRefreshs($refreshToken) {
        //
        $user_id = $refreshToken->getUserId();
        $api_key = $refreshToken->GetApiKey();
        $refresh_token = $refreshToken->getTokenString();

        //
        $sql = Libs\RESTLib::safeSQL('
            SELECT num_refresh_left
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key=%s
            AND ui_uihk_rest_oauth2.refresh_token=%s',
            $user_id,
            $api_key,
            $refresh_token
        );
        $query = self::$sqlDB->query($sql);

        //
        if ($query != null && $entry = self::$sqlDB->fetchAssoc($query))
            return $entry['num_refresh_left'];
    }


    /**
     */
    public function getToken($accessToken) {
        // Check token
        if (!$accessToken->isValid())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_INVALID);
        if ($accessToken->isExpired())
            throw new Exceptions\TokenInvalid(Token\Generic::MSG_EXPIRED);

        //
        $user_id = $accessToken->getUserId();
        $api_key = $accessToken->getApiKey();
        $refreshToken = Token\Refresh::fromFields(self::tokenSettings(), $user_id, $api_key);
        $remainingRefreshs = $this->getRemainingRefreshs($refreshToken);

        //
        if ($remainingRefreshs)
            $this->createToken($user_id, $api_key, $refreshToken);
        else
            $this->resetToken($user_id, $api_key, $refreshToken);

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
        $clientsModel = new Clients\Clients();
        $api_id =  $clientsModel->getApiIdFromKey($api_key);
        $refresh_token = $refreshToken->getTokenString();
        $now = date(self::DATE_FORMAT, time());

        //
        $a_columns = array(
            'user_id'                   => array('text',        $user_id),
            'api_id'                    => array('text',        $api_id),
            'refresh_token'             => array('text',        $refresh_token),
            'num_refresh_left'          => array('integer',     self::DEFAULT_RENEW_COUNT),
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
     * Returns the refresh token for an existing refresh token entry.
     * Decreases num_refresh_left field and updates the issuing time stamp.
     */
    public function renewToken($user_id, $api_key) {
        $now = date(self::DATE_FORMAT, time());

        $sql = Libs\RESTLib::safeSQL('
            UPDATE ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON  ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id=%d
            AND ui_uihk_rest_keys.api_key=%s
            SET num_refresh_left = num_refresh_left - 1,
                last_refresh_timestamp = %s',
            $user_id,
            $api_key,
            $now
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
                num_refresh_left = %d',
            $user_id,
            $api_key,
            $refresh_token,
            $now,
            self::DEFAULT_RENEW_COUNT
        );
        $numAffRows = self::$sqlDB->manipulate($sql);

        return $numAffRows;
    }
}
