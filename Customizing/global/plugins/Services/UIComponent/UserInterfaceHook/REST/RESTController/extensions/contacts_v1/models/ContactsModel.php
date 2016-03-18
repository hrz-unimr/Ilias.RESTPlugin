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


require_once("./Services/Database/classes/class.ilAuthContainerMDB2.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once("Services/Contact/classes/class.ilAddressbook.php");


class ContactsModel
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

}
