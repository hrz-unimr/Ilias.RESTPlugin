<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\bibliography_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once("./Services/Database/classes/class.ilAuthContainerMDB2.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once('./Modules/Bibliographic/classes/class.ilObjBibliographic.php');


class BibliographyModel
{
    function getBibliography($ref_id, $user_id) {
        global $ilAccess;
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $obj_id = Libs\RESTLib::getObjIdFromRef($ref_id);

        // Check access rights
        if (!(($ilAccess->checkAccess('read', "", $ref_id) )
            || $ilAccess->checkAccess('write', "", $ref_id))
        ) {
            throw new Libs\Exceptions\ReadFailed("No r/w access.",$ref_id, "biblio", -15);
        }

        $bibObj = new \ilObjBibliographic($obj_id);

        if ($bibObj->getOnline() == false) {
            throw new Libs\Exceptions\ReadFailed("Object is not online.",$ref_id, "biblio", -17);
        }

        $bib_info = array();
        $bib_info['ref_id'] = $ref_id;
        if(is_null($bibObj)) {
            $bib_info['title'] = 'notFound';
        } else {
            $bib_info['title'] = $bibObj->getTitle();
            $bib_info['description'] = $bibObj->getDescription();
            $bib_info['create_date'] = $bibObj->create_date;
            $bib_info['type'] = $bibObj->getType();
        }
        //var_dump($bibObj);
        return $bib_info;

    }
}
