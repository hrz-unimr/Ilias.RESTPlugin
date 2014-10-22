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
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();


        self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();

        $abook = new ilAddressbook($ilUser->getId());
        $entries = $abook->getEntries();

        return $entries;
    }



    /**
     * Initialize global instance
     *
     * @param string $a_name
     * @param string $a_class
     * @param string $a_source_file
     */
    protected static function initGlobal($a_name, $a_class, $a_source_file = null)
    {
        if($a_source_file)
        {
            include_once $a_source_file;
            $GLOBALS[$a_name] = new $a_class;
        }
        else
        {
            $GLOBALS[$a_name] = $a_class;
        }
    }

}