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
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $obj_id = Libs\RESTLib::getObjIdFromRef($ref_id);
        $bibObj = new \ilObjBibliographic($obj_id);
        //$svyObj = new \ilObjSurvey($ref_id);
        $bib_info = array();
        $bib_info['ref_id'] = $ref_id;
        //$obj = \ilObjectFactory::getInstanceByRefId($svy_ref_id,false);
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
