<?php
use \ApiTester;

/**
 * Class UsersV1RoutesCest
 * @group users
 */
class UsersV1RoutesCest
{
    public $user_id="-1";

    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/irakleion/IrakleionUpCest.php');
        $scenario = new IrakleionUpCest();
        $scenario->createTestClient($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','PUT');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','DELETE');
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/irakleion/IrakleionDownCest.php');
        $scenario = new IrakleionDownCest();
        $scenario->removeTestClient($I);
    }

    public function addUser(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('create a new user');
        $postData = array('login'=>'api_test_users_v1', 'passwd' => 'api_check', 'firstname' => 'user_v1_firstname', 'lastname' => 'user_v1_lastname','email'=> 'api-testing@localhost', 'gender' => 'f');
        $I->sendPOST('v1/users',$postData);
        $this->user_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends addUser
     */
    public function modifyUser(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('modify a new user');
        $data = array('firstname' => 'user_v1_mod');
        $I->sendPUT('v1/users/'.$this->user_id,$data);
        //$this->user_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends modifyUser
     */
    public function getSingleUser(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('retrieve information about a single user');
        $I->sendGET('v1/users/'.$this->user_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends getSingleUser
     */
    public function getAllUsers(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('list all users');
        $I->sendGET('v1/users');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * @depends getAllUsers
     */
    public function deleteUser(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('delete a user');
        $I->sendDELETE('v1/users/'.$this->user_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}