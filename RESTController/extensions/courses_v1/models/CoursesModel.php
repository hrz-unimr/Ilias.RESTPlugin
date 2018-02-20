<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('Services/Utilities/classes/class.ilUtil.php');
require_once('Modules/Course/classes/class.ilObjCourse.php');
require_once('Services/Object/classes/class.ilObjectFactory.php');
require_once('Services/Object/classes/class.ilObjectActivation.php');
require_once('Modules/LearningModule/classes/class.ilObjLearningModule.php');
require_once('Modules/LearningModule/classes/class.ilLMPageObject.php');


class CoursesModel extends Libs\RESTModel
{

    /**
     * This method lists all courses of a user that are visible and readable.
     *
     * @param $usr_id
     * @return an array of ref_ids
     */
    public function getCoursesOfUser($usr_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($usr_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
       // $list = ilUtil::getDataDir();
        $list = \ilUtil::_getObjectsByOperations('crs','visible,read',$usr_id); // returns ref_ids
        return $list;
    }

    /**
     * Retrieves all courses of a user.
     * @param $usr_id
     * @return array
     */
    public function getAllCourses($usr_id)
    {
        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($usr_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        $list = \ilUtil::_getObjectsByOperations('crs','visible,read'); // returns ref_ids
        foreach ($list as $id) {
            $result[] = array($this->getCourseInfo($id));
        }
        return $result;
    }

    /**
     * This method provides the online status of a course.
     *
     * @param $crs_ref_id
     * @return bool - true if course is online
     */
    public function getOnlineStatus($crs_ref_id)
    {
        $crs = new \ilObjCourse($crs_ref_id, true);
        $status = $crs->getOfflineStatus();
        return $status;
    }

    /**
     * This method delivers basic information such as title and description about a course.
     *
     * @param $crs_ref_id
     * @return array
     */
    public function getCourseInfo($crs_ref_id)
    {
        require_once('Services/Xml/classes/class.ilSaxParser.php');
        Libs\RESTilias::initGlobal('objDefinition', 'ilObjectDefinition','./Services/Object/classes/class.ilObjectDefinition.php');
        Libs\RESTilias::initGlobal('ilObjDataCache', 'ilObjectDataCache',
            './Services/Object/classes/class.ilObjectDataCache.php');
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition, $ilObjDataCache;

        $crs_info = array();
        $crs_info['ref_id'] = $crs_ref_id;
        $obj = \ilObjectFactory::getInstanceByRefId($crs_ref_id,false);
        if(is_null($obj)) {
            $crs_info['title'] = 'notFound';
        } else {
            $crs_info['title'] = $obj->getTitle();
            $crs_info['description'] = $obj->getDescription();
            $crs_info['create_date'] = $obj->create_date;
            $crs_info['type'] = $obj->getType();
        }
        //var_dump($obj);
        return $crs_info;
    }

    /**
     * Retrieves the content of a course as an array.
     *
     * @param $crs_ref_id
     * @return array
     */
    public function getCourseContent($crs_ref_id)
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
     * Returns a list of user ids that are members of a specific course.
     * Note: this method returns course members/participants only
     * if the setting "Show Memebers" is activated, i.e.
     * "If activated, course members can access the members gallery."
     *
     * @param $crs_ref_id
     * @param $include_tutors_and_admin - bool
     * @return array
     */
    public function getCourseMembers($crs_ref_id, $include_tutors_and_admin)
    {
        $a_userids = array();
        Libs\RESTilias::loadIlUser();
        Libs\RESTilias::initAccessHandling();

        $obj = \ilObjectFactory::getInstanceByRefId($crs_ref_id,false);
        if(!is_null($obj) && is_a($obj, 'ilObjCourse')) {
            if ($obj->getShowMembers() == true) {
                $mem_obj = $obj->getMembersObject();
                if ($include_tutors_and_admin == true) {
                    $a_userids = $mem_obj->getParticipants();
                } else {
                    $a_userids = $mem_obj->getMembers();
                }
            }

        }
        return $a_userids;
    }

    /**
     * Delivers a representation of an ILIAS Learning Module.
     *
     * @param $lm_ref_id
     * @return array
     */
    public function getDevILIASLMContent($lm_ref_id)
    {
        $lm_obj_id = \ilRESTUtils::refid_to_objid($lm_ref_id);
        $lm_data = array();
        $lm_data['lm_obj'] = $lm_obj_id;

        global $ilDB;
        $sql = Libs\RESTDatabase::safeSQL('SELECT * FROM page_object WHERE parent_id = %d', $lm_obj_id);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);
        /*while($row = $ilDB->fetchAssoc($res))//fetchObject($res))
         {
             $logins[] = $row->login;
         }
        */
        if (isset($row)){
             $lm_data['content'] = $row['content'];
        }
        return $lm_data;
     }

    /**
     * Delivers another representation of an ILIAS Learning Module.
     *
     * @param $lm_ref_id
     * @return array
     */
    public function getIliasLearnModule($lm_ref_id)
     {
         $lm_data = array();
         $lm = \ilObjectFactory::getInstanceByRefId($lm_ref_id,false);//new ilObjLearningModule($lm_ref_id, false);
         $lm_data['title'] = $lm->getTitle();
         $pages = \ilLMPageObject::getPageList($lm->getId());
         var_dump($pages[0]);
         $page = $pages[0];
         return $lm_data;
    }


    /**
     * Creates a course in the ILIAS repository. Does *not* check for permissions,
     * this has to be done at route level ($ilAccess->checkAccess()). The course
     * has only atitle and a description. Permissions are cloned from the parent.
     *
     * @param $parent_ref_id Ref ID of containing category
     * @param $title Title of new course
     * @param $desc Description of new course
     * @return Ref ID of newly created course, or 0 on error.
     */
    public function createNewCourse($parent_ref_id, $title, $desc)
    {

        include_once('Modules/Course/classes/class.ilObjCourse.php');

        $newObj = new \ilObjCourse();
        $newObj->setType('crs');
        $newObj->setTitle($title);
        $newObj->setDescription($desc);
        $newObj->create(true); // true for upload
        $newObj->createReference();
        $newObj->putInTree($parent_ref_id);
        $newObj->setPermissions($parent_ref_id);

        return $newObj->getRefId() ? $newObj->getRefId() : 0;
    }

    /**
     * Deletes a course reference.
     * @deprecated Note: this function uses SOAP, which might not be supported in the future.
     * @param $ref_id
     * @return array|mixed
     */
    public function deleteCourse($ref_id)
    {
        $adapter = new SoapAdapter();
        $success = $adapter->loginSOAP();
        if ($success == true) {
            $result = $adapter->executeSOAPFunction('deleteCourse', array($adapter->SID, $ref_id));
            $adapter->logoutSOAP();
            return $result;
        } else
        {
            return array('REST-Error' => 'Could not establish SOAP via REST connection: User unknown.');
        }
    }

    /*public function soapTest()
    {
        $adapter = new SoapAdapter();
        $adapter->loginSOAP();
        //echo $adapter->SID;
       // $result = $adapter->executeSOAPFunction('',array());
       // $result = $adapter->executeSOAPFunction('lookupUser',array($adapter->SID, 'root'));
       // $result = $adapter->executeSOAPFunction('getUser',array($adapter->SID, '6'));
        $result = $adapter->executeSOAPFunction('getCourseXML',array($adapter->SID, 60));
        $adapter->logoutSOAP();
        return $result;
    }*/

    /**
     * Returns a list of available xml export files to a course specified by ref_id.
     * @param $ref_id
     * @return mixed
     */
    public function listExportFiles($ref_id)
    {
        $types = array('xml');
        $obj_id = Libs\RESTilias::getObjId($ref_id);
        include_once('Services/Export/classes/class.ilExport.php');
        $files = \ilExport::_getExportFiles($obj_id, $types, "crs");
        return $files;
    }

    /**
     * Triggers the download of an export file.
     * @param $ref_id
     * @param $filename
     * see also determineLatestCourseExportFile
     */
    public function downloadExportFile($ref_id, $filename)
    {
        global $ilCtrl, $lng;

        $obj_id = Libs\RESTilias::getObjId($ref_id);
        include_once('Services/Export/classes/class.ilExport.php');
        $export_dir = \ilExport::_getExportDirectory($obj_id, $filename, "crs");
        $export_file = str_replace('export_','export/', $export_dir);
        self::getApp()->log->debug('DownloadExportFile '.$export_file);
        \ilUtil::deliverFile($export_file,
            $filename);
    }

    /**
     * Deletes an course export file (STUB)
     * @param $ref_id
     * @param $filename
     */
    public function deleteExportFile($ref_id, $filename)
    {
        // STUB
        // TODO c.f. class.ilExportGUI.php -> delete()
    }

    /**
     * Determines the latest export filename and will return null if there is no export file.
     * @param $ref_id
     * @return $filename
     */
    public function determineLatestCourseExportFile($ref_id)
    {
        $filename = null;
        $list = $this->listExportFiles($ref_id);
        // self::getApp()->log->debug('all export files '.print_r($list,true));
        if (count($list) == 0) return null;
        $maxts = 0;
        foreach ($list as $entry) {
            $currentTS = intval($entry['timestamp']);
            if ( $currentTS > $maxts) {
                $maxts = $currentTS;
                $filename = $entry['file'];
            }
        }
        return $filename;
    }

    /**
     * Creates a new coures export file fo a course specified by its ref_id.
     * This action corresponds to the GUI version: Export > Create Export File (XML)
     *
     * @param $ref_id
     * @return an array with a success indicator in it
     */
    public function createNewCourseExportFile($ref_id)
    {
        $success = false;
        $obj_id = Libs\RESTilias::getObjId($ref_id);

        include_once('Services/Export/classes/class.ilExportOptions.php');
        $eo = \ilExportOptions::newInstance(\ilExportOptions::allocateExportId());
        $eo->addOption(\ilExportOptions::KEY_ROOT,0,0,$obj_id);

        // TODO: Further improve it by simulating user choices. To this end
        // the ExportOption data structure must be filled by traversing over the course contents.
        // c.f. Services/Export/classses/class.ilExportGUI.php

        include_once('Services/Export/classes/class.ilExport.php');
        $exp = new \ilExport();
        return $exp->exportObject("crs",$obj_id, "5.0.0");
    }

}
