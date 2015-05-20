<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 * Constructor requires $app & $sqlDB.
 */
class RefreshEndpoint extends Libs\RESTModel {
    /**
     * Refresh Token Endpoint routine:
     * Returns a refresh token for a valid bearer token.
     * @param $bearer_token_array
     * @return string
     */
    public function getToken($bearer_token) {
        $user_id = Libs\RESTLib::loginToUserId($bearer_token['user']);
        $api_key = $bearer_token['api_key'];
        $entry = $this->checkRefreshTokenEntry($user_id, $api_key);

        $newRefreshToken = Libs\TokenLib::serializeToken(Libs\TokenLib::generateOAuth2RefreshToken($bearer_token['user'], $bearer_token['api_key']));
        if ($entry == null) { // Create new entry
            $this->createNewRefreshTokenEntry($user_id,  $api_key, $newRefreshToken);
            return $newRefreshToken;
        } else { // Reset an existing entry
            $this->resetRefreshTokenEntry($user_id, $api_key, $newRefreshToken);
            return $newRefreshToken;
        }
    }


    /**
     * Refresh Token Endpoint routine:
     * Tester of checkRefreshTokenEntry
     * @param $bearer_token_array
     * @return array
     */
     public function getInfo($bearer_token_array) {
        $user_id = Libs\RESTLib::loginToUserId($bearer_token_array['user']);
        $api_key = $bearer_token_array['api_key'];

        $entry = $this->checkRefreshTokenEntry($user_id, $api_key);
        if ($entry != null) {
            $result = array();
            $result['num_refresh_left'] = $entry['num_refresh_left'];
            $result['num_resets'] = $entry['num_resets'];
            $result['last_refresh_timestamp'] = $entry['last_refresh_timestamp'];
            return $result;

        }
        return array();
    }


    /**
     * Refresh Token Endpoint routine:
     * Returns the refresh token for an existing refresh token entry.
     * Decreases num_refresh_left field and updates the issuing time stamp.
     */
    protected function issueExisting($user_id, $api_key) {
        $query = '
            SELECT refresh_token, num_refresh_left
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id='.$user_id.'
            AND ui_uihk_rest_keys.api_key="'.$api_key.'"';
        $set = $this->sqlDB->query($query);
        if ($set != null && $entry = $this->sqlDB->fetchAssoc($set)) {
            $ct_num_refresh_left = $entry['num_refresh_left'];
            $refresh_token = $entry['refresh_token'];

            $this->updateRefreshTokenEntry($user_id, $api_key, 'num_refresh_left', $ct_num_refresh_left-1);
            $this->updateRefreshTokenEntry($user_id, $api_key, 'last_refresh_timestamp', date('Y-m-d H:i:s',time()));
            return $refresh_token;
        }
    }


    /**
     * Refresh Token Endpoint routine:
     * Resets an existing refresh token entry:
     *  - Overwrites refresh token field
     *  - Increases field 'num_resets'
     *  - Overwrites field num_refresh_left
     *  - Overwrites last_refresh_timestamp
     */
    protected function reset($user_id, $api_key, $newRefreshToken) {
        $query = '
            SELECT num_resets
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id='.$user_id.'
            AND ui_uihk_rest_keys.api_key="'.$api_key.'"';

        $set = $this->sqlDB->query($query);
        if ($set != null && $entry = $this->sqlDB->fetchAssoc($set)) {
            $ct_num_resets = $entry['num_resets'];

            $this->updateRefreshTokenEntry($user_id, $api_key, 'refresh_token', $newRefreshToken);
            $this->updateRefreshTokenEntry($user_id, $api_key, 'num_resets', $ct_num_resets + 1);
            $this->updateRefreshTokenEntry($user_id, $api_key, 'last_refresh_timestamp', date('Y-m-d H:i:s',time()));
            $this->updateRefreshTokenEntry($user_id, $api_key, 'num_refresh_left', 10000);
        }
    }


    /**
     * Refresh Token Endpoint routine:
     * Provides information about an entry:
     * 1) Entry exists: yes or no.
     * 2) How many refreshs are left (num_refresh_left)
     * 3) Number of resets (num_resets).
     * 3) Last refresh timestamp (last_refresh_timestamp).
     *
     * @param $user_id
     * @param $api_key
     * @return array
     */
    protected function check($user_id, $api_key) {
        $query = '
            SELECT *
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id='.$user_id.'
            AND ui_uihk_rest_keys.api_key="'.$api_key.'"';
        $set = $this->sqlDB->query($query);
        if ($set != null && $entry = $this->sqlDB->fetchAssoc($set))
            return $entry;
        else
            return null;
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
    protected function create($user_id, $api_key, $refresh_token) {
        $sql = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $query = $this->sqlDB->query($sql);
        if ($query != null && $row = $this->sqlDB->fetchAssoc($query)) {
            $api_id = $row['id'];

            $a_columns = array(
                'user_id' => array('text', $user_id),
                'api_id' => array('text', $api_id),
                'refresh_token' => array('text', $refresh_token),
                'num_refresh_left' => array('integer', 10000),
                'last_refresh_timestamp' => array('date', date('Y-m-d H:i:s',0)),
                'init_timestamp' => array('date', date('Y-m-d H:i:s',time())),
                'num_resets' => array('integer', 0)
            );

            $this->sqlDB->insert('ui_uihk_rest_oauth2', $a_columns);
            return $this->sqlDB->getLastInsertId();
        }
    }


    /**
     * Refresh Token Endpoint routine:
     * Deletes a Refresh Token Entry
     * @param $user_id
     * @param $api_key
     * @return mixed
     */
    protected function delete($user_id, $api_key) {
        $query = '
            DELETE ui_uihk_rest_oauth2
            FROM ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id='.$user_id.'
            AND ui_uihk_rest_keys.api_key="'.$api_key.'"';
        $numAffRows = $this->sqlDB->manipulate($query);

        return $numAffRows;
    }


    /**
     * Refresh Token Endpoint routine:
     * Updates a refresh token entry (helper).
     * @param $user_id
     * @param $api_key
     * @param $fieldname
     * @param $newval
     * @return mixed
     */
     protected function update($user_id, $api_key, $fieldname, $newval) {
        $query = '
            UPDATE ui_uihk_rest_oauth2
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_oauth2.api_id = ui_uihk_rest_keys.id
            AND ui_uihk_rest_oauth2.user_id='.$user_id.'
            AND ui_uihk_rest_keys.api_key="'.$api_key.'"
            SET '.$fieldname.' = "'.$newval.'"';
        $numAffRows = $this->sqlDB->manipulate($query);

        return $numAffRows;
    }
}
