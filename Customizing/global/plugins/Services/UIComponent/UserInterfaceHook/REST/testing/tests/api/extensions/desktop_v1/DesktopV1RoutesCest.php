<?php
use \ApiTester;

/**
 * Class DesktopV1RoutesCest
 * @group desktop
 */
class DesktopV1RoutesCest
{

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaUpCest.php');
        $scenario = new KalamariaUpCest();
        $scenario->createTestClient($I);
        $scenario->createSystemTestUsers($I);
        $scenario->createTestingCourse($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/desktop/overview','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/desktop/overview','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/desktop/overview','DELETE');
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
     * Adds a test course to the desktop of the authorized user
     */
    public function addTestCourseToDesktop(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('add test course to desktop');
        $data = array("ref_id" => TestScenarios::$course1_id);
        $I->sendPOST('v1/desktop/overview',$data);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends addTestCourseToDesktop
     */
    public function listDesktopItems(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('list all desktop items');
        $I->sendGET('v1/desktop/overview');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @param ApiTester $I
     * @depends addTestCourseToDesktop
     */
    public function removeTestCourseFromDesktop(ApiTester $I)
    {
        //$..ref_id
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        // Step 1: get ref_id of the test course
        $I->wantTo('remove the  test course to desktop');

        $I->sendGET('v1/desktop/overview');
        $data = $I->grabDataFromResponseByJsonPath('$.desktop.*');

        //\Codeception\Util\Debug::debug(print_r($data,true));//die();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['title']==TestScenarios::$course1_title) {
                TestScenarios::$course1_id = $data[$i]['ref_id'];
            }
        }
        \Codeception\Util\Debug::debug("Testcourse found : ".TestScenarios::$course1_id);//die();

        $pdata = array("ref_id" => TestScenarios::$course1_id);
        $I->sendDELETE('v1/desktop/overview',$data);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

}