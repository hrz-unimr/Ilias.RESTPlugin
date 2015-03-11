<?php
require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";
require_once "./Services/User/classes/class.ilObjUser.php";
require_once "Services/Contact/classes/class.ilAddressbook.php";


class ilContactsModel
{

    /**
     * Retrieves contacts for a given user listed under "My Contacts".
     * @param $user_id
     */
    function getMyContacts($user_id)
    {
        ilRESTLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRESTLib::initDefaultRESTGlobals();


        ilRESTLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRESTLib::initAccessHandling();

        $abook = new ilAddressbook($ilUser->getId());
        $entries = $abook->getEntries();

        return $entries;
    }

}