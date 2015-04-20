<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;


/**
 *
 */
class ClientsModel {
    /**
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
    const DELETE_FAILED_ID = "RESTController\core\clients\ClientsModel::DELETE_FAILED_ID";
    const POST_FAILED_ID = "RESTController\core\clients\ClientsModel::POST_FAILED_ID";
    const PUT_FAILED_ID = "RESTController\core\clients\ClientsModel::PUT_FAILED_ID";
    
    
    /**
     * Will add all permissions given by $perm_json to the ui_uihk_rest_perm table for the api_key with $id.
     *
     *  @params $id - The unique id of the api_key those permissions are for (see. ui_uihk_rest_keys.id)
     *  @params $perm_json - JSON Array of "pattern" (route), "verb" (HTTP header) pairs of all permission
     *
     *  @return NULL
     */
    protected function addPermissions($id, $perm_json) {
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
     * Given a api_key ID and an array of user id numbers, this function writes the mapping to the table "ui_uihk_rest_keymap".
     * Note: Old entries will be deleted.
     *
     * @param $api_key_id
     * @param $a_user_csv
     */
    protected function fillApikeyUserMap($api_key_id, $a_user_csv = array()) {
        global $ilDB;

        // Remove old entries
        $sql = sprintf('DELETE FROM ui_uihk_rest_keymap WHERE api_id = %d', $api_key_id);
        $ilDB->manipulate($sql);

        // Add new entries
        foreach ($a_user_csv as $user_id) {
            $a_columns = array(
                "api_id" => array("integer", $api_key_id),
                "user_id" => array("integer", $user_id)
            );
            $ilDB->insert("ui_uihk_rest_keymap", $a_columns);
        }
    }

    
    /**
     * Checks if a grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @param $grant_type
     * @return bool
     */
    protected function is_oauth2_grant_type_enabled($api_key, $grant_type) {
        global $ilDB;
        
        // 
        $query = sprintf('SELECT %s FROM ui_uihk_rest_keys WHERE api_key = %d', $grant_type, $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row[$grant_type] == 1) 
                return true;
        }
        return false;
    }
    
    
    /**
     * Returns all REST clients available in the system.
     *
     * @return bool
     */
    public function getClients() {
        global $ilDB;
        
        $queryKeys = 'SELECT * FROM ui_uihk_rest_keys ORDER BY id';
        $setKeys = $ilDB->query($queryKeys);

        $res = array();
        while($rowKeys = $ilDB->fetchAssoc($setKeys)) {
            $id = $rowKeys['id'];
            
            $queryPerm = sprintf('SELECT pattern, verb FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
            $setPerm = $ilDB->query($queryPerm);
            
            $perm = array();
            while($rowPerm = $ilDB->fetchAssoc($setPerm)) 
                $perm[] = $rowPerm;
            $rowKeys['permissions'] = $perm;
            
            $queryCSV = sprintf('SELECT user_id FROM ui_uihk_rest_keymap WHERE api_id = %d', $id);
            $setCSV = $ilDB->query($queryCSV);
            
            $csv = array();
            while($rowCSV = $ilDB->fetchAssoc($setCSV)) 
                $csv[] = $rowCSV['user_id'];
            $rowKeys['access_user_csv'] = $csv;
            
            $res[] = $rowKeys;
        }
        
        return $res;
    }
    

    /**
     * Creates a new REST client entry
     */
    public function createClient(
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
        $ilDB->insert("ui_uihk_rest_keys", $a_columns);
        $insertId = $ilDB->getLastInsertId();
        
        $this->addPermissions($insertId, $permissions);

        // Updated list of users
        if (is_string($access_user_csv) && strlen($access_user_csv) > 0) {
            $csvArray = explode(',', $access_user_csv);
            $admin_model->fillApikeyUserMap($id, $csvArray);
        }
        else
            $admin_model->fillApikeyUserMap($id);
            
        return $insertId;
    }

    
    /**
     * Updates an item
     * 
     * @param $id
     * @param $fieldname
     * @param $newval
     * @return mixed
     */
    public function updateClient($id, $fieldname, $newval) {
        global $ilDB;
        
        if (strtolower($fieldname) == "permissions") {
            $sql = sprintf('DELETE FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
            $ilDB->manipulate($sql);
            $this->addPermissions($id, stripslashes($newval));   
        } 
        else if (strtolower($fieldname) == "access_user_csv") {
            // Updated list of users
            if (is_string($newval) && strlen($newval) > 0) {
                $csvArray = explode(',', $newval);
                $admin_model->fillApikeyUserMap($id, $csvArray);
            }
            else
                $admin_model->fillApikeyUserMap($id);
        }
        else {
            $sql = sprintf('UPDATE ui_uihk_rest_keys SET %s = "%s" WHERE id = %d', $fieldname, $newval, $id);
            $numAffRows = $ilDB->manipulate($sql);
        }

        return $numAffRows;
    }


    /**
     * Deletes a REST client entry.
     *
     * @param $id
     * @return mixed
     */
    public function deleteClient($id) {
        global $ilDB;

        $sql = sprintf('DELETE FROM ui_uihk_rest_keys WHERE id = %d', $id);
        $numAffRows = $ilDB->manipulate($sql);
        
        $sql = sprintf('DELETE FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
        $ilDB->manipulate($sql);
        
        $sql = sprintf('DELETE FROM ui_uihk_rest_keymap WHERE api_id = %d', $id);
        $ilDB->manipulate($sql);
        
        $sql = sprintf('DELETE FROM ui_uihk_rest_oauth2 WHERE api_id = %d', $id);
        $ilDB->manipulate($sql);

        return $numAffRows;
    }


    /**
     * Returns the ILIAS user id associated with the grant type: client credentials.
     *
     * @param $api_key
     * @return mixed
     */
    public function getClientCredentialsUser($api_key) {
        global $ilDB;
        
        $query = sprintf('SELECT id, oauth2_gt_client_user FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        return $row['oauth2_gt_client_user'];
    }
    

    /**
     * Retrieves an array of ILIAS user ids that are allowed to use the grant types:
     * authcode, implicit and resource owner credentials
     *
     * @param $api_key
     * @return array
     */
    public function getAllowedUsersForApiKey($api_key) {
        global $ilDB;
        $query = sprintf('SELECT id, oauth2_user_restriction_active FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($set);
        $id = $row['id'];
        if ($row['oauth2_user_restriction_active'] == 1) {
            $query2 = sprintf('SELECT user_id FROM ui_uihk_rest_keymap WHERE api_id = "%s"', $id);
            $set2 = $ilDB->query($query2);
            
            $a_user_ids = array();
            while($row2 = $ilDB->fetchAssoc($set2))
                $a_user_ids[] = (int)$row2['user_id'];

            return $a_user_ids;
        }  
        return array(-1);
    }
    

    /**
     * Checks if a REST client with the specified API KEY does exist or not.
     *
     * @param $api_key
     * @return bool
     */
    public function clientExists($api_key) {
        global $ilDB;
        
        $query = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0) 
            return true;
        return false;
    }

    
    /**
     * Checks if the resource owner grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_resourceowner_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_resourceowner_active");
    }
    

    /**
     * Checks if the implicit grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_implicit_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_implicit_active");
    }

    
    /**
     * Checks if the authcode grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_authcode_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_authcode_active");
    }

    
    /**
     * Checks if the client credentials grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_clientcredentials_enabled($api_key) {
        return $this->is_oauth2_grant_type_enabled($api_key, "oauth2_gt_client_active");
    }

    
    /**
     * Checks if the oauth2 consent message is enabled, i.e. an additional page for the grant types
     * "authorization code" and "implicit grant".
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_consent_message_enabled($api_key) {
        global $ilDB;
        $query = sprintf('SELECT oauth2_consent_message_active FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row['oauth2_consent_message_active'] == 1) 
                return true;
        }
        return false;
    }

    
    /**
     * Returns the OAuth2 Consent Message
     *
     * @param $api_key
     * @return string
     */
    public function getOAuth2ConsentMessage($api_key) {
        global $ilDB;
        
        $query = sprintf('SELECT oauth2_consent_message FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0) {
            $row = $ilDB->fetchAssoc($set);
            return $row['oauth2_consent_message'];
        }
        return "";
    }

    
    /**
     * Checks if the refresh token support for the grant type authorization code is enabled or not.
     *
     * @param $api_key
     * @return bool
     */
    public function is_authcode_refreshtoken_enabled($api_key) {
        global $ilDB;
        $query = sprintf('SELECT oauth2_authcode_refresh_active FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row['oauth2_authcode_refresh_active'] == 1) 
                return true;
        }
        return false;
    }
    

    /**
     * Checks if the refresh token support for the grant type resource owner grant is enabled or not.
     *
     * @param $api_key
     * @return bool
     */
    public function is_resourceowner_refreshtoken_enabled($api_key) {
        global $ilDB;
        $query = sprintf('SELECT oauth2_resource_refresh_active FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $ilDB->query($query);
        if ($ilDB->numRows($set) > 0) {
            $row = $ilDB->fetchAssoc($set);
            if ($row['oauth2_resource_refresh_active'] == 1) 
                return true;
        }
        return false;
    }
}
