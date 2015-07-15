<?php

use \ApiTester;

/**
 * Class MobileRoutesCest
 * @group mobile
 */
class MobileMainRoutesCest
{
    public function _before(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaUpCest.php');
        $scenario = new KalamariaUpCest();
        $scenario->createTestClient($I);
        $scenario->createSystemTestUsers($I);
        $scenario->createTestingCourse($I);
        TestScenarios::admAddPermissionToTestApiClient($I,TestScenarios::$test_api_key,'/v1/m/origin','GET');
    }

    public function _after(ApiTester $I)
    {
        require_once('tests/api/scenarios/kalamaria/KalamariaDownCest.php');
        $scenario = new KalamariaDownCest();
        $scenario->removeTestUsers($I);
        $scenario->removeTestingCourse($I);
        $scenario->removeTestClient($I);
    }

    public function getInitialMobileDesktop(ApiTester $I)
    {
        $I->wantTo('get mobile profile of test user');
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->sendGET('v1/m/origin');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

}