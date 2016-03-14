<?php
require_once('./IliasRestSystemClient.php');

$client = new IliasRestSystemClient();

// Get all routes
$resp = $client->get('/v2/util/routes');
print_r(json_decode($resp,true));  // <- print associative array of json response


// Create a test course
//$resp = $client->post('/v1/courses',array("ref_id"=>"1", "title"=>"Course Sys client", "description" => "Created by RestSystemClient"));
//echo $resp ."\n";
//$a_resp = json_decode($resp,true);

// Delete the test course again
//$resp = $client->delete('/v1/courses/'.$a_resp['refId'], array());
//print_r(json_decode($resp,true));