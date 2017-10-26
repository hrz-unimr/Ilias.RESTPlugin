<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\files_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('./Services/Database/classes/class.ilAuthContainerMDB2.php');
require_once('./Modules/File/classes/class.ilObjFile.php');
require_once('./Services/User/classes/class.ilObjUser.php');

class FileModel extends Libs\RESTModel
{

    /**
     * Returns the file object for a user. The function checks it the specified user has the appropriate access permissions.
     * @param $file_obj_id
     * @param $user_id
     * @return array|object
     */
    function getFileObjForUser($file_obj_id, $user_id)
    {

        Libs\RESTilias::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        require_once('./Services/Xml/classes/class.ilSaxParser.php');
        Libs\RESTilias::initGlobal('objDefinition', 'ilObjectDefinition','./Services/Object/classes/class.ilObjectDefinition.php');
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition;
        global $ilAccess;

        // Check access
        $permission_ok = false;
        foreach($ref_ids = \ilObject::_getAllReferences($file_obj_id) as $ref_id)
        {
            if($ilAccess->checkAccess('read','',$ref_id))
            {
                $permission_ok = true;
                break;
            }
        }

        if ($permission_ok==false) {
            return array();
        }

        $fileObj=  \ilObjectFactory::getInstanceByObjId($file_obj_id);
        return $fileObj;
    }

    /**
     * Returns the file object associated with the obj_id (=file_id).
     * @param $obj_id
     * @return object
     */
    function getFileObj($obj_id)
    {
        //        //global $ilDB;
        require_once('./Services/Xml/classes/class.ilSaxParser.php');
        Libs\RESTilias::initGlobal('objDefinition', 'ilObjectDefinition','./Services/Object/classes/class.ilObjectDefinition.php');
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition;
        $fileObj=  \ilObjectFactory::getInstanceByObjId($obj_id);

        return $fileObj;
    }

    /**
     * Handles the upload of a single file and adds it to the repository object specified by ref_id.
     *
     * This code is inspired by class.ilObjFileGUI->handleFileUpload().
     * NOTE: The function does not handle zip files yet.
     *
     * @param array $file_upload An array containing the file upload parameters of a single file.
     * @param int $ref_id The reference id of a repository object where the uploaded file will be associated to.
     * @param int $owner_id The user_id of the owner of the file if available. Default: anonymous
     * @return object The response object.
     */
    function handleFileUpload($file_upload, $ref_id, $owner_id = 13)
    {
        define('IL_VIRUS_SCANNER', 'None');
        // The following constants are normally set by class.ilInitialisation.php->initClientInitFile()
        define ('MAXLENGTH_OBJ_TITLE',125);
        define ('MAXLENGTH_OBJ_DESC',123);

        require_once('./Services/Xml/classes/class.ilSaxParser.php');
        Libs\RESTilias::initGlobal('objDefinition', 'ilObjectDefinition','./Services/Object/classes/class.ilObjectDefinition.php');
        Libs\RESTilias::initGlobal('ilAppEventHandler', 'ilAppEventHandler','./Services/EventHandling/classes/class.ilAppEventHandler.php');
        Libs\RESTilias::initGlobal('ilObjDataCache', 'ilObjectDataCache','./Services/Object/classes/class.ilObjectDataCache.php');
        Libs\RESTilias::loadIlUser();
        global $ilDB, $ilias, $ilPluginAdmin, $objDefinition, $ilAppEventHandler, $ilObjDataCache, $ilUser;

        // file upload params
        $filename = $file_upload['name'];
        $type = $file_upload['type'];
        $size = $file_upload['size'];
        $temp_name = $file_upload['tmp_name'];

        // additional params
        $title = $file_upload['title'];
        $description = $file_upload['description'];
        //$extract = $file_upload['extract'];
        //$keep_structure = $file_upload['keep_structure'];

        // create answer object
        $response = new \stdClass();
        $response->fileName = $filename;
        $response->fileSize = intval($size);
        $response->fileType = $type;
        //$response->fileUnzipped = $extract;
        $response->error = null;

        // extract archive?
       /* if ($extract)
        {
            $zip_file = $filename;
            $adopt_structure = $keep_structure;

            include_once('Services/Utilities/classes/class.ilFileUtils.php');

            // Create unzip-directory
            $newDir = \ilUtil::ilTempnam();
            \ilUtil::makeDir($newDir);

            // Check if permission is granted for creation of object, if necessary
            if($this->id_type != self::WORKSPACE_NODE_ID)
            {
                $type = \ilObject::_lookupType((int)$this->parent_id, true);
            }
            else
            {
                $type = \ilObject::_lookupType($this->tree->lookupObjectId($this->parent_id), false);
            }

            $tree = $access_handler = null;
            switch($type)
            {
                // workspace structure
                case 'wfld':
                case 'wsrt':
                    $permission = $this->checkPermissionBool('create', '', 'wfld');
                    $containerType = 'WorkspaceFolder';
                    $tree = $this->tree;
                    $access_handler = $this->getAccessHandler();
                    break;

                // use categories as structure
                case 'cat':
                case 'root':
                    $permission = $this->checkPermissionBool('create', '', 'cat');
                    $containerType = 'Category';
                    break;

                // use folders as structure (in courses)
                default:
                    $permission = $this->checkPermissionBool('create', '', 'fold');
                    $containerType = 'Folder';
                    break;
            }

            try
            {
                //     processZipFile (
                //        Dir to unzip,
                //        Path to uploaded file,
                //        should a structure be created (+ permission check)?
                //        ref_id of parent
                //        object that contains files (folder or category)
                //        should sendInfo be persistent?)
                \ilFileUtils::processZipFile(
                    $newDir,
                    $temp_name,
                    ($adopt_structure && $permission),
                    $this->parent_id,
                    $containerType,
                    $tree,
                    $access_handler);
            }
            catch (\ilFileUtilsException $e)
            {
                $response->error = $e->getMessage();
            }
            catch (\Exception $ex)
            {
                $response->error = $ex->getMessage();
            }

            \ilUtil::delDir($newDir);
        }
        else
       */
        if (true) {
            if (trim($title) == '')
            {
                $title = $filename;
            }
            else
            {
                include_once('./Modules/File/classes/class.ilObjFileAccess.php');
                // BEGIN WebDAV: Ensure that object title ends with the filename extension
                $fileExtension = \ilObjFileAccess::_getFileExtension($filename);
                $titleExtension = \ilObjFileAccess::_getFileExtension($title);
                if ($titleExtension != $fileExtension && strlen($fileExtension) > 0)
                {
                    $title .= '.'.$fileExtension;
                }
                // END WebDAV: Ensure that object title ends with the filename extension
            }

            //var_dump($file_upload);
            //var_dump($title);

            // create and insert file in grp_tree
            include_once('./Modules/File/classes/class.ilObjFile.php');
            $fileObj = new \ilObjFile();
            $fileObj->setOwner($owner_id);
            $fileObj->setTitle($title);
            $fileObj->setDescription($description);
            $fileObj->setFileName($filename);

            include_once('./Services/Utilities/classes/class.ilMimeTypeUtil.php');
            $fileObj->setFileType(\ilMimeTypeUtil::getMimeType('', $filename, $type));
            $fileObj->setFileSize($size);
            $object_id = $fileObj->create();
            //var_dump($fileObj);
            //$GLOBALS['ilLog']->write(__METHOD__.' Parent ID='.$this->parent_id);
            $this->putObjectInTree($fileObj, $ref_id);

            // upload file to filesystem
            $fileObj->createDirectory();
            $fileObj->raiseUploadError(false);
            $fileObj->getUploadFile($temp_name, $filename, false);

            //$this->handleAutoRating($fileObj);

        }

        return $response;
    }

    /**
     * Add object to tree at given position
     *
     * NOTE: Taken from Services/object/classes/class.ilObjectGUI.php needed for handleFileUpload
     *
     * @param ilObject $a_obj
     * @param int $a_parent_node_id
     */
    protected function putObjectInTree(\ilObject $a_obj, $a_parent_node_id = null)
    {
        Libs\RESTilias::initGlobal('rbacreview', 'ilRbacReview', './Services/AccessControl/classes/class.ilRbacReview.php');
        Libs\RESTilias::initGlobal('rbacadmin', 'ilRbacAdmin', './Services/AccessControl/classes/class.ilRbacAdmin.php');
        //ilInitialisation::initAccessHandling();
        global $rbacreview, $ilUser, $objDefinition;

        $a_obj->createReference();
        $a_obj->putInTree($a_parent_node_id);
        $a_obj->setPermissions($a_parent_node_id);

        $obj_id = $a_obj->getId();
        $ref_id = $a_obj->getRefId();

        // BEGIN ChangeEvent: Record save object.
        require_once('Services/Tracking/classes/class.ilChangeEvent.php');
        \ilChangeEvent::_recordWriteEvent($this->obj_id, $ilUser->getId(), 'create');
        // END ChangeEvent: Record save object.

        // rbac log
        include_once('Services/AccessControl/classes/class.ilRbacLog.php');
        $rbac_log_roles = $rbacreview->getParentRoleIds($ref_id, false);
        $rbac_log = \ilRbacLog::gatherFaPa($ref_id, array_keys($rbac_log_roles), true);
        \ilRbacLog::add(\ilRbacLog::CREATE_OBJECT, $ref_id, $rbac_log);
    }

}
