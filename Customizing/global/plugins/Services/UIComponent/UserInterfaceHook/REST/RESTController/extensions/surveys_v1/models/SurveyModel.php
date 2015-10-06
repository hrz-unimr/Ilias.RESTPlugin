<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\surveys_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('./Services/User/classes/class.ilObjUser.php');
require_once('./Services/AccessControl/classes/class.ilRbacReview.php');
require_once('./Modules/Survey/classes/class.ilObjSurvey.php');

class SurveyModel extends Libs\RESTModel
{
    /**
     * Fills random answers for a user and survey specified by ref_id.
     * @param $ref_id - survey_id
     * @param $user_id
     */
    public function fillRandomAnswers($ref_id, $user_id){
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        //$srvObj = \ilObjectFactory::getInstanceByObjId($ref_id, false);
        $srvObj = new \ilObjSurvey($ref_id);
        $srvObj->fillSurveyForUser($user_id);
    }
}
