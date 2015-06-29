<?php 
$I = new ApiTester($scenario);
$I->wantTo('list all routes');
$I->sendGET('routes');
$I->seeResponseContainsJson(array('status' => 'success'));

