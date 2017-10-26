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

require_once('Services/Utilities/classes/class.ilUtil.php');
require_once('Modules/TestQuestionPool/classes/class.ilObjQuestionPool.php');
require_once('Modules/TestQuestionPool/classes/class.assTextQuestion.php');
require_once('Modules/TestQuestionPool/classes/class.assSingleChoice.php');
require_once('Modules/TestQuestionPool/classes/class.assMultipleChoice.php');
require_once('Services/MediaObjects/classes/class.ilObjMediaObject.php');


class QuestionpoolModel extends Libs\RESTModel {

    /**
     * Returns questions of a questionpool
     * @param $ref_id
     * @param $user_id
     * @param $types
     * @return array
    */
    public function getQuestions($ref_id, $user_id, $types)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();
        $questionpool = new \ilObjQuestionPool($ref_id);
        $questionpool->read();
        $questions = $questionpool->getPrintviewQuestions();

        $filter = array();
        $result = array();

        //filter questions that match the provided types parameters
        if($types != '*' && $types != ''){
            $types = explode(',', $types);
            foreach($questions as $question){
                if(in_array($question['question_type_fi'], $types)){
                    array_push($filter, $question);
                }
            }
         }
         else {
            $filter = $questions;
         }

         //get media objects of each question
         foreach($filter as $question){
           $ilObjMediaObject = new \ilObjMediaObject();
           $mobs = $ilObjMediaObject->_getMobsOfObject("qpl:pg", $question['question_id']);
           
           $question['mediaObjects'] = [];
           foreach($mobs as $mob){
                $mediaobject = new \ilObjMediaObject($mob);
                $mobarray = [
                    "id" => $mob,
                    "mediaItems" => $mediaobject->media_items
                ];
                array_push($question['mediaObjects'], $mobarray);

           }


           array_push($result, $question);
         }

        return $result;
    }

    /**
     * Returns the answers for a question of type 8 = assTextQuestion
     * @param $ref_id
     * @param $user_id
     * @return array
    */
    public function getTextAnswers($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();

        $text_question = new \assTextQuestion();
        $text_question->loadFromDb($ref_id);
        
        $answers = array();
        foreach($text_question->answers as $answer){
           $result = array(
                'answertext' => $answer->getAnswertext(),
                'points' => $answer->getPoints()
            ); 
           array_push($answers, $result);
        }
        return $answers;
    }  

    /**
     * Returns the answer for a question of type 1 = assSingleChoice
     * @param $ref_id
     * @param $user_id
     * @return array
    */
    public function getSingleChoiceAnswers($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();

        $sc_question = new \assSingleChoice();
        $sc_question->loadFromDb($ref_id);

        $answers = array();
        foreach($sc_question->answers as $answer){
           $result = array(
                'answertext' => $answer->getAnswertext(),
                'points' => $answer->getPoints()
            ); 
           array_push($answers, $result);
        }
        return $answers;
    }

    /**
     * Returns the answer for a question of type 2 = assMultipleChoice
     * @param $ref_id
     * @param $user_id
     * @return array
    */
    public function getMultipleChoiceAnswers($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();

        $sc_question = new \assMultipleChoice();
        $sc_question->loadFromDb($ref_id);

        $answers = array();
        foreach($sc_question->answers as $answer){
           $result = array(
                'answertext' => $answer->getAnswertext(),
                'points' => $answer->getPoints()
            ); 
           array_push($answers, $result);
        }
        return $answers;
    }
}
