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
require_once('./Modules/SurveyQuestionPool/classes/class.SurveyMultipleChoiceQuestion.php');
require_once('./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php');

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
        $svyObj = new \ilObjSurvey($ref_id);
        $svyObj->fillSurveyForUser($user_id);
    }

    /**
     * Creates a JSON representation of a survey.
     * @param $ref_id
     * @param $user_id
     * @return array
     */
    public function getJsonRepresentation($ref_id, $user_id) {
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $svyObj = new \ilObjSurvey($ref_id);
        $pages =& $svyObj->getSurveyPages();
        $result = array();
        $res_questions = array();
        $cnt = 1;
        foreach ($pages as $key => $question_array)
        {
            foreach ($question_array as $question)
            {
                // instanciate question
                require_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
                $question =&  \SurveyQuestion::_instanciateQuestion($question["question_id"]);
                //(SurveyMultipleChoiceQuestion)
                //$question = (\SurveyMultipleChoiceQuestion)$question;
                $q = array();
                $q['id'] = $question->getId();
                $q['title'] = $question->getTitle();
                $q['description'] = $question->getDescription();
                $q['questiontext'] = $question->getQuestiontext();
                $q['type'] = $question->getQuestionType();
                if ($q['type'] == "SurveyMultipleChoiceQuestion" || $q['type']== "SurveySingleChoiceQuestion") {
                    $cats = $question->getCategories();
                    $nCats = $cats->getCategoryCount();
                    $res_cats = array();
                    for ($i = 0; $i < $nCats; $i++) {
                        $cat =$cats->getCategory($i);
                        $res_cats[] = $cat->title;
                    }
                    $q['categories'] = $res_cats;
                }

                //$cat =  $question->getCatories();
                // getQuestionType, getQuestionTypeID
                $res_questions[$cnt++] = $q;

            }
        }
        return $res_questions;
        //$result['questions'] = $res_questions;
        //return $result;
    }

    /**
     * Returns the answers of a user for a survey.
     * @param $ref_id - survey id
     * @param $user_id
     * @return array
     */
    public function getSurveyResultsOfUser($ref_id, $user_id) {
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $svyObj = new \ilObjSurvey($ref_id);
        $ids = $this->getFinishIdOfUser($ref_id, $user_id);
        $data = $svyObj->getUserSpecificResults($ids);
        return $data;
    }

    /**
     * Returns the "finishID" of a particular user.
     * @param $ref_id
     * @param $user_id
     * @return array
     */
    private function getFinishIdOfUser($ref_id, $user_id) {
        global $ilDB;
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $svyObj = new \ilObjSurvey($ref_id);

        // gather participants who already finished
        $finished_ids = array();
        $set = $ilDB->query("SELECT finished_id FROM svy_finished".
            " WHERE survey_fi = ".$ilDB->quote($svyObj->getSurveyId(), "integer").
            " AND state = ".$ilDB->quote(1, "text").
            " AND user_fi= ". $user_id);
        while($row = $ilDB->fetchAssoc($set))
        {
            $finished_ids[] = $row["finished_id"];
        }
        return $finished_ids;
    }

    /**
     * @param $ref_id
     * @param $user_id
     * @return active_id (same as finish_id)
     */
    public function beginSurvey($ref_id, $user_id){
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $svyObj = new \ilObjSurvey($ref_id);
        $id = $svyObj->startSurvey($user_id,"",0);
        //$active_id = $ilDB->nextId('svy_finished');
        return $id; // =finish_id=active_id?
    }

    /**
     * @param $ref_id
     * @param $user_id
     * @param $active_id
     * @param $question_id
     * @param $answerdata - array of numbers (type string) - they denote the answer id(+1)
     */
    public function saveQuestionAnswer($ref_id, $user_id, $active_id, $question_id, $answer_csv) {
        // convert answer_data  to $post_data format
        $answers = explode(',',$answer_csv);
        for ($i=0;$i<count($answers);$i++) {

        } // TODO
        $post_data = array($question_id.'_value'=>'1',$question_id.'_1_other'=>'text answer');

        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $svyObj = new \ilObjSurvey($ref_id);
        $pages =& $svyObj->getSurveyPages();
        $result = array();
        $res_questions = array();
        $cnt = 1;
        foreach ($pages as $key => $question_array)
        {
            foreach ($question_array as $question)
            {
                // instanciate question
                require_once "./Modules/SurveyQuestionPool/classes/class.SurveyQuestion.php";
                $question =&  \SurveyQuestion::_instanciateQuestion($question["question_id"]);
                if ($question->getId() == $question_id) {
                    $question->saveUserInput($post_data, $active_id,  false);
                }
            }
        }
    }

    /**
     * @param $ref_id
     * @param $user_id
     * @param $finish_id
     */
    public function finishSurvey($ref_id, $user_id, $finish_id){
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $svyObj = new \ilObjSurvey($ref_id);
        $svyObj->finishSurvey($finish_id);
    }


}
