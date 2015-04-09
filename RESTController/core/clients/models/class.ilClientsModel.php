<?php
require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";
class ilClientsModel
{
    /**
     * addPermissions($id, $perm_json)
     * Will add all permissions given by $perm_json to the ui_uihk_rest_perm table for the api_key with $id.
     *
     *  @params $id - The unique id of the api_key those permissions are for (see. ui_uihk_rest_keys.id)
     *  @params $perm_json - JSON Array of "pattern" (route), "verb" (HTTP header) pairs of all permission
     *
     *  @return NULL
     */
    private function addPermissions($id, $perm_json) {
        global $ilDB;
         
        /* 
         * *************************
         * RANT: (rot-13 for sanity)
         * *************************
         *  Fb, V'q yvxr gb vafreg zhyvgcyr ebjf jvgu bar dhrel hfvat whfg gur fvzcyr
         *  VAFREG VAGB <gnoyr> (<pby1, pby2, ...>) INYHRF (<iny1_1, iny1_2, ...>), (<iny2_1, iny2_2, ...>) , ...;
         *  Ohg thrff jung? Jubrire qrfvtarq vyQO qvqa'g nqq fhccbeg sbe guvf gb
         *  vgf vafreg()-zrgubq... oybbql uryy!
         *  Naq AB (!!!) genafnpgvbaf nera'g snfgre guna bar fvatyr vafreg.
         *  uggc://jjj.fjrnerzvcfhz.pbz/?cnentencuf=10&glcr=Ratyvfu&fgnegfjvguyberz=snyfr
         */
        $perm = json_decode($perm_json, true);
        foreach($perm as $value) {
            $perm_columns = array(
                "api_id" => array("integer", $id),
                "pattern" => array("text", $value["pattern"]),
                "verb" => array("text", $value["verb"])
            );
            $ilDB->insert("ui_uihk_rest_perm", $perm_columns);
        }
    }
    
    /**
     * Returns all REST clients available in the system.
     * @return bool
     */
    function getClients() {
        global $ilDB;
        $queryKeys = "SELECT * FROM ui_uihk_rest_keys order by id";
        $setKeys = $ilDB->query($queryKeys);

        while($rowKeys = $ilDB->fetchAssoc($setKeys)) {
            $queryPerm = "SELECT `pattern`, `verb` FROM `ui_uihk_rest_perm` WHERE `api_id` = " . $rowKeys['id'];
            $setPerm = $ilDB->query($queryPerm);
            $perm = array();
            while($rowPerm = $ilDB->fetchAssoc($setPerm)) {
                $perm[] = $rowPerm;
            }
            $rowKeys['permissions'] = $perm;
            
            $queryCSV = "SELECT `user_id` FROM `ui_uihk_rest_keymap` WHERE `api_id` = " . $rowKeys['id'];
            $setCSV = $ilDB->query($queryCSV);
            $csv = array();
            while($rowCSV = $ilDB->fetchAssoc($setCSV)) {
                $csv[] = $rowCSV['user_id'];
            }
            $rowKeys['access_user_csv'] = $csv;
            
            $res[] = $rowKeys;
        }
        
        return $res;
    }

    /**
     * Creates a new REST client entry
     */
    function createClient(
        $api_key, 
        $api_secret, 
        $oauth2_redirection_uri, 
        $oauth2_consent_message, 
        $oauth2_consent_message_active, 
        $permissions,
        $oauth2_gt_client_active,
        $oauth2_gt_authcode_active,
        $oauth2_gt_implicit_active,
        $oauth2_gt_resourceowner_active,
        $oauth2_user_restriction_active,
        $oauth2_gt_client_user,
        $access_user_csv,
        $oauth2_authcode_refresh_active,
        $oauth2_resource_refresh_active
    ) {
        global $ilDB;
        global $ilLog;
        $ilLog->write('In createClient');
        $a_columns = array(
            "api_key" => array("text", $api_key),
            "api_secret" => array("text", $api_secret),
            "oauth2_redirection_uri" => array("text", $oauth2_redirection_uri),
            "oauth2_consent_message" => array("text", $oauth2_consent_message),
            "oauth2_gt_client_active" => array("integer", $oauth2_gt_client_active),
            "oauth2_gt_authcode_active" => array("integer", $oauth2_gt_authcode_active),
            "oauth2_gt_implicit_active" => array("integer", $oauth2_gt_implicit_active),
            "oauth2_gt_resourceowner_active" => array("integer", $oauth2_gt_resourceowner_active),
            "oauth2_gt_client_user" => array("integer", $oauth2_gt_client_user),
            "oauth2_user_restriction_active" => array("integer", $oauth2_user_restriction_active),
            "oauth2_consent_message_active" => array("integer", $oauth2_consent_message_active),
            "oauth2_authcode_refresh_active" => array("integer", $oauth2_authcode_refresh_active),
            "oauth2_resource_refresh_active" => array("integer", $oauth2_resource_refresh_active)
        );

        $ilLog->write("Try to create new client db insert data: ".$a_columns);
        $ilDB->insert("ui_uihk_rest_keys", $a_columns);
        $insertId = $ilDB->getLastInsertId();
        $ilLog->write("Try to create new client db insert id: ".$insertId);
        
        $this->addPermissions($insertId, $permissions);

        // process access_user_csv
        if ($oauth2_user_restriction_active==true) {
            $a_user_csv = array();
            if (isset($access_user_csv) && strlen($access_user_csv) > 0) {
                $a_user_csv = explode(',', $access_user_csv);
                $this->fillApikeyUserMap($insertId, $a_user_csv);
            }
        }
        return $insertId;
    }

    /**
     * Given a api_key ID and an array of user id numbers, this function writes the mapping to the table "ui_uihk_rest_keymap".
     * Note: Old entries will be deleted.
     *
     * @param $api_key_id
     * @param $a_user_csv
     */
    public function fillApikeyUserMap($api_key_id, $a_user_csv) {
        global $ilDB;

        $sql = "DELETE FROM ui_uihk_rest_keymap WHERE api_id =".$ilDB->quote($api_key_id, "integer");
        $ilDB->manipulate($sql);

        foreach ($a_user_csv as $user_id) {
            $a_columns = array(
                "api_id" => array("integer", $api_key_id),
                "user_id" => array("integer", $user_id)
            );
            $ilDB->insert("ui_uihk_rest_keymap", $a_columns);
        }
    }

    /**
     * Updates an item
     * @param $id
     * @param $fieldname
     * @param $newval
     * @return mixed
     */
    public function updateClient($id, $fieldname, $newval) {
        global $ilDB;
        
        if (strtolower($fieldname) == "permissions") {
            $ilDB->manipulate("DELETE FROM ui_uihk_rest_perm WHERE api_id = $id");
            $this->addPermissions($id, stripslashes($newval));   
        } else {
            //var_dump($fieldname);
            // !!! TODO: When api_key changes, delete ui_uihk_rest_oauth2 entry
            $numAffRows = $ilDB->manipulate("UPDATE ui_uihk_rest_keys SET $fieldname = \"$newval\" WHERE id = $id");
        }
        return $numAffRows;
    }


    /**
     * Deletes a REST client entry.
     * @param $id
     * @return mixed
     */
    public function deleteClient($id) {
        global $ilDB;

        $sql = "DELETE FROM ui_uihk_rest_keys WHERE id = ".$ilDB->quote($id, "integer");
        $numAffRows = $ilDB->manipulate($sql);
        
        $sql = "DELETE FROM ui_uihk_rest_perm WHERE api_id = ".$ilDB->quote($id, "integer");
        $ilDB->manipulate($sql);
        
        $sql = "DELETE FROM ui_uihk_rest_keymap WHERE api_id = ".$ilDB->quote($id, "integer");
        $ilDB->manipulate($sql);
        
        $sql = "DELETE FROM ui_uihk_rest_oauth2 WHERE api_id = ".$ilDB->quote($id, "integer");
        $ilDB->manipulate($sql);

        return $numAffRows;
    }


    /**
     * Returns the ILIAS user id associated with the grant type: client credentials.
     * @param $api_key
     * @return mixed
     */
    function getClientCredentialsUser($api_key) {
        global $ilDB;
        $query = "SELECT id, oauth2_gt_client_user FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
    function getAllowedUsersForApiKey($api_key) {
        global $ilDB;
        $query = "SELECT id, oauth2_user_restriction_active FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        $id = $row['id'];
        if ($row['oauth2_user_restriction_active'] == 1) {
            $query2 = "SELECT user_id FROM ui_uihk_rest_keymap WHERE api_id=".$ilDB->quote($id, "integer");
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
    function clientExists($api_key) {
        global $ilDB;
        $query = "SELECT id FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
    private function is_oauth2_grant_type_enabled($api_key, $grant_type) {
        global $ilDB;
        $query = "SELECT " . $grant_type . " FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
        $query = "SELECT oauth2_consent_message_active FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
        $query = "SELECT oauth2_consent_message FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
        $query = "SELECT oauth2_authcode_refresh_active FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
        $query = "SELECT oauth2_resource_refresh_active FROM ui_uihk_rest_keys WHERE api_key=".$ilDB->quote($api_key, "text");
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
