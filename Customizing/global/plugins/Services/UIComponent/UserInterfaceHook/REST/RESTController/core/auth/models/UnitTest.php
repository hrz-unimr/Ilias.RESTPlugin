<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');


include('Exceptions/TokenInvalid.php');
include('TokenSettings.php');
include('TokenBase.php');
include('GenericToken.php');
include('RefreshToken.php');
include('BearerToken.php');


use RESTController\core\auth as Auth;


$settings = new Auth\TokenSettings("123456", 30);

$user = "root";
$api_key = "apollon";
$type = "generic";
$misc = "hier könnte ihre Werbung stehen...";
$lifetime = 30;
$scope = "No-Scope Headshot";

$generic1 = Auth\GenericToken::fromFields($settings, $user, $api_key, $type, $misc, $lifetime);

echo "Dump #0:";
var_dump($generic1);

$token1String = $generic1->getTokenString();
$token1Array = $generic1->getTokenArray();

echo "Dump #1:";
var_dump($token1String);
var_dump($token1Array);

//$token1Array['h'] = '0';
$generic2 = Auth\GenericToken::fromMixed($settings, $token1Array);
$generic3 = Auth\GenericToken::fromMixed($settings, $token1String);

echo "Dump #2:";
var_dump($generic2);
var_dump($generic3);

echo "Dump #3:";
var_dump($generic2->getTokenArray());
var_dump($generic3->getTokenArray());

echo "Dump #4:";
var_dump($generic3->getEntry('misc'));
var_dump($generic3->getEntry('h'));

$generic3->setEntry('misc', 'Hello World');

echo "Dump #5:";
var_dump($generic3->getEntry('misc'));
var_dump($generic3->getEntry('h'));

$generic2->setEntry('misc', 'Hello World');

echo "Dump #6:";
var_dump($generic2->getEntry('misc'));
var_dump($generic2->getEntry('h'));

echo "Dump #7:";
var_dump($generic3->isValid());
var_dump($generic3->isExpired());

echo "Dump #8:";
var_dump($generic2->isValid());
var_dump($generic2->isExpired());

$refresh1 = Auth\RefreshToken::fromFields($settings, $user, $api_key);

echo "Dump #B1:";
var_dump($refresh1);

$token2Array = $refresh1->getTokenArray();
$token2String = $refresh1->getTokenString();

echo "Dump #B2:";
var_dump($token2Array);
var_dump($token2String);

$refresh2 = Auth\RefreshToken::fromMixed($settings, $token2Array);
$refresh3 = Auth\RefreshToken::fromMixed($settings, $token2String);

echo "Dump #B3:";
var_dump($refresh2);
var_dump($refresh3);

$bearer1 = Auth\BearerToken::fromFields($settings, $user, $api_key, $scope);

echo "Dump #C1:";
var_dump($bearer1);

$token3Array = $bearer1->getTokenArray();

echo "Dump #C2:";
var_dump($token3Array);

$bearer2 = Auth\BearerToken::fromMixed($settings, $token3Array);

echo "Dump #C3:";
var_dump($bearer2);

$tokenXString = $bearer2->getEntry('access_token');

echo "Dump #C4:";
var_dump($tokenXString);

$genericX = Auth\GenericToken::fromMixed($settings, $tokenXString);

echo "Dump #C5:";
var_dump($genericX);
