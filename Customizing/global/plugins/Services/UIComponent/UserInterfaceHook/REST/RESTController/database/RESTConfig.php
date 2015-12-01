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
 *  Abstraction for 'ui_uihk_rest_config' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTConfig extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey = 'id';
  protected static $tableName = 'ui_uihk_rest_config';
  protected static $tableKeys = array(
    'id'            => 'integer',
    'setting_name'  => 'text',
    'setting_value' => 'text'
  );


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // This table is a bit special, as a keys value can have many different types
    // For simplicity and extenability we only convert strings to integer/floats where
    // it looks feasable. (Sadly booleans look just like integers when fetching from database...)
    if (is_int($value))
      $value = intval($value);
    if (is_float($value))
      $value = floatval($value);

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }
}
