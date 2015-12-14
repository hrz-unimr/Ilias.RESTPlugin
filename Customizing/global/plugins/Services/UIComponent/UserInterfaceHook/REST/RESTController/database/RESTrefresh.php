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
 * Class: RESTrefresh (Database-Table)
 *  Abstraction for 'ui_uihk_rest_refresh' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTrefresh extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_refresh';
  protected static $tableKeys   = array(
    'id'            => 'integer',
    'hash'          => 'text',
    'token'         => 'text',
    'last_refresh'  => 'text',
    'created'       => 'text',
    'refreshes'     => 'integer'
  );


  /**
   * Function: fromApiKey($tokenString)
   *  Creates a new instance of RESTrefresh representing the table-entry with given Refresh-Token.
   *
   * Parameters:
   *  $tokenString <String> - Refresh-Token (as string) who's database entry should be returned
   *
   * Return:
   *  <RESTrefresh> - A new instance of RESTrefresh representing the table-entry with given token
   */
  public static function fromToken($tokenString) {
    // Generate a (save) where clause for the token ($tokenString can be malformed!)
    $where  = sprintf(
      'token IN (%s, %s, %s)',
      self::quote($tokenString, 'text'),
      self::quote(urlencode($tokenString), 'text'),
      self::quote(urldecode($tokenString), 'text')
    );

    // Fetch matching object
    return self::fromWhere($where);
  }


  /**
   * Function: fromHash($hash)
   *  Creates a new instance of RESTclient representing the table-entry with given hash.
   *
   * Parameters:
   *  $hash <String> - Hash who's token database entry should be returned
   *
   * Return:
   *  <RESTclient> - A new instance of RESTclient representing the table-entry with given hash
   */
  public static function fromHash($hash) {
    // Generate a (save) where clause for the hash ($hash can be malformed!)
    $where  = sprintf('hash = %s', self::quote($hash, 'text'));

    // Fetch matching object
    return self::fromWhere($where);
  }


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // Parse input based on key
    switch ($key) {
      // Convert int values
      case 'num_resets':
        $value = intval($value);
        break;

      // No default behaviour
      default:
    }

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }


  /**
   * Function: refreshed()
   *  Call this function to update the last_refresh timer and the number of refresh for this token.
   *  This will update the internally stored data as well as the database. (Needs valid primary-key)
   */
  public function refreshed() {
    // Update internal values
    $this->setKey('last_refresh', date("Y-m-d H:i:s"));
    $this->setKey('refreshes', $this->getKey('refreshes') + 1);

    // Update database with internal values
    $this->update();
  }
}
