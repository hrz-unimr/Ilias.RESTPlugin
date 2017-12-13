<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\groups_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('Services/Utilities/classes/class.ilUtil.php');
require_once('Modules/Course/classes/class.ilObjCourse.php');
require_once('Services/Object/classes/class.ilObjectFactory.php');
require_once('Services/Object/classes/class.ilObjectActivation.php');
require_once('Modules/LearningModule/classes/class.ilObjLearningModule.php');
require_once('Modules/LearningModule/classes/class.ilLMPageObject.php');



class GroupsModel extends Libs\RESTModel
{

    /**
     * This method lists all groups of a user that are visible and readable.
     *
     * @param $usr_id
     * @return an array of ref_ids
     */
    public function getGroupsOfUser($usr_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($usr_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
       // $list = \ilUtil::getDataDir();
        $list = \ilUtil::_getObjectsByOperations('grp','visible,read',$usr_id); // returns ref_ids
        return $list;
    }


    /**
     * This method delivers basic information such as title and description about a group.
     *
     * @param $crs_ref_id
     * @return array
     */
    public function getGroupInfo($crs_ref_id)
    {
        require_once('Services/Xml/classes/class.ilSaxParser.php');
        Libs\RESTilias::initGlobal('objDefinition', 'ilObjectDefinition','./Services/Object/classes/class.ilObjectDefinition.php');
        Libs\RESTilias::initGlobal('ilObjDataCache', 'ilObjectDataCache','./Services/Object/classes/class.ilObjectDataCache.php');
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition, $ilObjDataCache;

        $grp_info = array();
        $grp_info['ref_id'] = $crs_ref_id;
        $obj = \ilObjectFactory::getInstanceByRefId($crs_ref_id,false);
        $grp_info['title'] = $obj->getTitle();
        $grp_info['description'] = $obj->getDescription();
        $grp_info['create_date'] = $obj->create_date;
        $grp_info['type'] = $obj->getType();
        //var_dump($obj);
        return $grp_info;
    }

    /**
     * Retrieves the content of a group as an array.
     *
     * @param $crs_ref_id
     * @return array
     */
    public function getGroupContent($crs_ref_id)
    {

        require_once('Services/Xml/classes/class.ilSaxParser.php');
        Libs\RESTilias::initGlobal('objDefinition', 'ilObjectDefinition','./Services/Object/classes/class.ilObjectDefinition.php');
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition;

        $crs_items = array();

        $sorted_items = \ilObjectActivation::getTimingsItems($crs_ref_id);

        foreach($sorted_items as $item)
        {
            $record=array();
            $record['ref_id'] = $item['ref_id'];
            $record['type'] = $item['type'];
            $record['title'] = $item['title'];
            $record['description'] = $item['description'];
            $record['parent_ref_id'] = $crs_ref_id;
            //var_dump($item);
            $crs_items[] = $record;
        }
        return $crs_items;
    }

    /**
     * Returns a list of user ids that are members of a group.
     *
     * @param $grp_ref_id
     * @return array
     */
    public function getGroupMembers($grp_ref_id)
    {
        $a_userids = array();
        Libs\RESTilias::loadIlUser();
        Libs\RESTilias::initAccessHandling();

        $obj = \ilObjectFactory::getInstanceByRefId($grp_ref_id,false);
        if(!is_null($obj) && is_a($obj, 'ilObjGroup')) {
            $a_userids = $obj->getGroupMemberIds();
        }
        return $a_userids;
    }

    // TODO
    public function createGroup()
    {
    }

    // TODO
    public function deleteGroup($ref_id)
    {
    }
}
