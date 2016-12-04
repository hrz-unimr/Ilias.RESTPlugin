<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\questionpools_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;

require_once('./Services/Utilities/classes/class.ilUtil.php');
require_once('./Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php');

class QuestionpoolModel extends Libs\RESTModel {

    /**
     * Returns all questions of a test
     * @param $ref_id
     * @param $user_id
     * @return array
    */
    public function getQuestions($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();
        $test = new \ilObjQuestionPool($ref_id);
        $test->read();
        $questions = $test->getPrintviewQuestions();
        return $questions;
    }
    
}
