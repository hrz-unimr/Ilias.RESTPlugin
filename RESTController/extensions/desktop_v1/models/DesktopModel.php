<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\desktop_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \ilObjectFactory;


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
        
        RESTLib::loadIlUser();
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
        
        RESTLib::loadIlUser();
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
        
        RESTLib::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        RESTLib::initAccessHandling();
        $ilUser->addDesktopItem($ref_id, $item_type);
        return true;
    }


    function isDesktopItem($user_id, $ref_id)
    {
        
        RESTLib::loadIlUser();
        global    $ilUser;
        if ($ilUser->getId()!=$user_id) {
            $ilUser->setId($user_id);
            $ilUser->read();
        }
        RESTLib::initAccessHandling();
        return $ilUser->isDesktopItem($a_item_id, $a_type);
    }


}