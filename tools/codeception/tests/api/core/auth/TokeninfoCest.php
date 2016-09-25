<?php
use \ApiTester;

class TokeninfoCest
{
    public function _before(ApiTester $I)
    {
    }

    public function _after(ApiTester $I)
    {
    }

    // tests
    public function getTokenInfo(ApiTester $I)
    {
        TestCommons::logMeIn($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('get information about the currently used oauth2 access token');
        $I->sendGET('v1/oauth2/tokeninfo');
        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}