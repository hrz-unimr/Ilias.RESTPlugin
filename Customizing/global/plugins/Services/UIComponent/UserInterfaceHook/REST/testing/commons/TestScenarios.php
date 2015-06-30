<?php
class TestScenarios
{
    public static $test_client_id = "-1";
    public static $test_api_key = 'testing';
    public static $test_api_secret = 'testing';

    public static $test_username = "zeus";
    public static $test_user_password = "athen";
    public static $test_user_token = "";
    public static $test_user_id = "-1";


    public static function adminLogin($I)
    {
        TestCommons::logMeIn($I);
    }

    /**
     * This function enables a login using a new client and new user.
     * Therefore this method depends on createTestApiClient and createTestUser.
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
     * @param $permissionsJSON ,e.g. '[{"pattern":"/routes","verb":"GET"}]'
     */
    public static function createTestApiClient($I, $permissionsJSON)
    {
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('create a new api_key');
        $a_post_data = array("api_key" => TestScenarios::$test_api_key, "api_secret" => TestScenarios::$test_api_secret, "oauth2_gt_resourceowner_active" => "1");
        $I->sendPOST('clients',$a_post_data);
        TestScenarios::$test_client_id = $I->grabDataFromResponseByJsonPath('$.id')[0];

        $I->wantTo('put route permission on new test client '.TestScenarios::$test_client_id);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->amBearerAuthenticated(TestCommons::$token);

        $aPost = array('permissions' => $permissionsJSON);
        $I->sendPUT('clients/'.TestScenarios::$test_client_id, $aPost);

        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * Requirement: The test api_key needs the permissions to create and delete an ilias user.
     * @param $I
     */
    public static function createTestUser($I)
    {
        // problem: mit welchem api key soll test user erstellt werden?
        // hat der api_key die entsprechenden rechte?
       // '/v1/users'
 //       i.post('v1/users',{'login':'isabell','passwd':'top_secret','firstname':'isa','lastname':'bell','email':'ds@jivas.de','gender':'f'})

        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('create a new test user');
    }
}