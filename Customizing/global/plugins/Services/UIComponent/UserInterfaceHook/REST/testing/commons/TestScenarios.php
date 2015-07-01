<?php
class TestScenarios
{
    /* A new rest client / api-key for testing */
    public static $test_client_id = "-1";
    public static $test_api_key = 'testing';
    public static $test_api_secret = 'testing';

    /* A system user that performs API requests with the above specified api key*/
    public static $test_username = "zeus";
    public static $test_user_password = "athen";
    public static $test_user_token = "";
    public static $test_user_id = "-1";

    /* State variables */
    public static $isAdminLoggedIn = false;

    /* System users */
    public static $system_user_1_id = -1;
    public static $system_user_2_id = -1;
    public static $system_user_1_login = 'hero';
    public static $system_user_2_login = 'leander';

    /**
     * Log in as admin/apollon.
     * @param $I
     */
    protected static function adminLogin($I)
    {
        if (TestScenarios::$isAdminLoggedIn == false) {
            TestCommons::logMeIn($I);
            TestScenarios::$isAdminLoggedIn = true;
        }
    }

    /**
     * This function enables a login using a new client and new user.
     * Therefore this method depends on admCreateTestApiClient and createTestUser.
     *
     * @param $I
     */
    public static function testerLogin($I)
    {
        $I->wantTo('authenticate via oauth2 user credentials');
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $aPost = array('grant_type' => 'password',
            'username' => TestScenarios::$test_username,
            'password' => TestScenarios::$test_user_password,
            'api_key' => TestScenarios::$test_api_key);
        $I->sendPOST('v1/oauth2/token',$aPost);
        TestScenarios::$test_user_token = $I->grabDataFromResponseByJsonPath('$.access_token')[0];
    }

    /**
     * Creates a test REST client for the test scenarios.
     * @param $I
     *
     */
    public static function admCreateTestApiClient($I)
    {
        // @param $permissionsJSON ,e.g. '[{"pattern":"/routes","verb":"GET"}]'
        TestScenarios::adminLogin($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('create a new api_key');
        $a_post_data = array("api_key" => TestScenarios::$test_api_key, "api_secret" => TestScenarios::$test_api_secret, "oauth2_gt_resourceowner_active" => "1");
        $I->sendPOST('clients',$a_post_data);
        TestScenarios::$test_client_id = $I->grabDataFromResponseByJsonPath('$.id')[0];

        /*$I->wantTo('put route permission on new test client '.TestScenarios::$test_client_id);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->amBearerAuthenticated(TestCommons::$token);

        $aPost = array('permissions' => $permissionsJSON);
        $I->sendPUT('clients/'.TestScenarios::$test_client_id, $aPost);
        */
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * Allows to add a permission statement, i.e. (route,verb) -  pair, to the test rest client.
     * @param $I
     * @param $pattern
     * @param $verb
     */
    public static function admAddPermissionToTestApiClient($I, $api_key, $pattern, $verb)
    {
        TestScenarios::adminLogin($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('add permission to test client');
        $postData = array("api_key" => $api_key, 'pattern' => $pattern, 'verb' => $verb);
        $I->sendPOST('clientpermissions',$postData);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * Creates some ILIAS test users.
     * Requirement: The test api_key needs the permissions to create (and delete) an ilias user.
     * @param $I
     */
    public static function admCreateTestUsers($I)
    {
        TestScenarios::adminLogin($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestCommons::$api_key,'/v1/users','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestCommons::$api_key,'/v1/users/:user_id','DELETE');

        $postData = array('login'=>TestScenarios::$system_user_1_login, 'passwd' => 'stormy', 'firstname' => 'Hero', 'lastname' => 'Sestos','email'=> 'myth@localhost', 'gender' => 'f');
        $I->sendPOST('v1/users',$postData);
        TestScenarios::$system_user_1_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $postData = array('login'=>TestScenarios::$system_user_2_login, 'passwd' => 'stormy', 'firstname' => 'Leander', 'lastname' => ' Abydos','email'=> 'myth@localhost', 'gender' => 'm');
        $I->sendPOST('v1/users',$postData);
        TestScenarios::$system_user_2_id = $I->grabDataFromResponseByJsonPath('$.id')[0];

        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('create a new test user');
    }

    /**
     * Removes some ILIAS test users.
     * Requirement: The test api_key needs the permissions to create (and delete) an ilias user.
     * @param $I
     */
    public static function admRemoveTestUsers($I)
    {
        TestScenarios::adminLogin($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestCommons::$api_key,'/v1/users','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestCommons::$api_key,'/v1/users/:user_id','DELETE');

        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('remove test users');

        // Step 1: get user ids (this is necessary in case when this method is invoked in a different process
        // than admCreateTestUsers
        $I->sendGET('v1/users');
        $data = $I->grabDataFromResponseByJsonPath('$.users[*].user');
        //\Codeception\Util\Debug::debug(print_r($data,true));die();

        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['login'] == TestScenarios::$system_user_1_login) {
                TestScenarios::$system_user_1_id = $data[$i]['usr_id'];
            }
            if ($data[$i]['login']== TestScenarios::$system_user_2_login) {
                TestScenarios::$system_user_2_id = $data[$i]['usr_id'];
            }
        }

        // Step 2: delete users
        if (TestScenarios::$system_user_1_id > -1) {
            $I->sendDELETE('v1/users/'.TestScenarios::$system_user_1_id);
            $I->seeResponseContainsJson(array('status' => 'success'));
        }

        if (TestScenarios::$system_user_2_id > -1) {
            $I->sendDELETE('v1/users/'.TestScenarios::$system_user_2_id);
            $I->seeResponseContainsJson(array('status' => 'success'));
        }
    }


    /**
     * Removes a test REST client for the test scenarios.
     * @param $I
     *
     */
    public static function admRemoveTestApiClient($I)
    {
        TestScenarios::adminLogin($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('remove the test client/api_key');

        // Step 1: get client id
        $I->sendGET('clients');
        $data = $I->grabDataFromResponseByJsonPath('$.clients[*]');

        //\Codeception\Util\Debug::debug(print_r($data,true));//die();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['api_key']==TestScenarios::$test_api_key) {
                TestScenarios::$test_client_id = $data[$i]['id'];
            }
         }
        \Codeception\Util\Debug::debug("Testclient found : ".TestScenarios::$test_client_id);//die();

        // Step 2: delete client
        $I->sendDELETE('clients/'.TestScenarios::$test_client_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}