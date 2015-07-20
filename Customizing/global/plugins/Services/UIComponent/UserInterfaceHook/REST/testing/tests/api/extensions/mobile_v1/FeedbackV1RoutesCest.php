<?php
use \ApiTester;

/**
 * Class FeedbackV1RoutesCest
 * @group mobile
 */
class FeedbackV1RoutesCest
{
    public $feedback1_id = "-1";
    public $feedback2_id = "-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/irakleion/IrakleionUpCest.php');
        $scenario = new IrakleionUpCest();
        $scenario->createTestClient($I);

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/feedbackinit','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/feedbackread/:id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/feedbackdrop','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/feedbackdrop','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/feedbackdel/:id','DELETE');
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/irakleion/IrakleionDownCest.php');
        $scenario = new IrakleionDownCest();
        $scenario->removeTestClient($I);
    }

    public function initFeedbackDB(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('create a feedback db');
        $I->sendGET('v1/m/feedbackinit');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends initFeedbackDB
     */
    public function sendFeedbackViaGET(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('modify a new user');
        $data = array('message' => 'Feedback via GET Test','env' => 'Codeception');
        $I->sendGET('v1/m/feedbackdrop',$data);
        $this->feedback1_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends initFeedbackDB
     */
    public function sendFeedbackViaPOST(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('modify a new user');
        $data = array('message' => 'Feedback via POST Test','env' => 'Codeception');
        $I->sendPOST('v1/m/feedbackdrop',$data);
        $this->feedback2_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends sendFeedbackViaGET
     */
    public function readFeedback1(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('retrieve feedback 1');
        $I->sendGET('v1/m/feedbackread/'.$this->feedback1_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }


    /**
     * @depends readFeedback1
     */
    public function deleteFeedback1(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('delete feedback entry 1');
        $I->sendDELETE('v1/m/feedbackdel/'.$this->feedback1_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends sendFeedbackViaPOST
     */
    public function deleteFeedback2(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('delete feedback entry 2');
        $I->sendDELETE('v1/m/feedbackdel/'.$this->feedback2_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}