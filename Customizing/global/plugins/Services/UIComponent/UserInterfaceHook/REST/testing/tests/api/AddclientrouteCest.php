<?php
use \ApiTester;

class AddclientrouteCest
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
        $I->wantTo('put some route permission');

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $aPost = array('permissions' => '[{"pattern":"/routes","verb":"GET"}]');
        //$aPost = array('permissions' => '[]');
        $I->sendPUT('clients/5',$aPost);


        $I->seeResponseContainsJson(array('status' => 'success'));
    }
}