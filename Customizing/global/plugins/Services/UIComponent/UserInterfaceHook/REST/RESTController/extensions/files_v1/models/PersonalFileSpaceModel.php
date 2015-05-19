<?php
/**
* ILIAS REST Plugin for the ILIAS LMS
*
* Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
* 2014-2015
*/
namespace RESTController\extensions\files_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \ilObject, \ilObjectFactory, \ilUtil, \ilFileUtils, \ilObjFileAccess, \ilObjFile, \ilChangeEvent, \ilRbacLog;


require_once("./Services/Database/classes/class.ilAuthContainerMDB2.php");
require_once("./Modules/File/classes/class.ilObjFile.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once("./Services/Object/classes/class.ilObjectFactory.php");

/**
 * Class PersonalFileSpaceModel
 * This class comprises methods that operate on files and personal workspaces.
 * @package RESTController\extensions\files_v1
 */
class PersonalFileSpaceModel {

    /**
     * Copies a file (object) from the personal file space to some container within the repository.
     * @param $user_id
     * @param $file_id (file or object id)
     * @param $ref_id_repository
     * @return true if copy action has been successful, false otherwise
     */
    public function clone_file_into_repository($user_id, $file_id, $ref_id_repository)
    {
        global    $ilUser;
        RESTLib::loadIlUser();
        $ilUser->setId($user_id);
        $ilUser->read();
        RESTLib::initAccessHandling();

        $source_object = ilObjectFactory::getInstanceByObjId($file_id);
        $owner_id = $source_object->getOwner();
        if ($owner_id != $user_id) {
            return false;
        }

        $newObject = $source_object->cloneObject($ref_id_repository);

        if ($newObject) {
            return true;
        }
        return false;
    }


    /**
     * Handles the upload of a single file and adds it to the user's "MyFileSpace".
     *
     * This code is inspired by FileModel->handleFileUpload()
     * NOTE: The function does not handle zip files yet.
     *
     * @param array $file_upload An array containing the file upload parameters of a single file.
     * @param int $user_id The reference id of a repository object where the uploaded file will be associated to.
     * @param int $owner_id The user_id of the owner of the file if available. Default: anonymous
     * @return object The response object.
     */
    function handleFileUploadIntoMyFileSpace($file_upload, $user_id, $owner_id = 13)
    {
        define("IL_VIRUS_SCANNER", "None");
        // The following constants are normally set by class.ilInitialisation.php->initClientInitFile()
        define ("MAXLENGTH_OBJ_TITLE",125);
        define ("MAXLENGTH_OBJ_DESC",123);

        require_once("./Services/Xml/classes/class.ilSaxParser.php");
        RESTLib::initGlobal("objDefinition", "ilObjectDefinition","./Services/Object/classes/class.ilObjectDefinition.php");
        RESTLib::initGlobal("ilAppEventHandler", "ilAppEventHandler","./Services/EventHandling/classes/class.ilAppEventHandler.php");
        RESTLib::initGlobal("ilObjDataCache", "ilObjectDataCache","./Services/Object/classes/class.ilObjectDataCache.php");
        RESTLib::loadIlUser();
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition, $ilAppEventHandler, $ilObjDataCache, $ilUser;

        // file upload params
        $filename = $file_upload["name"];
        $type = $file_upload["type"];
        $size = $file_upload["size"];
        $temp_name = $file_upload["tmp_name"];

        // additional params
        $title = $file_upload["title"];
        $description = $file_upload["description"];
        //$extract = $file_upload["extract"];
        //$keep_structure = $file_upload["keep_structure"];

        // create answer object
        $response = new \stdClass();
        $response->fileName = $filename;
        $response->fileSize = intval($size);
        $response->fileType = $type;
        //$response->fileUnzipped = $extract;
        $response->error = null;

        if (true) {
            if (trim($title) == "")
            {
                $title = $filename;
            }
            else
            {
                include_once("./Modules/File/classes/class.ilObjFileAccess.php");
                // BEGIN WebDAV: Ensure that object title ends with the filename extension
                $fileExtension = ilObjFileAccess::_getFileExtension($filename);
                $titleExtension = ilObjFileAccess::_getFileExtension($title);
                if ($titleExtension != $fileExtension && strlen($fileExtension) > 0)
                {
                    $title .= '.'.$fileExtension;
                }
                // END WebDAV: Ensure that object title ends with the filename extension
            }

            //var_dump($file_upload);
            //var_dump($title);

            // create and insert file in grp_tree
            include_once("./Modules/File/classes/class.ilObjFile.php");
            $fileObj = new ilObjFile();
            $fileObj->setOwner($owner_id);
            $fileObj->setTitle($title);
            $fileObj->setDescription($description);
            $fileObj->setFileName($filename);

            include_once("./Services/Utilities/classes/class.ilMimeTypeUtil.php");
            $fileObj->setFileType(\ilMimeTypeUtil::getMimeType("", $filename, $type));
            $fileObj->setFileSize($size);
            $object_id = $fileObj->create();
            //var_dump($fileObj);
            //$GLOBALS['ilLog']->write(__METHOD__.' Parent ID='.$this->parent_id);
            $this->putObjectInMyFileSpaceTree($fileObj, $user_id);

            // upload file to filesystem
            $fileObj->createDirectory();
            $fileObj->raiseUploadError(false);
            $fileObj->getUploadFile($temp_name, $filename, false);
        }

        return $response;
    }

    /**
     * Add (file) object to tuser's "MyFileSpace".
     * In this method the "MyFileSpace" is modeled as the workspace of a user.
     *
     * @param ilObject $a_obj
     * @param int $user_id
     */
    protected function putObjectInMyFileSpaceTree(ilObject $a_obj, $user_id)
    {
        RESTLib::initGlobal("rbacreview", "ilRbacReview", "./Services/AccessControl/classes/class.ilRbacReview.php");
        RESTLib::initGlobal("rbacadmin", "ilRbacAdmin", "./Services/AccessControl/classes/class.ilRbacAdmin.php");
        //ilInitialisation::initAccessHandling();
        global $rbacreview, $ilUser, $objDefinition;
        //global $ilLog;

        include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
        $tree = new\ ilWorkspaceTree($user_id);
        if(!$tree->getRootId())
        {
            $tree->createTreeForUser($user_id);
        }

        $obj_id = $a_obj->getId();
        $ref_id = $tree->insertObject($tree->getRootId(),$obj_id);
        //$a_obj->setPermissions($a_parent_node_id);

        // BEGIN ChangeEvent: Record save object.
        require_once('Services/Tracking/classes/class.ilChangeEvent.php');
        ilChangeEvent::_recordWriteEvent($obj_id, $user_id, 'create');
        // END ChangeEvent: Record save object.

        // rbac log
        include_once("Services/AccessControl/classes/class.ilRbacLog.php");
        $rbac_log_roles = $rbacreview->getParentRoleIds($ref_id, false);
        $rbac_log = ilRbacLog::gatherFaPa($ref_id, array_keys($rbac_log_roles), true);
        ilRbacLog::add(ilRbacLog::CREATE_OBJECT, $ref_id, $rbac_log);
    }


    public function deleteFromMyFileSpace($obj_id, $user_id)
    {
        include_once "Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php";
        $tree = new\ ilWorkspaceTree($user_id);
        $source_node_id = $tree->lookupNodeId($obj_id);
        //$parent_id = $this->tree->getParentId($source_node_id);
        $tree->deleteReference($source_node_id);
        $source_node = $tree->getNodeData($source_node_id);
        $tree->deleteTree($source_node);
        RESTLib::initAccessHandling();
        $source_object = ilObjectFactory::getInstanceByObjId($obj_id);
        $source_object->delete();
    }
}