<?php 
$I = new ApiTester($scenario);
$I->wantTo('list ilias-rest plugin routes');
$I->sendGET('routes');
$I->seeResponseContainsJson(array('status' => 'success'));

