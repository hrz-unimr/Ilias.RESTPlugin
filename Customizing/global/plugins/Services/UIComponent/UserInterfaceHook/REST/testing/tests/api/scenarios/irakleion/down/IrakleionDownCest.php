<?php
use \ApiTester;

/**
 * Class IrakleionDownCest
 * Irakleion is the a name of a greek city and that of a test scenario.
 *
 * see readme.md
 *
 * @group scenario
 */
class IrakleionDownCest
{

    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    public function removeTestClient(ApiTester $I)
    {
        TestScenarios::admRemoveTestApiClient($I);
    }


}