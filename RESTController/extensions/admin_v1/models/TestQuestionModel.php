<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;


require_once("./Modules/TestQuestionPool/classes/class.assSingleChoice.php");


class TestQuestionModel extends Libs\RESTModel
{

    /**
     * Retrieves a test question
     * @param $question_id
     * @return associative array
     */
    public function getQuestion($question_id)
    {
        $qst = new \assSingleChoice();
        $question = $qst::_instanciateQuestion($question_id);
        return json_decode($question->toJSON());
    }

}
?>
