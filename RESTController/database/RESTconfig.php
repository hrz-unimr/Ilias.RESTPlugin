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
 * Class: RESTconfig (Database-Table)
 *  Abstraction for 'ui_uihk_rest_config' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTconfig extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey = 'id';
  protected static $tableName = 'ui_uihk_rest_config';
  protected static $tableKeys = array(
    'id'            => 'integer',
    'setting_name'  => 'text',
    'setting_value' => 'text'
  );


  /**
   * Function: fromSettingName($name)
   *  Creates a new instance of RESTconfig representing the table-entry with given setting_name.
   *
   * Parameters:
   *  $name <String> - Name of setting who's database entry should be returned
   *
   * Return:
   *  <RESTconfig> - A new instance of RESTconfig representing the table-entry with given setting_name
   */
  public static function fromSettingName($name) {
    // Generate a (save) where clause for the setting_name ($name can be malformed!)
    $where  = sprintf('setting_name = %s', self::quote($name, 'text'));

    // Fetch matching object
    return self::fromWhere($where);
  }


  /**
   * Function: fetchSettings($names)
   *  Fetches settings with given names from ui_uihk_rest_config.
   *  Returns an array with that contains all settings
   *  accessable by using their name as key.
   *
   * Parameters:
   *  $names <Array[String]> - Settings (setting_name) to fetch
   *
   * Return:
   *  <Array[String]> - List of settings with returnValue[setting_name] = setting_value
   */
  public static function fetchSettings($names) {
    // Make sure we are dealing with an array
    if (!is_array($names))
      $names = array($names);

    // Quote and escape input
    foreach($names as $key => $name)
      $names[$key] = self::quote($name, 'text');

    // Create correct where-clause for fetching all settings
    $in       = implode(', ', $names);
    $where    = sprintf('setting_name IN (%s)', $in);

    // Convert rows to array of name/setting_value[name] pairs
    $settings = array();
    $rows     = self::fromWhere($where, true);
    foreach($rows as $row){
      $name   = $row->getKey('setting_name');
      $value  = $row->getKey('setting_value');
      $settings[$name] = $value;
    }

    // return settings-object
    return $settings;
  }


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // This table is a bit special, as a keys value can have many different types
    // For simplicity and extenability we only convert strings to integer/floats where
    // it looks feasable. (Sadly booleans look just like integers when fetching from database...)
    if (ctype_digit($value))
      $value = intval($value);
    if (is_float($value))
      $value = floatval($value);

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }
}
