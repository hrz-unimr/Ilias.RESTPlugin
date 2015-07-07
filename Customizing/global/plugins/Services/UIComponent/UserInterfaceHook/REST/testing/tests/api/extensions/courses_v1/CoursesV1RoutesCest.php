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

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/:ref_id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/:ref_id','DELETE');

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/enroll','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/join/:ref_id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/leave/:ref_id','GET');

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
        $postData = array('ref_id'=>'1', 'title' => 'API Testing Course', 'description' => 'Created by Codeception');
        $I->sendPOST('v1/courses',$postData);

        $this->course_id = $I->grabDataFromResponseByJsonPath('$.refId')[0];
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
     * @depends addCourse
     */
    public function joinCourse(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('join the authenticated user to the course');
        $I->sendGET('v1/courses/join/'.$this->course_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends joinCourse
     */
    public function leaveCourse(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('remove the authenticated user from the course');
        $I->sendGET('v1/courses/leave/'.$this->course_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends addCourse
     */
    public function enrollUserToCourse(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('enroll a testuser to a course');
        $postData = array('mode'=>'by_id', 'usr_id' => TestScenarios::$system_user_1_id, 'crs_ref_id' => $this->course_id);
        $I->sendPOST('v1/courses/enroll',$postData);

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