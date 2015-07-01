<?php
use \ApiTester;

/**
 * Class PeristerionDownCest
 * Peristerion is the a name of a greek city and that of a test scenario.
 *
 * Invocation of this test class causes the deconstruction of a specific test scenario described below.
 * This includes the destruction of specific ilias test users, courses etc. which have been constructed in Peristerion.
 *
 * Peristerion-"Down"-Cest in contrast to Peristerion-"Up"-Cest, deletes the created
 * objects from the system, whereas the latter does exactly the opposite.
 *
 * @group scenario
 */
class PeristerionDownCest
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

    /**
     * @depends removeTestUsers
     */
    public function removeTestClient(ApiTester $I)
    {
        TestScenarios::admRemoveTestApiClient($I);
    }


}