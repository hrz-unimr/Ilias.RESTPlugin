<?php

use \ApiTester;

/**
 * Class MobileRoutesCest
 * @group mobile
 */
class MobileRoutesCest
{
    public function _before(ApiTester $I)
    {
        //TestCommons::logMeIn($I);
    }

    public function _after(ApiTester $I)
    {
    }

   /* public function getMobileProfile(ApiTester $I)
    {
        $I->wantTo('get mobile profile of test user');
        $I->amBearerAuthenticated(TestCommons::$token);
        //$I->sendGET('clients');
        //$success =  array_search($this->client_id,$I->grabDataFromResponseByJsonPath('$.clients[*].id'));
        //\PHPUnit_Framework_Assert::assertTrue($success);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
   */
}