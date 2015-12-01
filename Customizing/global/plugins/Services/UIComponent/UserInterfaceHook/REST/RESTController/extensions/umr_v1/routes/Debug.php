<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;
use \RESTController\libs\RESTAuth as RESTAuth;


$app->post('/v1/umr/debug', function () use ($app) {
  $app->success(array(
    'hello' => 'world',
    '123'   => '#####',
    'gfdgsd' => 123
  ));
  /*
  $request = $app->request;

  $val = $request->params('header_key2', 'haha', false);
  var_dump($val);
  die;

  // FORM/JSON: RAW BODY CONTENT
  // JSON: Array of key/value pairs from body
  var_dump($request->getBody());

  // FORM: Array of key/value pairs from get and body
  // JSON: Array key/value pairs from get only
  var_dump($request->params());

  // FORM: Array of key/value pairs from body
  // JSON: Empty
  var_dump($_POST);

  // FORM/JSON: Array of key/value of get
  var_dump($_GET);

  // FORM/JSON: Array of key/value of headers
  var_dump($request->headers());

  die;
  */
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
