<?php
require_once "./Services/Utilities/classes/class.ilUtil.php";
require_once "./Modules/Course/classes/class.ilObjCourse.php";
require_once './Services/Object/classes/class.ilObjectFactory.php';
require_once './Services/Object/classes/class.ilObjectActivation.php';
require_once("./Modules/LearningModule/classes/class.ilObjLearningModule.php");
require_once("./Modules/LearningModule/classes/class.ilLMPageObject.php");
require_once "./Services/Database/classes/class.ilDB.php";
require_once "./Services/Database/classes/class.ilAuthContainerMDB2.php";


class ilCoursesModel
{

    /**
     * This method lists all courses of a user that are visible and readable.
     *
     * @param $usr_id
     * @return an array of ref_ids
     */
    public function getCoursesOfUser($usr_id)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($usr_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();
       // $list = ilUtil::getDataDir();
        $list = ilUtil::_getObjectsByOperations("crs","visible,read",$usr_id); // returns ref_ids
        return $list;
    }

    /**
     * This method provides the online status of a course.
     *
     * @param $crs_ref_id
     * @return bool - true if course is online
     */
    public function getOnlineStatus($crs_ref_id)
    {
        $crs = new ilObjCourse($crs_ref_id, true);
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
        require_once("./Services/Xml/classes/class.ilSaxParser.php");
        ilRestLib::initGlobal("ilias", "ILIAS", "./Services/Init/classes/class.ilias.php");
        ilRestLib::initGlobal("ilPluginAdmin", "ilPluginAdmin","./Services/Component/classes/class.ilPluginAdmin.php");
        ilRestLib::initGlobal("objDefinition", "ilObjectDefinition","./Services/Object/classes/class.ilObjectDefinition.php");
        ilRestLib::initGlobal("ilObjDataCache", "ilObjectDataCache",
            "./Services/Object/classes/class.ilObjectDataCache.php");
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition, $ilObjDataCache;
        define("DEBUG", FALSE);

        $crs_info = array();
        $crs_info['ref_id'] = $crs_ref_id;
        $obj = ilObjectFactory::getInstanceByRefId($crs_ref_id,false);
        $crs_info['title'] = $obj->getTitle();
        $crs_info['description'] = $obj->getDescription();
        $crs_info['create_date'] = $obj->create_date;
        $crs_info['type'] = $obj->getType();
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

        require_once("./Services/Xml/classes/class.ilSaxParser.php");
        ilRestLib::initGlobal("ilias", "ILIAS", "./Services/Init/classes/class.ilias.php");
        ilRestLib::initGlobal("ilPluginAdmin", "ilPluginAdmin","./Services/Component/classes/class.ilPluginAdmin.php");
        ilRestLib::initGlobal("objDefinition", "ilObjectDefinition","./Services/Object/classes/class.ilObjectDefinition.php");
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition;
        define("DEBUG", FALSE);

        if(!$lng)
        {
            $lang = "en";
            require_once "./Services/Language/classes/class.ilLanguage.php";
            $lng = new ilLanguage($lang);
            $lng->loadLanguageModule("init");
            ilRestLib::initGlobal('lng', $lng);
        }


        $crs_items = array();

        $sorted_items = ilObjectActivation::getTimingsItems($crs_ref_id);

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
     * Delivers a representation of an ILIAS Learning Module.
     *
     * @param $lm_ref_id
     * @return array
     */
    public function getDevILIASLMContent($lm_ref_id)
    {
        $lm_obj_id = ilRestUtils::refid_to_objid($lm_ref_id);
        $lm_data = array();
        $lm_data['lm_obj'] = $lm_obj_id;

        global $ilDB;
        $query="SELECT * FROM page_object WHERE parent_id=".$lm_obj_id;
        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);
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
         $lm = ilObjectFactory::getInstanceByRefId($lm_ref_id,false);//new ilObjLearningModule($lm_ref_id, false);
         $lm_data['title'] = $lm->getTitle();
         $pages = ilLMPageObject::getPageList($lm->getId());
         var_dump($pages[0]);
         $page = $pages[0];
         return $lm_data;
    }


    public function createNewCourseAsUser($user_id, $parent_ref_id, $title, $desc)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        ilRestLib::initAccessHandling();
        //$this->createNewCourse($parent_ref_id, $title, $desc);


        include_once("Modules/Course/classes/class.ilObjCourse.php");

        $newObj = new ilObjCourse();
        $newObj->setType('crs');
        $newObj->setTitle($title);
        $newObj->setDescription($desc);
        $newObj->create(true); // true for upload
        $newObj->createReference();
        $newObj->putInTree($parent_ref_id);
        $newObj->setPermissions($parent_ref_id);

        return $newObj->getRefId() ? $newObj->getRefId() : "0";

    }

    /**
     * This methods creates a new course object within the repository.
     *
     * @param $parent_ref_id
     * @param $title
     * @param $desc
     * @return mixed
     */
  /*  public function createNewCourse($parent_ref_id, $title, $desc)
    {
        //$class_name = "ilObj".$objDefinition->getClassName($new_type);
        //$location = $objDefinition->getLocation($new_type);
        //include_once($location."/class.".$class_name.".php");
        $new_type = "crs";
        $class_name = "ilObjCourse";
        $newObj = new $class_name();
        $newObj->setType($new_type);
        $newObj->setTitle($title);
        $newObj->setDescription($desc);
        $newObj->create();

        //$this->putObjectInTree($newObj);
        return $newObj->getId();
    }
*/

    public function deleteCourse($ref_id)
    {
        $adapter = new ilRestSoapAdapter();
        $success = $adapter->loginSOAP();
        if ($success == true) {
            $result = $adapter->executeSOAPFunction("deleteCourse", array($adapter->SID, $ref_id));
            $adapter->logoutSOAP();
            return $result;
        } else
        {
            return array("REST-Error" => "Could not establish SOAP via REST connection: User unknown.");
        }
    }


    public function soapTest()
    {
        $adapter = new ilRestSoapAdapter();
        $adapter->loginSOAP();
        //echo $adapter->SID;
       // $result = $adapter->executeSOAPFunction("",array());
       // $result = $adapter->executeSOAPFunction("lookupUser",array($adapter->SID, "root"));
       // $result = $adapter->executeSOAPFunction("getUser",array($adapter->SID, "6"));
        $result = $adapter->executeSOAPFunction("getCourseXML",array($adapter->SID, 60));


        $adapter->logoutSOAP();
        return $result;
    }

}
