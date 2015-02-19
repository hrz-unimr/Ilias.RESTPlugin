<?php
require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";
class ilClientsModel
{
    /**
     * Returns all REST clients available in the system.
     * @return bool
     */
    function getClients()
    {
        global $ilDB;
        $query = "SELECT * FROM rest_apikeys order by id";
        $set = $ilDB->query($query);

        while($row = $ilDB->fetchAssoc($set))
        {
            $row['permissions'] = stripslashes($row['permissions']);
            $res[] = $row;
        }
        return $res;
    }

    /**
     * Creates a new REST client entry
     */
    function createClient($api_key, $api_secret, $oauth2_redirection_uri, $oauth2_consent_message, $oauth2_consent_message_active, $permissions,
                          $oauth2_gt_client_active,
                          $oauth2_gt_authcode_active,
                          $oauth2_gt_implicit_active,
                          $oauth2_gt_resourceowner_active,
                          $oauth2_user_restriction_active,
                          $oauth2_gt_client_user,
                          $access_user_csv,
                        $oauth2_authcode_refresh_active,
                        $oauth2_resource_refresh_active)
    {
        global $ilDB;

        $a_columns = array("api_key" => array("text", $api_key),
            "api_secret" => array("text", $api_secret),
            "oauth2_redirection_uri" => array("text", $oauth2_redirection_uri),
            "oauth2_consent_message" => array("text", $oauth2_consent_message),
            "permissions" => array("text", $permissions),
            "oauth2_gt_client_active" => array("integer", $oauth2_gt_client_active),
            "oauth2_gt_authcode_active" => array("integer", $oauth2_gt_authcode_active),
            "oauth2_gt_implicit_active" => array("integer", $oauth2_gt_implicit_active),
            "oauth2_gt_resourceowner_active" => array("integer", $oauth2_gt_resourceowner_active),
            "oauth2_gt_client_user" => array("integer", $oauth2_gt_client_user),
            "oauth2_user_restriction_active" => array("integer", $oauth2_user_restriction_active),
            "oauth2_consent_message_active" => array("integer", $oauth2_consent_message_active),
            "oauth2_authcode_refresh_active" => array("integer", $oauth2_authcode_refresh_active),
            "oauth2_resource_refresh_active" => array("integer", $oauth2_resource_refresh_active),
        );

        $ilDB->insert("rest_apikeys", $a_columns);
        $insertId = $ilDB->getLastInsertId();


        // process access_user_csv
        if ($oauth2_user_restriction_active==true) {
            $a_user_csv = array();
            if (isset($access_user_csv)) {
                $a_user_csv = explode(',', $access_user_csv);
            }
            $this->fillApikeyUserMap($insertId, $a_user_csv);
        }
        return $insertId;
    }

    /**
     * Given a api_key ID and an array of user id numbers, this function writes the mapping to the table "rest_user_apikey_map".
     * Note: Old entries will be deleted.
     *
     * @param $api_key_id
     * @param $a_user_csv
     */
    public function fillApikeyUserMap($api_key_id, $a_user_csv)
    {
        global $ilDB;

        $sql = "DELETE FROM rest_user_apikey_map WHERE api_id =".$ilDB->quote($api_key_id, "integer");
        $ilDB->manipulate($sql);

        foreach ($a_user_csv as $user_id) {
            $a_columns = array(
                "api_id" => array("integer", $api_key_id),
                "user_id" => array("integer", $user_id)
            );
            $ilDB->insert("rest_user_apikey_map", $a_columns);
        }
    }

    /**
     * Updates an item
     * @param $id
     * @param $fieldname
     * @param $newval
     * @return mixed
     */
    public function updateClient($id, $fieldname, $newval)
    {
        global $ilDB;
        $sql = "UPDATE rest_apikeys SET $fieldname = \"$newval\" WHERE id = $id";
        $numAffRows = $ilDB->manipulate($sql);
        return $numAffRows;
    }


    /**
     * Deletes a REST client entry.
     * @param $id
     * @return mixed
     */
    public function deleteClient($id)
    {
        global $ilDB;

        $sql = "DELETE FROM rest_apikeys WHERE id =".$ilDB->quote($id, "integer");

        $numAffRows = $ilDB->manipulate($sql);

        return $numAffRows;
    }


    /**
     * Returns the ILIAS user id associated with the grant type: client credentials.
     * @param $api_key
     * @return mixed
     */
    function getClientCredentialsUser($api_key)
    {
        global $ilDB;
        $query = "SELECT id, oauth2_gt_client_user FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        return $row['oauth2_gt_client_user'];
    }

    /**
     * Retrieves an array of ILIAS user ids that are allowed to use the grant types:
     * authcode, implicit and resource owner credentials
     * @param $api_key
     * @return array
     */
    function getAllowedUsersForApiKey($api_key)
    {
        global $ilDB;
        $query = "SELECT id, oauth2_user_restriction_active FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        $id = $row['id'];
        if ($row['oauth2_user_restriction_active'] == 1) {
            $query2 = "SELECT user_id FROM rest_user_apikey_map WHERE api_id=".$ilDB->quote($id, "integer");
            $set2 = $ilDB->query($query2);
            $a_user_ids = array();
            while($row2 = $ilDB->fetchAssoc($set2))
            {
                $a_user_ids[] = (int)$row2['user_id'];
            }
            return $a_user_ids;
        } else {
            return array(-1);
        }
    }

    /**
     * Checks if a REST client with the specified API KEY does exist or not.
     * @param $api_key
     * @return bool
     */
    function clientExists($api_key)
    {
        global $ilDB;
        $query = "SELECT id FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set)>0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the resource owner grant type is enabled for the specified API KEY.
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_resourceowner_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_resourceowner_active");
    }

    /**
     * Checks if the implicit grant type is enabled for the specified API KEY.
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_implicit_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_implicit_active");
    }

    /**
     * Checks if the authcode grant type is enabled for the specified API KEY.
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_authcode_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_authcode_active");
    }

    /**
     * Checks if the client credentials grant type is enabled for the specified API KEY.
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_clientcredentials_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_client_active");
    }

    /**
     * Checks if a grant type is enabled for the specified API KEY.
     * @param $api_key
     * @param $grant_type
     * @return bool
     */
    private function is_oauth2_grant_type_enabled($api_key, $grant_type)
    {
        global $ilDB;
        $query = "SELECT * FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set)>0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row[$grant_type] == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the oauth2 consent message is enabled, i.e. an additional page for the grant types
     * "authorization code" and "implicit grant".
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_consent_message_enabled($api_key) {
        global $ilDB;
        $query = "SELECT * FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set)>0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row['oauth2_consent_message_active'] == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the OAuth2 Consent Message
     * @param $api_key
     * @return string
     */
    public function getOAuth2ConsentMessage($api_key) {
        global $ilDB;
        $query = "SELECT * FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set)>0) {
            $row = $ilDB->fetchAssoc($set);
            return $row['oauth2_consent_message'];
        }
        return "";
    }

    /**
     * Checks if the refresh token support for the grant type authorization code is enabled or not.
     * @param $api_key
     * @return bool
     */
    public function is_authcode_refreshtoken_enabled($api_key) {
        global $ilDB;
        $query = "SELECT * FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set)>0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row['oauth2_authcode_refresh_active'] == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the refresh token support for the grant type resource owner grant is enabled or not.
     * @param $api_key
     * @return bool
     */
    public function is_resourceowner_refreshtoken_enabled($api_key) {
        global $ilDB;
        $query = "SELECT * FROM rest_apikeys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set)>0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row['oauth2_resource_refresh_active'] == 1) {
                return true;
            }
        }
        return false;
    }

}