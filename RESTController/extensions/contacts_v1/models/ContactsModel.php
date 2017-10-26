<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\contacts_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;



require_once('Services/User/classes/class.ilObjUser.php');
require_once('Services/Contact/classes/class.ilAddressbook.php');


class ContactsModel extends Libs\RESTModel
{

    /**
     * Retrieves contacts for a given user listed under "My Contacts".
     * @param $user_id
     * @return array list of contact entries
     */
    function getMyContacts($user_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $abook = new \ilAddressbook($ilUser->getId());
        $entries = $abook->getEntries();

        return $entries;
    }

    /**
     * Adds a new contact entry to the list of contacts of the user specified by $user_id.
     * @param $user_id
     * @param $login
     * @param $firstname
     * @param $lastname
     * @param $email
     * @return bool
     */
    function addContactEntry($user_id, $login,$firstname,$lastname,$email)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $abook = new \ilAddressbook($ilUser->getId());
        return $abook->addEntry($login,$firstname,$lastname,$email);
    }

    /**
     * Updates an existing contact of the contactlist of user $user_id.
     * @param $user_id
     * @param $a_addr_id
     * @param $a_login
     * @param $a_firstname
     * @param $a_lastname
     * @param $a_email
     * @return bool
     */
    function updateContactEntry($user_id, $a_addr_id, $a_login, $a_firstname, $a_lastname, $a_email)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $abook = new \ilAddressbook($ilUser->getId());
        return $abook->updateEntry($a_addr_id,$a_login,$a_firstname,$a_lastname,$a_email);
    }

    /**
     * Deletes a contact from the contact list of user $user_id.
     * @param $user_id
     * @param $addr_id
     * @return bool
     */
    function deleteContactEntry($user_id, $addr_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $abook = new \ilAddressbook($ilUser->getId());
        return $abook->deleteEntry($addr_id);
    }


    /**
     * Returns a contact entry.
     * Return associate array:  with keys "addr_id","login","firstname","lastname","email","auto_update"
     * 
     * @param $user_id
     * @param $addr_id
     * @return array
     */
    function getContactEntry($user_id, $addr_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $abook = new \ilAddressbook($ilUser->getId());
        return $abook->getEntry($addr_id);
    }

}
