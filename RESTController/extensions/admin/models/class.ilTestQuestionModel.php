<?php
require_once "./Modules/TestQuestionPool/classes/class.assSingleChoice.php";

class ilTestQuestionModel
{

    public function getQuestion($question_id)
    {
        $qst = new assSingleChoice();
        $question = $qst::_instanciateQuestion($question_id);
        return json_decode($question->toJSON());
    }

}
?>