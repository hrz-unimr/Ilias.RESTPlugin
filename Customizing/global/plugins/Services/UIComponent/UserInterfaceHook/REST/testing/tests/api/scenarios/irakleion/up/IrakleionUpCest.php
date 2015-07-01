<?php
use \ApiTester;

/**
 * Class IrakleionUpCest
 * Irakleion is the a name of a greek city and that of a test scenario.
 *
 * see readme.md
 *
 * @group scenario
 */
class IrakleionUpCest
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

}