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
    function createClient($api_key, $api_secret, $oauth2_redirection_uri, $oauth2_consent_message, $permissions,
                          $oauth2_gt_client_active,
                          $oauth2_gt_authcode_active,
                          $oauth2_gt_implicit_active,
                          $oauth2_gt_resourceowner_active,
                          $oauth2_user_restriction_active,
                          $oauth2_gt_client_user,
                          $access_user_csv)
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
            "oauth2_user_restriction_active" => array("integer", $oauth2_user_restriction_active)
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

}