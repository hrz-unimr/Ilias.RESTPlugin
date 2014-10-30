<?php
require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";
require_once "./Modules/File/classes/class.ilObjFile.php";
require_once "./Services/User/classes/class.ilObjUser.php";

class ilDesktopModel
{

    /**
     * Retrieves all future appointments for a given user.
     * @param $user_id
     */
    function getPersonalDesktopItems($user_id)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();

        self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();

        $items = $ilUser->getDesktopItems();
        return $items;
    }

    function removeItemFromDesktop($user_id, $ref_id)
    {
        $obj = ilObjectFactory::getInstanceByRefId($ref_id,false);
        $item_type = $obj->getType();
        $this->removeItemFromDesktopWithType($user_id, $ref_id, $item_type);
    }

    function removeItemFromDesktopWithType($user_id, $ref_id, $item_type)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();

        self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        global $ilDB;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();
        $ilUser->dropDesktopItem($ref_id, $item_type);
    }

    function addItemToDesktop($user_id, $ref_id)
    {
        $obj = ilObjectFactory::getInstanceByRefId($ref_id,false);
        $item_type = $obj->getType();
       // echo "item type: ".$item_type;
        $this-> addItemToDesktopWithType($user_id, $ref_id, $item_type);
        return true;
    }


    function addItemToDesktopWithType($user_id, $ref_id, $item_type)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();

        self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();
        $ilUser->addDesktopItem($ref_id, $item_type);
        //_addDesktopItem($a_usr_id, $a_item_id, $a_type, $a_par = "")
        return true;
    }


    function isDesktopItem($user_id, $ref_id)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();

        self::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        if ($ilUser->getId()!=$user_id) {
            $ilUser->setId($user_id);
            $ilUser->read();
        }
        ilRestLib::initAccessHandling();
        return $ilUser->isDesktopItem($a_item_id, $a_type);
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