<?php
/**
 * Class ThessalonikiUpCest
 * Builds-up the testing scenario.
 *
 * @group scenario
 */
class ThessalonikiUpCest
{

    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    public function createTestClient(ApiTester $I)
    {
        TestScenarios::admCreateTestApiClient($I);

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/:ref_id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/:ref_id','DELETE');

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/enroll','POST');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/join/:ref_id','GET');
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/courses/leave/:ref_id','GET');

        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/admin/files','POST');
    }

    /**
     * @depends createTestClient
     */
    public function createSystemTestUsers($I)
    {
        TestScenarios::createTestUsers($I);
    }

    /**
     * @depends createSystemTestUsers
     */
    public function createTestingCourse($I)
    {
        TestScenarios::createTestCourse1($I);
    }

    /**
     * @depends createTestingCourse
     */
    public function uploadFileToTestCourse1($I)
    {
        TestScenarios::admUploadFileToTestCourse1($I);
    }

}