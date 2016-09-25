<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */

namespace RESTController\core\clients_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;

/**
 *
 * Constructor requires $sqlDB.
 */
class ClientsLegacyModel extends Libs\RESTModel {
    const MSG_NO_CLIENT_OR_FIELD = 'No client with this api-key (api-id = %id%, field = %fieldName%) found.';
    const MSG_NO_CLIENT = 'No client with this api-key (api-id = %id%) found.';

    /**
     * Will add all permissions given by $perm_json to the ui_uihk_rest_perm table for the api_key with $id.
     *
     * @params $id - The unique id of the api_key (=api_id) those permissions are for (see. ui_uihk_rest_client.id)
     * @params $perm_json - JSON Array of "pattern" (route), "verb" (HTTP header) pairs of all permission
     *
     * @return NULL
     */
    public static function setPermissions($id, $perm)
    {
        // Remove old entries
        $sql = Libs\RESTDatabase::safeSQL('DELETE FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
        self::getDB()->manipulate($sql);

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
                self::getDB()->insert('ui_uihk_rest_perm', $perm_columns);
            }
        }
    }

    /**
     * Removes permission given by the unique permission id.
     *
     * @param $perm_id
     * @return mixed
     */
    public static function deletePermission($perm_id)
    {
        $sql = Libs\RESTDatabase::safeSQL('DELETE FROM ui_uihk_rest_perm WHERE id = %d', $perm_id);
        $numAffRows = self::getDB()->manipulate($sql);
        return $numAffRows;
    }

    /**
     * Returns a permission statement (i.e. route-pattern + verb) given a unique permission id.
     *
     * @param $perm_id
     * @return array
     */
    public static function getPermissionByPermId($perm_id)
    {
        $sql = Libs\RESTDatabase::safeSQL("SELECT * FROM ui_uihk_rest_perm WHERE id = %d", $perm_id);
        $query = self::getDB()->query($sql);
        if (self::getDB()->numRows($query) > 0) {
            $row = self::getDB()->fetchAssoc($query);
            return $row;
        }
        return array();
    }

    /**
     * Returns all permissions for a rest client specified by its api-key.
     *
     * @param $api_key
     * @return array
     */
    public static function getPermissionsForApiKey($api_key)
    {
        $api_key_id = self::getApiIdFromKey($api_key);
        $sql = Libs\RESTDatabase::safeSQL("SELECT * FROM ui_uihk_rest_perm WHERE api_id = %d", $api_key_id);
        $query = self::getDB()->query($sql);
        $aPermissions = array();
        while($row = self::getDB()->fetchAssoc($query)) {
            $aPermissions[] = $row;
        }
        return $aPermissions;
    }

    /**
     * Returns all existing REST clients / API-Key.
     *
     * @return bool
     */
    public static  function getClients()
    {
        // Fetch all clients
        $clients = Database\RESTclient::fromWhere(null, true);

        // Iterate over all clients
        $result = array();
        foreach($clients as $client) {
            // Extract clientId and complete table-row
            $id           = $client->getKey('id');
            $row          = $client->getRow();

            $sqlPerm = Libs\RESTDatabase::safeSQL('SELECT pattern, verb FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
            $queryPerm = self::getDB()->query($sqlPerm);
            $perm = [];
            while($rowPerm = self::getDB()->fetchAssoc($queryPerm)) {
                $perm[] = $rowPerm;
            }
            $row['permissions'] = $perm;

            // Remove null values
            $result[$id]  = array_filter($row, function($value) { return !is_null($value); });
        }
        return $result;
    }

    /**
     * Deletes a REST client entry.
     *
     * @param $id
     * @return mixed
     * @throws Exceptions\DeleteFailed
     */
    public static  function deleteClient($id)
    {
        // Delete acutal client
        $sql = Database\RESTclient::safeSQL('DELETE FROM ui_uihk_rest_client WHERE id = %d', $id);
        $numAffRows = self::getDB()->manipulate($sql);

        // Delete all his permissions
        $sql = Database\RESTclient::safeSQL('DELETE FROM ui_uihk_rest_perm WHERE api_id = %d', $id);
        self::getDB()->manipulate($sql);

        if ($numAffRows === false) {
            throw new Exceptions\DeleteFailed(self::MSG_NO_CLIENT, $id);
        }
    }
}
