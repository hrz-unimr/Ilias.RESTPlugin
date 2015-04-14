<?php
require_once("./Services/Database/classes/class.ilAuthContainerMDB2.php");
require_once("./Modules/File/classes/class.ilObjFile.php");
require_once("./Services/User/classes/class.ilObjUser.php");

class DesktopModel
{

    /**
     * Retrieves all future appointments for a given user.
     * @param $user_id
     */
    function getPersonalDesktopItems($user_id)
    {
        RESTLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        RESTLib::initDefaultRESTGlobals();

        RESTLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        RESTLib::initAccessHandling();

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
        RESTLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        RESTLib::initDefaultRESTGlobals();

        RESTLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        global $ilDB;
        $ilUser->setId($user_id);
        $ilUser->read();
        RESTLib::initAccessHandling();
        $ilUser->dropDesktopItem($ref_id, $item_type);
    }

    function addItemToDesktop($user_id, $ref_id)
    {
        $obj = ilObjectFactory::getInstanceByRefId($ref_id,false);
        $item_type = $obj->getType();
        $this->addItemToDesktopWithType($user_id, $ref_id, $item_type);
        return true;
    }


    function addItemToDesktopWithType($user_id, $ref_id, $item_type)
    {
        RESTLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        RESTLib::initDefaultRESTGlobals();

        RESTLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        RESTLib::initAccessHandling();
        $ilUser->addDesktopItem($ref_id, $item_type);
        return true;
    }


    function isDesktopItem($user_id, $ref_id)
    {
        RESTLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        RESTLib::initDefaultRESTGlobals();

        RESTLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        if ($ilUser->getId()!=$user_id) {
            $ilUser->setId($user_id);
            $ilUser->read();
        }
        RESTLib::initAccessHandling();
        return $ilUser->isDesktopItem($a_item_id, $a_type);
    }


}