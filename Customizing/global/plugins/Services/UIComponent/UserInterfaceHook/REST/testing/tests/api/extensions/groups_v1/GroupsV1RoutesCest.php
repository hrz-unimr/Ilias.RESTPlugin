<?php
use \ApiTester;

/**
 * Class GroupsV1RoutesCest
 * @group groups
 */
class GroupsV1RoutesCest
{
    public $course_id="-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaUpCest.php');
        $scenario = new KalamariaUpCest();
        $scenario->createTestClient($I);
        $scenario->createSystemTestUsers($I);
        $scenario->createTestingCourse($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/groups4user','GET');
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
    public function getGroupsForAuthenticatedUser(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('get groups for auth user');
        $I->sendGET('v1/groups4user');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

}