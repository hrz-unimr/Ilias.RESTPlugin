<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\desktop_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;



require_once('Modules/File/classes/class.ilObjFile.php');
require_once('Services/User/classes/class.ilObjUser.php');

class DesktopModel extends Libs\RESTModel
{

    /**
     * Retrieves all future appointments for a given user.
     * @param $user_id
     * @return array
     */
    function getPersonalDesktopItems($user_id)
    {

        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $items = $ilUser->getDesktopItems();
        return $items;
    }

    /**
     * Removes an item from the user's desktop.
     * @param $user_id
     * @param $ref_id
     */
    function removeItemFromDesktop($user_id, $ref_id)
    {
        Libs\RESTilias::initAccessHandling();
        $obj = \ilObjectFactory::getInstanceByRefId($ref_id,false);
        $item_type = $obj->getType();
        $this->removeItemFromDesktopWithType($user_id, $ref_id, $item_type);
    }

    /**
     * Internal: Removes an item of a certain type from the user's desktop.
     * @param $user_id
     * @param $ref_id
     * @param $item_type
     */
    private function removeItemFromDesktopWithType($user_id, $ref_id, $item_type)
    {

        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        global $ilDB;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        $ilUser->dropDesktopItem($ref_id, $item_type);
    }

    /**
     * Adds a new item to the user's desktop.
     * @param $user_id
     * @param $ref_id
     * @return bool
     */
    function addItemToDesktop($user_id, $ref_id)
    {
        $obj = \ilObjectFactory::getInstanceByRefId($ref_id,false);
        $item_type = $obj->getType();
        $this->addItemToDesktopWithType($user_id, $ref_id, $item_type);
        return true;
    }

    /**
     * Internal: Adds a new item of a certain type to the user's desktop.
     * @param $user_id
     * @param $ref_id
     * @param $item_type
     * @return bool
     */
    private function addItemToDesktopWithType($user_id, $ref_id, $item_type)
    {

        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        $ilUser->addDesktopItem($ref_id, $item_type);
        return true;
    }

    /**
     * Checks if the item specified by $ref_id is located at the user's desktop.
     * TODO: implementation
     * @param $user_id
     * @param $ref_id
     * @return bool
     */
    /*function isDesktopItem($user_id, $ref_id)
    {

        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        if ($ilUser->getId()!=$user_id) {
            $ilUser->setId($user_id);
            $ilUser->read();
        }
        Libs\RESTilias::initAccessHandling();
        return $ilUser->isDesktopItem($a_item_id, $a_type);
    }*/


}
