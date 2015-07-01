<?php
use \ApiTester;

/**
 * Class PeristerionUpCest
 * Peristerion is the a name of a greek city and that of a test scenario.
 *
 * Invocation of this test class causes a build up of a specific test scenario described below.
 * This includes the creation of additional ilias test users, courses etc.
 *
 * Peristerion-"Up"-Cest in contrast to Peristerion-"Down"-Cest, does not delete the created
 * objects from the system, whereas the latter does exactly this.
 *
 * @group scenario
 */
class PeristerionUpCest
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
        TestScenarios::admAddPermissionToTestApiClient($I, TestScenarios::$test_api_key, '/v1/testing', 'GET');
    }

    /**
     * @depends createTestClient
     */
    public function createSystemTestUsers($I)
    {
        TestScenarios::createTestUsers($I);
    }


}