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
    function createClient($api_key, $api_secret, $redirection_uri, $oauth_consent_message, $permissions)
    {
        global $ilDB;

        $a_columns = array("api_key" => array("text", $api_key),
            "api_secret" => array("text", $api_secret),
            "redirection_uri" => array("text", $redirection_uri),
            "oauth_consent_message" => array("text", $oauth_consent_message),
            "permissions" => array("text", $permissions));

        $ilDB->insert("rest_apikeys", $a_columns);
        return $ilDB->getLastInsertId();
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