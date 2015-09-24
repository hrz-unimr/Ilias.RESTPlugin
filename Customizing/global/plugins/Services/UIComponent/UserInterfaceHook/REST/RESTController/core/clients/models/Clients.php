<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\clients;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 * Constructor requires $sqlDB.
 */
class Clients extends Libs\RESTModel {
    const MSG_NO_CLIENT_OR_FIELD = 'No client with this api-key (api-id = %id%, field = %fieldName%) found.';
    const MSG_NO_CLIENT = 'No client with this api-key (api-id = %id%) found.';

    /**
     * Will add all permissions given by $perm_json to the ui_uihk_rest_perm table for the api_key with $id.
     *
     * @params $id - The unique id of the api_key those permissions are for (see. ui_uihk_rest_keys.id)
     * @params $perm_json - JSON Array of "pattern" (route), "verb" (HTTP header) pairs of all permission
     *
     * @return NULL
     */
    protected function setPermissions($id, $perm)
    {
        // Remove old entries
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
        self::$sqlDB->manipulate($sql);

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

        if (is_array($perm) == false) {
            $perm = json_decode(utf8_encode($perm),true);
        }
        if (is_array($perm) && count($perm) > 0) {
            foreach ($perm as $value) {
                $perm_columns = array(
                    'api_id' => array('integer', $id),
                    'pattern' => array('text', $value['pattern']),
                    'verb' => array('text', $value['verb'])
                );
                self::$sqlDB->insert('ui_uihk_rest_perm', $perm_columns);
            }
        }
    }

    /**
     * Adds a route permission for a rest client specified by its api-key.
     *
     * @param $api_key
     * @param $route_pattern
     * @param $verb
     * @return int
     * @throws Exceptions\MissingApiKey
     */
    public function addPermission($api_key, $route_pattern, $verb)
    {
        // Sanity check, prevent double entries
        $api_key_id = $this->getApiIdFromKey($api_key);
        $sql = Libs\RESTLib::safeSQL("SELECT * FROM ui_uihk_rest_perm WHERE api_id = %d AND pattern = %s AND verb = %s", $api_key_id, $route_pattern, $verb);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            return -1;
        }

        // Add permission
        $perm_columns = array(
            'api_id' => array('integer', $api_key_id),
            'pattern' => array('text', $route_pattern),
            'verb' => array('text', $verb)
        );
        self::$sqlDB->insert('ui_uihk_rest_perm', $perm_columns);
        return intval(self::$sqlDB->getLastInsertId());
    }

    /**
     * Removes permission given by the unique permission id.
     *
     * @param $perm_id
     * @return mixed
     */
    public function deletePermission($perm_id)
    {
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_perm WHERE id = %d', $perm_id);
        $numAffRows = self::$sqlDB->manipulate($sql);
        return $numAffRows;
    }

    /**
     * Returns a permission statement (i.e. route-pattern + verb) given a unique permission id.
     *
     * @param $perm_id
     * @return array
     */
    public function getPermissionByPermId($perm_id)
    {
        $sql = Libs\RESTLib::safeSQL("SELECT * FROM ui_uihk_rest_perm WHERE id = %d", $perm_id);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            $row = self::$sqlDB->fetchAssoc($query);
            return $row;
        }
        return array();
    }

    /**
     * Returns all permissions for a rest client specified by its api-key.
     *
     * @param $api_key
     * @return array
     * @throws Exceptions\MissingApiKey
     */
    public function getPermissionsForApiKey($api_key)
    {
        $api_key_id = $this->getApiIdFromKey($api_key);
        $sql = Libs\RESTLib::safeSQL("SELECT * FROM ui_uihk_rest_perm WHERE api_id = %d", $api_key_id);
        $query = self::$sqlDB->query($sql);
        $aPermissions = array();
        while($row = self::$sqlDB->fetchAssoc($query)) {
            $aPermissions[] = $row;
        }
        return $aPermissions;
    }

    /**
     * Given a api_key ID and an array of user id numbers, this function writes the mapping to the table 'ui_uihk_rest_keymap'.
     * Note: Old entries will be deleted.
     *
     * @param $api_key_id
     * @param $a_user_csv
     */
    protected function fillApikeyUserMap($api_key_id, $a_user_csv = NULL)
    {
        // Remove old entries
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_keymap WHERE api_id = %d', $api_key_id);
        self::$sqlDB->manipulate($sql);

        // Add new entries
        if (is_array($a_user_csv) && count($a_user_csv) > 0)
            foreach ($a_user_csv as $user_id) {
                $a_columns = array(
                    'api_id' => array('integer', $api_key_id),
                    'user_id' => array('integer', $user_id)
                );
                self::$sqlDB->insert('ui_uihk_rest_keymap', $a_columns);
            }
    }


    /**
     * Checks if a grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @param $grant_type
     * @return bool
     */
    protected function is_oauth2_grant_type_enabled($api_key, $grant_type)
    {
        // Check if given grant_type is enabled
        // TODO: remove sprintf after safeSQL is fixed
        $sql = Libs\RESTLib::safeSQL("SELECT $grant_type FROM ui_uihk_rest_keys WHERE api_key = %s", $api_key);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            $row = self::$sqlDB->fetchAssoc($query);
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
    public function getClients()
    {
        // Will store result
        $res = array();

        // Query all api-keys
        $sqlKeys = 'SELECT * FROM ui_uihk_rest_keys ORDER BY id';
        $queryKeys = self::$sqlDB->query($sqlKeys);
        while($rowKeys = self::$sqlDB->fetchAssoc($queryKeys)) {
            $id = intval($rowKeys['id']);

            // Will store permission
            $perm = array();

            // Query api-key permissions
            $sqlPerm = Libs\RESTLib::safeSQL('SELECT pattern, verb FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
            //\RESTController\RESTController::getInstance()->log->debug($id);
            $queryPerm = self::$sqlDB->query($sqlPerm);
            while($rowPerm = self::$sqlDB->fetchAssoc($queryPerm))
                $perm[] = $rowPerm;
            $rowKeys['permissions'] = $perm;

            // Will store allowd users
            $csv = array();

            // fetch allowd users for api-key
            $sqlCSV = Libs\RESTLib::safeSQL('SELECT user_id FROM ui_uihk_rest_keymap WHERE api_id = %d', $id);
            $queryCSV = self::$sqlDB->query($sqlCSV);
            while($rowCSV = self::$sqlDB->fetchAssoc($queryCSV)) {
                $csv[] = $rowCSV['user_id'];
            }
            $rowKeys['access_user_csv'] = $csv;

            // Add entry to result
            $res[] = $rowKeys;
        }

        // Return result
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
    )
    {
        // Add client with given settings
        $a_columns = array(
            'api_key' => array('text', $api_key),
            'api_secret' => array('text', $api_secret),
            'oauth2_redirection_uri' => array('text', $oauth2_redirection_uri),
            'oauth2_consent_message' => array('text', $oauth2_consent_message),
            'oauth2_gt_client_active' => array('integer', $oauth2_gt_client_active),
            'oauth2_gt_authcode_active' => array('integer', $oauth2_gt_authcode_active),
            'oauth2_gt_implicit_active' => array('integer', $oauth2_gt_implicit_active),
            'oauth2_gt_resourceowner_active' => array('integer', $oauth2_gt_resourceowner_active),
            'oauth2_gt_client_user' => array('integer', $oauth2_gt_client_user),
            'oauth2_user_restriction_active' => array('integer', $oauth2_user_restriction_active),
            'oauth2_consent_message_active' => array('integer', $oauth2_consent_message_active),
            'oauth2_authcode_refresh_active' => array('integer', $oauth2_authcode_refresh_active),
            'oauth2_resource_refresh_active' => array('integer', $oauth2_resource_refresh_active)
        );
        self::$sqlDB->insert('ui_uihk_rest_keys', $a_columns);
        $insertId = intval(self::$sqlDB->getLastInsertId());

        // Add permissions to separate table
        $this->setPermissions($insertId, $permissions);

        // Updated list of allowed users
        if (is_string($access_user_csv) && strlen($access_user_csv) > 0) {
            $csvArray = explode(',', $access_user_csv);
            $this->fillApikeyUserMap($insertId, $csvArray);
        }
        else
            $this->fillApikeyUserMap($insertId);

        // Return new api_id
        return $insertId;
    }


    /**
     * Updates an item
     *
     * @param $id - API-Key-ID
     * @param $fieldname
     * @param $newval
     * @return mixed
     * @throws Exceptions\UpdateFailed
     */
    public function updateClient($id, $fieldname, $newval)  {

        if (strtolower($fieldname) == 'permissions') {
            $this->setPermissions($id, $newval);
        }

        // Update allowed users? (Separate table)
        else if (strtolower($fieldname) == 'access_user_csv') {
            // Updated list of allowed users
            if (is_string($newval) && strlen($newval) > 0) {
                $csvArray = explode(',', $newval);
                $this->fillApikeyUserMap($id, $csvArray);
            }
            else
                $this->fillApikeyUserMap($id);
        }
        // Update any other field...
        // Note: for now, we take it for granted that this update is prone to sql-injections. Only admins should be able to use this method / corresponding route.
        else {
            if (is_numeric($newval)) {
                $sql = Libs\RESTLib::safeSQL("UPDATE ui_uihk_rest_keys SET $fieldname = %d WHERE id = %d", $newval, $id);
            } else {
                $sql = Libs\RESTLib::safeSQL("UPDATE ui_uihk_rest_keys SET $fieldname = %s WHERE id = %d", $newval, $id);
            }
            $numAffRows = self::$sqlDB->manipulate($sql);

            if ($numAffRows === false)
                throw new Exceptions\UpdateFailed(self::MSG_NO_CLIENT_OR_FIELD, $id, $fieldname);
        }
    }


    /**
     * Deletes a REST client entry.
     *
     * @param $id
     * @return mixed
     * @throws Exceptions\DeleteFailed
     */
    public function deleteClient($id)
    {
        // Delete acutal client
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_keys WHERE id = %d', $id);
        $numAffRows = self::$sqlDB->manipulate($sql);

        // Delete all his permissions
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
        self::$sqlDB->manipulate($sql);

        // Delete list of allowed users
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_keymap WHERE api_id = %d', $id);
        self::$sqlDB->manipulate($sql);

        // Delete oauth tokens
        $sql = Libs\RESTLib::safeSQL('DELETE FROM ui_uihk_rest_oauth2 WHERE api_id = %d', $id);
        self::$sqlDB->manipulate($sql);

        if ($numAffRows === false)
            throw new Exceptions\DeleteFailed(self::MSG_NO_CLIENT, $id);
    }


    /**
     * Returns the ILIAS user id associated with the grant type: client credentials.
     *
     * @param $api_key
     * @return mixed
     */
    public function getClientCredentialsUser($api_key)
    {
        // Fetch client-credentials for api-key
        $sql = Libs\RESTLib::safeSQL('SELECT id, oauth2_gt_client_user FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        $row = self::$sqlDB->fetchAssoc($query);
        return $row['oauth2_gt_client_user'];
    }


    /**
     * Retrieves an array of ILIAS user ids that are allowed to use the grant types:
     * authcode, implicit and resource owner credentials
     *
     * @param $api_key
     * @return array
     */
    public function getAllowedUsersForApiKey($api_key)
    {
        // Fetch api_id for api-key
        $sql = Libs\RESTLib::safeSQL('SELECT id, oauth2_user_restriction_active FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        $row = self::$sqlDB->fetchAssoc($query);
        $id = intval($row['id']);

        // Check restrictions
        if ($row['oauth2_user_restriction_active'] == 1) {
            // Stores allowed users
            $a_user_ids = array();

            // Fetch allowed users
            $sql2 = Libs\RESTLib::safeSQL('SELECT user_id FROM ui_uihk_rest_keymap WHERE api_id = %s', $id);
            $query2 = self::$sqlDB->query($sql2);
            while($row2 = self::$sqlDB->fetchAssoc($query2))
                $a_user_ids[] = (int)$row2['user_id'];

            // Return allowed users
            return $a_user_ids;
        }

        // No restriction in place
        return array(-1);
    }


    /**
     * Checks if the resource owner grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_resourceowner_enabled($api_key)
    {
        return $this->is_oauth2_grant_type_enabled($api_key, 'oauth2_gt_resourceowner_active');
    }


    /**
     * Checks if the implicit grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_implicit_enabled($api_key)
    {
        return $this->is_oauth2_grant_type_enabled($api_key, 'oauth2_gt_implicit_active');
    }


    /**
     * Checks if the authcode grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_authcode_enabled($api_key)
    {
        return $this->is_oauth2_grant_type_enabled($api_key, 'oauth2_gt_authcode_active');
    }


    /**
     * Checks if the client credentials grant type is enabled for the specified API KEY.
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_gt_clientcredentials_enabled($api_key)
    {
        return $this->is_oauth2_grant_type_enabled($api_key, 'oauth2_gt_client_active');
    }


    /**
     * Checks if the oauth2 consent message is enabled, i.e. an additional page for the grant types
     * "authorization code" and "implicit grant".
     *
     * @param $api_key
     * @return bool
     */
    public function is_oauth2_consent_message_enabled($api_key)
    {
        // Query if client with this aki-key has an oauth2 consent-message set
        $sql = Libs\RESTLib::safeSQL('SELECT oauth2_consent_message_active FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            $row = self::$sqlDB->fetchAssoc($query);
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
    public function getOAuth2ConsentMessage($api_key)
    {
        // Fetch ouath2 consent-message for client with given api-key
        $sql = Libs\RESTLib::safeSQL('SELECT oauth2_consent_message FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            $row = self::$sqlDB->fetchAssoc($query);
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
    public function is_authcode_refreshtoken_enabled($api_key)
    {
        // Query if client with this aki-key has oauth2 refresh-tokens enabled (for authentification-code)
        $sql = Libs\RESTLib::safeSQL('SELECT oauth2_authcode_refresh_active FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            $row = self::$sqlDB->fetchAssoc($query);
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
    public function is_resourceowner_refreshtoken_enabled($api_key)
    {
        // Query if client with this aki-key has oauth2 refresh-tokens enabled (for resource-owner)
        $sql = Libs\RESTLib::safeSQL('SELECT oauth2_resource_refresh_active FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0) {
            $row = self::$sqlDB->fetchAssoc($query);
            if ($row['oauth2_resource_refresh_active'] == 1)
                return true;
        }
        return false;
    }


    /**
     * Returns the id given an api_key string.
     * @param $api_key
     * @return int
     * @throws Exceptions\MissingApiKey
     */
    public function getApiIdFromKey($api_key)
    {
        $sql = Libs\RESTLib::safeSQL('SELECT id FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);

        if ($query != null && $row = self::$sqlDB->fetchAssoc($query))
            return intval($row['id']);
        else
            throw new Exceptions\MissingApiKey(sprintf(self::MSG_API_KEY, $api_key));
    }


    /**
     * Returns a api_key string given an internal api id.
     * @param $api_id
     * @return string
     * @throws Exceptions\MissingApiKey
     */
    public function getApiKeyFromId($api_id)
    {
        $sql = Libs\RESTLib::safeSQL('SELECT api_key FROM ui_uihk_rest_keys WHERE id = %d', $api_id);
        $query = self::$sqlDB->query($sql);

        if ($query != null && $row = self::$sqlDB->fetchAssoc($query))
            return intval($row['api_key']);
        else
            throw new Exceptions\MissingApiKey(sprintf(self::MSG_API_ID, $api_id));
    }
}
