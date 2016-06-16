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

require_once('./Services/Utilities/classes/class.ilUtil.php');
require_once('./Modules/Test/classes/class.ilObjTest.php');

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
}
