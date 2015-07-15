<?php
class TestCommons
{
    public static $username = 'root';
    public static $password = 'homer';
    public static $api_key = 'apollon';
    public static $token = '';
    public static $isLoggedIn = false;

    public static function logMeIn($I)
    {
        if (TestCommons::$isLoggedIn == false) {
            $I->wantTo('authenticate via oauth2 user credentials');
            $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');
            $aPost = array('grant_type' => 'password',
                'username' => TestCommons::$username,
                'password' => TestCommons::$password,
                'api_key' => TestCommons::$api_key);
            $I->sendPOST('v1/oauth2/token', $aPost);
            TestCommons::$token = $I->grabDataFromResponseByJsonPath('$.access_token')[0];
            TestCommons::$isLoggedIn = true;
        }
    }
}