<?php
use \ApiTester;

/**
 * Class MyFilespaceV1RoutesCest
 * @group mobile
 */
class MyFilespaceV1RoutesCest
{
    public $file_id = "-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaUpCest.php');
        $scenario = new KalamariaUpCest();
        $scenario->createTestClient($I);
        $scenario->createSystemTestUsers($I);
        $scenario->createTestingCourse($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/myfilespaceupload','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/myfilespace','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/myfilespacecopy','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/myfilespacedelete','DELETE');
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
    public function uploadToPersonalFileSpace(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $this->file_id = TestScenarios::admUploadFileToPersonalFileSpace($I);
    }

    /**
     * @depends uploadToPersonalFileSpace
     */
    public function getMyFileSpaceListing(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('get myfilespace listing');
        $I->sendGET('v1/m/myfilespace');
        $I->seeResponseContainsJson(array('status' => 'success'));

    }

    /**
     * @depends uploadToPersonalFileSpace
     */
    public function moveFileFromPersonalDesktopToTestCourse1(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('copy a file from myfilespace to course1');
        $target_ref_id = TestScenarios::$course1_id;
        $postData = array('file_id'=> $this->file_id, 'target_ref_id'=> $target_ref_id);
        $I->sendPOST('v1/m/myfilespacecopy',$postData);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends uploadToPersonalFileSpace
     */
    public function deleteFileFromPersonalDesktop(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('Remove a file from myfilespace.');
        $postData = array('file_id'=> $this->file_id);
        $I->sendDELETE('v1/m/myfilespacedelete',$postData);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

}