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


}