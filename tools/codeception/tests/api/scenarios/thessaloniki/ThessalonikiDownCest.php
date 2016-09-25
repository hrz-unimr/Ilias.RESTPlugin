<?php
/**
 * Class KalamariaDownCest
 * Cleans-up the testing scenario.
 *
 * @group scenario
 */
class ThessalonikiDownCest
{

    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    public function removeTestUsers(ApiTester $I)
    {
        TestScenarios::admRemoveTestUsers($I);
    }

    public function removeTestingCourse(ApiTester $I)
    {
        TestScenarios::deleteCourse1($I);
    }

    /**
     * @depends removeTestUsers
     */
    public function removeTestClient(ApiTester $I)
    {
        TestScenarios::admRemoveTestApiClient($I);
    }


}