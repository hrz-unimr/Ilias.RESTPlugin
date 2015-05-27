<?php
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();


$all_head = $app->request->headers->get('param-all');   // Fetched nur HEAD
$head = $app->request->headers->get('param-head');      // Fetched nur HEAD

$all_get = $app->request->get('param-all');             // Fetched nur GET
$get = $app->request->get('param-get');                 // Fetched nur GET

$all_post = $app->request->post('param-all');           // Fetched nur BODY
$post = $app->request->post('param-post');              // Fetched nur BODY

$all_put = $app->request->put('param-all');             // Fetched nur BODY
$put = $app->request->put('param-put');                 // Fetched nur BODY

$all_params = $app->request->params('param-all');       // Fetched GET & BODY
$head_params = $app->request->params('param-head');     // Fetched GET & BODY
$get_params = $app->request->params('param-get');       // Fetched GET & BODY
$post_params = $app->request->params('param-post');     // Fetched GET & BODY
$put_params = $app->request->params('param-put');       // Fetched GET & BODY

$dump = array(
    '$all_head' => $all_head,
    '$head' => $head,
    '$all_get' => $all_get,
    '$get' => $get,
    '$all_post' => $all_post,
    '$post' => $post,
    '$all_put' => $all_put,
    '$put' => $put,
    '$all_params' => $all_params,
    '$head_params' => $head_params,
    '$get_params' => $get_params,
    '$post_params' => $post_params,
    '$put_params' => $put_params
);
var_dump($dump);
