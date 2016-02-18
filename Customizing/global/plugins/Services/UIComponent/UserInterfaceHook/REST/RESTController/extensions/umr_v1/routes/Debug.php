<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\oauth2 as Auth;


$app->get('/v1/umr/debug', function () use ($app) {
  try {
    // Fetch token
    $accessToken = Auth\Util::getAccessToken();

    // Check token for common problems: Non given or invalid format
    if (!$accessToken)
        $app->halt(401, self::MSG_NO_TOKEN, self::ID_NO_TOKEN);

    // Check token for common problems: Invalid format
    if (!$accessToken->isValid())
        $app->halt(401, Auth\Tokens\Generic::MSG_INVALID, Auth\Tokens\Generic::ID_INVALID);

    // Check token for common problems: Invalid format
    if ($accessToken->isExpired())
        $app->halt(401, Auth\Tokens\Generic::MSG_EXPIRED, Auth\Tokens\Generic::ID_EXPIRED);

    // Check IP (if option is enabled)
    $api_key  = $accessToken->getApiKey();
    $client   = new Clients\RESTClient($api_key);
    if (!$client->checkIPAccess($_SERVER['REMOTE_ADDR']))
      $app->halt(401, self::MSG_IP_NOT_ALLOWED, self::ID_IP_NOT_ALLOWED);

    // For sake of simplicity also return the access-token
    return $accessToken;
  }
  catch (Auth\Exceptions\TokenInvalid $e) {
    $e->send(401);
  }
});


$app->post('/v1/umr/debug2', RESTAuth::checkAccess(RESTAuth::TOKEN), function () use ($app) {
  echo '{"worked": true}';
});


/*
body_key1=body_value1&body_key2=body_value2

{
"body_key1": "body_value1",
"body_key2": "body_value2"
}

http://ilias.me/restplugin.php/v1/umr/debug?hello=world
*/
