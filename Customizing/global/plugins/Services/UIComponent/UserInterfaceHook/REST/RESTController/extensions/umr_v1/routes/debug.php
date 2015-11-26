<?php
namespace RESTController\extensions\umr_v1;

use \RESTController\database as Database;


$app->get('/v1/umr/debug', function () use ($app) {
  $id     = 1;
  $row    = array();

  $entry1  = Database\RESTKeys::fromUnique($id);
  var_dump($entry1->getRow());
  $entry2  = Database\RESTConfig::fromUnique($id);
  var_dump($entry2->getRow());

  die;
});
