<?php
use \ApiTester;

/**
 * Class GroupsV1RoutesCest
 * @group groups
 */
class UserFilesV1RoutesCest
{
    public $course_id="-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/thessaloniki/ThessalonikiUpCest.php');
        $scenario = new ThessalonikiUpCest();
        $scenario->createTestClient($I);
        $scenario->createSystemTestUsers($I);
        $scenario->createTestingCourse($I);
        $scenario->uploadFileToTestCourse1($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/files/:id','GET');
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/thessaloniki/ThessalonikiDownCest.php');
        $scenario = new ThessalonikiDownCest();
        $scenario->removeTestUsers($I);
        $scenario->removeTestingCourse($I);
        $scenario->removeTestClient($I);
    }

    /**
     * List all groups of the authenticated user.
     */
    public function downloadUserFile(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('download test file 1');
        TestScenarios::determineIdOfTestCourse1($I);
        TestScenarios::determineTestFile1Id($I);

        $I->sendGET('v1/files/'.TestScenarios::$test_file1_id.'?meta_data=1');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

}