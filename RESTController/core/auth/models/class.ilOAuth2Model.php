<?php
class ilOAuth2Model
{
    // ----------------------------------------------------------------------------------------------
    // Refresh Token Support
    /**
     * Returns a refresh token for a valid bearer token.
     * @param $bearer_token_array
     * @return string
     */
    public function getRefreshToken($bearer_token_array)
    {
        $user_id = ilRestLib::loginToUserId($bearer_token_array['user']);
        $api_key = $bearer_token_array['api_key'];
        $entry = $this->_checkRefreshTokenEntry($user_id, $api_key);

        $newRefreshToken = ilTokenLib::serializeToken(ilTokenLib::generateOAuth2RefreshToken($bearer_token_array['user'], $bearer_token_array['api_key']));
        if ($entry == null) { // Create new entry
            $this->_createNewRefreshTokenEntry($user_id,  $api_key, $newRefreshToken);
            return $newRefreshToken;
        } else { // Reset an existing entry
            $this->_resetRefreshTokenEntry($user_id, $api_key, $newRefreshToken);
            return $newRefreshToken;
        }
    }

    /**
     * Returns a new bearer token for a valid refresh token.
     * Validation check and bookkeeping is realized via an internal refresh token table.
     * @param $refresh_token
     * @return array|bool
     */
    public function getBearerTokenForRefreshToken($refresh_token)
    {
        $refresh_token_array = ilTokenLib::deserializeToken($refresh_token);
        if (ilTokenLib::tokenValid($refresh_token_array) == true) {
            $user = $refresh_token_array['user'];
            $user_id = ilRestLib::loginToUserId($user);
            $api_key = $refresh_token_array['api_key'];
            $entry = $this->_checkRefreshTokenEntry($user_id, $api_key);
            if ($entry == null) {
                return false;
            } else {
                if ($entry['num_refresh_left'] > 0 ) {
                    if ($entry['refresh_token'] == $refresh_token) {
                        $this->_issueExistingRefreshToken($user_id, $api_key);
                        $bearer_token = ilTokenLib::generateBearerToken($user, $api_key);
                        return $bearer_token;
                    } else {
                        return false;
                    }
                } else {
                    $this->_deleteRefreshTokenEntry($user_id, $api_key);
                    return false;
                }
            }
        } else {
            return "Token not valid.";
        }
    }

    /**
     * Returns the refresh token for an existing refresh token entry.
     * Decreases num_refresh_left field and updates the issuing time stamp.
     */
    private function _issueExistingRefreshToken($user_id, $api_key)
    {
        global $ilDB;
        $query = "SELECT refresh_token, num_refresh_left FROM rest_oauth2_refresh WHERE user_id=".$user_id." AND api_key='".$api_key."'";
        $set = $ilDB->query($query);
        if ($set!=null) {
            $entry = $ilDB->fetchAssoc($set);
            $ct_num_refresh_left = $entry['num_refresh_left'];
            $refresh_token = $entry['refresh_token'];

            $this->_updateRefreshTokenEntry($user_id, $api_key, "num_refresh_left", $ct_num_refresh_left-1);
            $this->_updateRefreshTokenEntry($user_id, $api_key, "last_refresh_timestamp", date("Y-m-d H:i:s",time()));
            return $refresh_token;
        }
    }



    /**
     * Resets an existing refresh token entry:
     *  - Overwrites refresh token field
     *  - Increases field "num_resets"
     *  - Overwrites field num_refresh_left
     *  - Overwrites last_refresh_timestamp
     */
    private function _resetRefreshTokenEntry($user_id, $api_key, $newRefreshToken)
    {
        global $ilDB;
        $query = "SELECT num_resets FROM rest_oauth2_refresh WHERE user_id=".$user_id." AND api_key='".$api_key."'";
        $set = $ilDB->query($query);
        if ($set!=null) {
            $entry = $ilDB->fetchAssoc($set);
            $ct_num_resets = $entry['num_resets'];

            $this->_updateRefreshTokenEntry($user_id, $api_key, "refresh_token", $newRefreshToken);
            $this->_updateRefreshTokenEntry($user_id, $api_key, "num_resets", $ct_num_resets + 1);
            $this->_updateRefreshTokenEntry($user_id, $api_key, "last_refresh_timestamp", date("Y-m-d H:i:s",time()));
            $this->_updateRefreshTokenEntry($user_id, $api_key, "num_refresh_left", 10000);
        }
    }

    /**
     * Tester of _checkRefreshTokenEntry
     * @param $bearer_token_array
     * @return array
     */
    /*public function getRefreshEntryInfo($bearer_token_array)
    {
        $user_id = ilRestLib::loginToUserId($bearer_token_array['user']);
        $api_key = $bearer_token_array['api_key'];

        $entry = $this->_checkRefreshTokenEntry($user_id, $api_key);
        if ($entry != null) {
            $result = array();
            $result['num_refresh_left'] = $entry['num_refresh_left'];
            $result['num_resets'] = $entry['num_resets'];
            $result['last_refresh_timestamp'] = $entry['last_refresh_timestamp'];
            return $result;

        }
        return array();
    }*/

    /**
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
    private function _checkRefreshTokenEntry($user_id, $api_key)
    {
        global $ilDB;
        $query = "SELECT * FROM rest_oauth2_refresh WHERE user_id=".$user_id." AND api_key='".$api_key."'";
        $set = $ilDB->query($query);
        $entry = $ilDB->fetchAssoc($set);
        return $entry;
    }

    /**
     * Creates a new Refresh-Token Entry (helper).
     *
     * @param $user_id
     * @param $api_key
     * @param $refresh_token
     * @return mixed the insertion id
     */
    private function _createNewRefreshTokenEntry($user_id, $api_key, $refresh_token)
    {
        global $ilDB;

        $a_columns = array(
            "user_id" => array("text", $user_id),
            "api_key" => array("text", $api_key),
            "refresh_token" => array("text", $refresh_token),
            "num_refresh_left" => array("integer", 10000),
            "last_refresh_timestamp" => array("date", date("Y-m-d H:i:s",0)),
            "init_timestamp" => array("date", date("Y-m-d H:i:s",time())),
            "num_resets" => array("integer", 0)
        );

        $ilDB->insert("rest_oauth2_refresh", $a_columns);
        return $ilDB->getLastInsertId();
    }

    /**
     * Deletes a Refresh Token Entry
     * @param $user_id
     * @param $api_key
     * @return mixed
     */
    private function _deleteRefreshTokenEntry($user_id, $api_key)
    {
        global $ilDB;
        $sql = "DELETE FROM rest_oauth2_refresh WHERE user_id =".$ilDB->quote($user_id, "integer")." AND api_key=".$ilDB->quote($api_key, "text");
        $numAffRows = $ilDB->manipulate($sql);
        return $numAffRows;
    }

    /**
     * Updates a refresh token entry (helper).
     * @param $user_id
     * @param $api_key
     * @param $fieldname
     * @param $newval
     * @return mixed
     */
    public function _updateRefreshTokenEntry($user_id, $api_key, $fieldname, $newval)
    {
        global $ilDB;
        $sql = "UPDATE rest_oauth2_refresh SET $fieldname = \"$newval\" WHERE user_id = ".$user_id." AND api_key='".$api_key."'";
        $numAffRows = $ilDB->manipulate($sql);
        return $numAffRows;
    }
}
?>