<?php
use \ApiTester;

/**
 * Class AdminFileRoutesCest
 * @group groups
 */
class AdminFileRoutesCest
{
    public $course_id="-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaUpCest.php');
        $scenario = new KalamariaUpCest();
        $scenario->createTestClient($I);
        $scenario->createSystemTestUsers($I);
        $scenario->createTestingCourse($I);

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/admin/files','POST');
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaDownCest.php');
        $scenario = new KalamariaDownCest();
        $scenario->removeTestUsers($I);
        $scenario->removeTestingCourse($I);
        $scenario->removeTestClient($I);
    }

    /**
     * List all groups of the authenticated user.
     */
    public function uploadFile(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('I want to upload  a file to test course 1');
        TestScenarios::admUploadFileToTestCourse1($I);
    }

}