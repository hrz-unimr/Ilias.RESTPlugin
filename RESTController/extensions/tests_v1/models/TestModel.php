<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\tests_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;

require_once('Services/Utilities/classes/class.ilUtil.php');
require_once('Modules/Test/classes/class.ilObjTest.php');
require_once('Services/MediaObjects/classes/class.ilObjMediaObject.php');

class TestModel extends Libs\RESTModel {

    /**
     * Downloads a zipped version of a test.
     * @param $ref_id - reference id of the test
     * @param $user_id - a user's id
     */
    public function downloadTestExportFile($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();
        $test = new \ilObjTest($ref_id);
        $test->read();
        $xml = $test->getXMLZip();   // "create ZIP and return path"
        \ilUtil::deliverFile($xml,
            basename($xml));
    }

    /**
     * Returns a list of participants.
     * @param $ref_id
     * @param $user_id
     * @return array
     */
    public function getTestParticipants($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();
        $test = new \ilObjTest($ref_id);
        $test->read();
        $participants = $test->getParticipants();
        return $participants;
    }

    /**
     * Returns some basic information about a test.
     * @param $ref_id
     * @param $user_id
     * @return array
     */
    public function getBasicInformation($ref_id, $user_id)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();
        $test = new \ilObjTest($ref_id);
        $test->read();
        return array("title"=>$test->getTitle(),"description"=>$test->getDescription(),"type"=>$type = $test->getType());
    }

    /**
     * Returns all questions of a test.
     * @param $ref_id
     * @param $user_id
     * @param $types
     * @return array
     */
    public function getQuestions($ref_id, $user_id, $types)
    {
        Libs\RESTilias::loadIlUser($user_id);
        Libs\RESTilias::initAccessHandling();
        $test = new \ilObjTest($ref_id);
        $questions = $test->getAllQuestions();

        $filter = array();
        $result = array();

        //filter questions that match the provided types parameter
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
}
