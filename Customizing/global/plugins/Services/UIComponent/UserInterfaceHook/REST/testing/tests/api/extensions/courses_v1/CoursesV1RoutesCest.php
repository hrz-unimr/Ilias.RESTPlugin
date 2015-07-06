<?php
use \ApiTester;

/**
 * Class CoursesV1RoutesCest
 * @group courses
 */
class CoursesV1RoutesCest
{
    public $course_id="-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/peristerion/PeristerionUpCest.php');
        $scenario = new PeristerionUpCest();
        $scenario->createTestClient($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','PUT');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','DELETE');
        $scenario->createSystemTestUsers($I);
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/peristerion/PeristerionDownCest.php');
        $scenario = new PeristerionDownCest();
        $scenario->removeTestUsers($I);
        $scenario->removeTestClient($I);
    }

    public function addCourse(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('create a new course');
        //$postData = array('login'=>'api_test_users_v1', 'passwd' => 'api_check', 'firstname' => 'user_v1_firstname', 'lastname' => 'user_v1_lastname','email'=> 'api-testing@localhost', 'gender' => 'f');
       // $I->sendPOST('v1/courses',$postData);
        $this->user_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends addCourse
     */
    public function getSingleCourse(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('retrieve information about a specific course');
        $I->sendGET('v1/courses/'.$this->course_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends getSingleCourse
     */
    public function deleteCourse(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('delete a course');
        $I->sendDELETE('v1/courses/'.$this->course_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}