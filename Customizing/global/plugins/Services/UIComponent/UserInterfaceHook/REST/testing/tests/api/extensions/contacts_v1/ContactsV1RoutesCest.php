<?php
use \ApiTester;

/**
 * Class ContactsV1RoutesCest
 * @group courses
 */
class ContactsV1RoutesCest
{

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/peristerion/PeristerionUpCest.php');
        $scenario = new PeristerionUpCest();
        $scenario->createTestClient($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/contacts/:id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/contacts','GET');
        $scenario->createSystemTestUsers($I);
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/peristerion/PeristerionDownCest.php');
        $scenario = new PeristerionDownCest();
        $scenario->removeTestUsers($I);
        $scenario->removeTestClient($I);
    }

    /**
     *  Returns contacts of the authorized user
     */
    public function getContacts(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('returns contacts of the authorized user');
        $I->sendGET('v1/contacts');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * Returns the contacts of a user. The requester must have admin permissions to call this endpoint.
     */
    public function getContactsOfTestuser(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('join the authenticated user to the course');
        $I->sendGET('v1/contacts/'.TestScenarios::$system_user_1_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}