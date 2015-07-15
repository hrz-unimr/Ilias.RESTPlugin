<?php
class TestScenarios
{
    /* A new rest client / api-key for testing */
    public static $test_client_id = "-1";
    public static $test_api_key = 'testing';
    public static $test_api_secret = 'testing';

    /* (Test) API-Key User */
    public static $test_token = "";

    /* State variables */
    public static $isAdminLoggedIn = false;
    public static $isAdminLoggedInViaTestClient = false;

    /* System users */
    public static $system_user_1_id = -1;
    public static $system_user_2_id = -1;
    public static $system_user_1_login = 'hero';
    public static $system_user_2_login = 'leander';

    /* Courses */
    public static $course1_id = "-1";
    public static $course1_title = "API-Testing";
    public static $course1_description = "Created by Codeception.";

    /* Files */
    public static $test_file1_name = "logo.png";
    public static $test_file1_id = "-1";


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
     * This function enables a login using a new client and the admin user.
     * Therefore this method depends on admCreateTestApiClient (and createTestUser).
     *
     * @param $I
     */
    public static function testerLogin($I)
    {
        if (TestScenarios::$isAdminLoggedInViaTestClient == false) {
            $I->wantTo('authenticate via oauth2 user credentials');
            $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
            $aPost = array('grant_type' => 'password',
                'username' => TestCommons::$username,
                'password' => TestCommons::$password,
                'api_key' => TestScenarios::$test_api_key);
            $I->sendPOST('v1/oauth2/token',$aPost);
            TestScenarios::$test_token = $I->grabDataFromResponseByJsonPath('$.access_token')[0];
            TestScenarios::$isAdminLoggedInViaTestClient = true;
        }
    }

    /**
     * Creates a test REST client for the test scenarios.
     * @param $I
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
     * Creates some ILIAS test users using the newly created API-Key.
     * Requirement: The test api_key needs the permissions to create (and delete) an ilias user.
     * @param $I
     */
    public static function createTestUsers($I)
    {
        TestScenarios::testerLogin($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','DELETE');

        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('create a new test user');

        $postData = array('login'=>TestScenarios::$system_user_1_login, 'passwd' => 'stormy', 'firstname' => 'Hero', 'lastname' => 'Sestos','email'=> 'myth@localhost', 'gender' => 'f');
        $I->sendPOST('v1/users',$postData);
        TestScenarios::$system_user_1_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $postData = array('login'=>TestScenarios::$system_user_2_login, 'passwd' => 'stormy', 'firstname' => 'Leander', 'lastname' => ' Abydos','email'=> 'myth@localhost', 'gender' => 'm');
        $I->sendPOST('v1/users',$postData);
        TestScenarios::$system_user_2_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
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
     * Removes some ILIAS test users with the newly created test API-Key.
     * Requirement: The test api_key needs the permissions to create (and delete) an ilias user.
     * @param $I
     */
    public static function removeTestUsers($I)
    {
        TestScenarios::testerLogin($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/users/:user_id','DELETE');

        $I->amBearerAuthenticated(TestScenarios::$test_token);
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

    /**
     * Creates a test course.
     * Prerequisites: test client must exist and must have the appropriate permissions.
     * @param ApiTester $I
     */
    public static function createTestCourse1(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('create a new course');
        $postData = array('ref_id'=>'1', 'title' => TestScenarios::$course1_title, 'description' => TestScenarios::$course1_description);
        $I->sendPOST('v1/courses',$postData);
        TestScenarios::$course1_id = $I->grabDataFromResponseByJsonPath('$.refId')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * Determines the ID of the test course 1.
     * Invokation of this method is necessary, if a scenario is build and removed at different times.
     *
     * @param ApiTester $I
     */
    public static function determineIdOfTestCourse1(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('determine test course 1 ref_id');

        // get courses
        $I->sendGET('v1/courses');
        $data = $I->grabDataFromResponseByJsonPath('$.courses.*');

        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i][0]['title']==TestScenarios::$course1_title) {
                TestScenarios::$course1_id = $data[$i][0]['ref_id'];
            }
        }
    }

    /**
     * Determines the ID of the test file 1, which is contained in test course 1.
     * Requirements:
     * 1) testfile must exist in test course 1
     * 2) test course 1 ID must be known / determined beforehand
     * @param ApiTester $I
     */
    public static function determineTestFile1Id(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('determine ID of test file 1');

        // get course contents
        $I->sendGET('v1/courses/'.TestScenarios::$course1_id);
        $data = $I->grabDataFromResponseByJsonPath('$.coursecontents.*');

        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['title']==TestScenarios::$test_file1_name) {
                TestScenarios::$test_file1_id = $data[$i]['ref_id'];
            }
        }
    }

    /**
     * Creates a test course.
     * Prerequisites: test client must exist and must have the appropriate permissions.
     * @param ApiTester $I
     */
    public static function deleteCourse1(ApiTester $I)
    {
        TestScenarios::determineIdOfTestCourse1($I);

        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestScenarios::$test_token);
        $I->wantTo('delete a course');

        // Step 2: delete course
        $I->sendDELETE('v1/courses/'.TestScenarios::$course1_id);
        $I->seeResponseContainsJson(array('status' => 'success'));

    }

    /**
     * Uploads a file (_data/logo.png) to test course1.
     * Requirements:
     *  1) test course 1 exists
     *  2) test user exists and is able to verify the ref-id of test course 1
     *  3) client has permissions to access route admin/file
     * @param ApiTester $I
     */
    public static function admUploadFileToTestCourse1(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('upload a file to test course 1');

        // Step 1: update course id
        TestScenarios::determineIdOfTestCourse1($I);

        // Step 2: upload file
        $I->sendPOST('/admin/files', ['ref_id' => TestScenarios::$course1_id], ['uploadfile' => codecept_data_dir('logo.png')]);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     * Uploads a file (_data/logo.png) to the personal file space of the authenticated user.
     * Requirements:
     *  - client has permissions to access route v1/m/myfilespaceupload
     * @param ApiTester $I
     */
    public static function admUploadFileToPersonalFileSpace(ApiTester $I)
    {
        TestScenarios::testerLogin($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('upload a file to MyFileSpace');

        // Step 2: upload file
        $I->sendPOST('/v1/m/myfilespaceupload', [], ['uploadfile' => codecept_data_dir('logo.png')]);
        $I->seeResponseContainsJson(array('status' => 'success'));
        return $I->grabDataFromResponseByJsonPath('$.id')[0];
    }

}