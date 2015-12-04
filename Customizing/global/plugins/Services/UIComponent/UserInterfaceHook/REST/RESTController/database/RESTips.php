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
 * Class: RESTKey2IP (Database-Table)
 *  Abstraction for 'ui_uihk_rest_key2ip' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTKey2IP extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_key2ip';
  protected static $tableKeys   = array(
    'id'      => 'integer',
    'api_id'  => 'integer',
    'ip'      => 'text'
  );


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // Parse input based on key
    switch ($key) {
      // Convert int values
      case 'api_id':
        $value = intval($value);
        break;

      // No default behaviour
      default:
    }

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }


  /**
   * Function: setKey($joinTable)
   *  @See RESTDatabase::getJoinKey(...)
   */
  public static function getJoinKey($joinTable) {
    // JOIN ui_uihk_rest_keys ON api_id
    if ($joinTable == 'ui_uihk_rest_keys')
      return 'api_id';

    // Otherwise join on primary
    return parent::getJoinKey($joinTable);
  }
}
