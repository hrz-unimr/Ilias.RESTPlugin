<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\database;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Class: RESTConfig (Database-Table)
 *
 */
class RESTConfig extends Libs\RESTDatabase {
  protected static $uniqueKey = 'id';
  protected static $tableName = 'ui_uihk_rest_config';
  protected static $tableKeys = array(
    'id'            => 'integer',
    'setting_name'  => 'text',
    'setting_value' => 'text'
  );


  public function setKey($key, $value, $write = false) {
    if (is_int($value))
      $value = intval($value);
    if (is_float($value))
      $value = floatval($value);

    return parent::setKey($key, $value, $write);
  }
}
