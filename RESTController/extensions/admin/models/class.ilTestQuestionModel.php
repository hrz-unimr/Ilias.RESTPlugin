<?php
require_once "./Modules/TestQuestionPool/classes/class.assSingleChoice.php";

class ilTestQuestionModel
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