<?php
use \ApiTester;

/**
 * Class ClientroutesCest
 * @group clients
 */
class ClientroutesCest
{
    public $client_id="-1";

    public function _before(ApiTester $I)
    {
        //TestCommons::logMeIn($I);
    }

    public function _after(ApiTester $I)
    {
    }

    public function addNewClient(ApiTester $I)
    {
        TestCommons::logMeIn($I);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->wantTo('create a new rest client');
        $a_post_data = array("api_key" => "testing", "api_secret" => 1234, "oauth2_gt_resourceowner_active" => "1");
        $I->sendPOST('clients',$a_post_data);
        $this->client_id = $I->grabDataFromResponseByJsonPath('$.id')[0];
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     *  @depends addNewClient
     */
    public function setPermissionTest(ApiTester $I)
    {
        $I->wantTo('put route permission on new client '.$this->client_id);
        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
        $I->amBearerAuthenticated(TestCommons::$token);

        $aPost = array('permissions' => '[{"pattern":"/routes","verb":"GET"}]');
        $I->sendPUT('clients/'.$this->client_id,$aPost);

        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     *  @depends addNewClient
     */
    public function listAllClients(ApiTester $I)
    {
        $I->wantTo('list all clients');
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->sendGET('clients');
        //$success =  array_search($this->client_id,$I->grabDataFromResponseByJsonPath('$.clients[*].id'));
        //\PHPUnit_Framework_Assert::assertTrue($success);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

    /**
     *  @depends setPermissionTest
     */
    public function deleteClient(ApiTester $I)
    {
        $I->wantTo('delete client '.$this->client_id);
        $I->amBearerAuthenticated(TestCommons::$token);
        $I->sendDELETE('clients/'.$this->client_id);
        $I->seeResponseContainsJson(array('status' => 'success'));
    }

}