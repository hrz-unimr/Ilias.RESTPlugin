<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \assSingleChoice;


require_once("./Modules/TestQuestionPool/classes/class.assSingleChoice.php");


class TestQuestionModel
{

    /**
     * Retrieves a test question
     * @param $question_id
     * @return associative array
     */
    public function getQuestion($question_id)
    {
        $qst = new assSingleChoice();
        $question = $qst::_instanciateQuestion($question_id);
        return json_decode($question->toJSON());
    }

}
?>