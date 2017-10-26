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
 * Class: RESTaccess (Database-Table)
 *  Abstraction for 'ui_uihk_rest_access' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTaccess extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_access';
  protected static $tableKeys   = array(
    'id'      => 'integer',
    'hash'    => 'text',
    'token'   => 'text',
    'expires' => 'text'
  );


  /**
   * Function: fromToken($tokenString)
   *  Creates a new instance of RESTclient representing the table-entry with given token representation.
   *
   * Parameters:
   *  $tokenString <String> - token representation who's database entry should be returned
   *
   * Return:
   *  <RESTclient> - A new instance of RESTclient representing the table-entry with given token representation
   */
  public static function fromToken($tokenString) {
    // Generate a (save) where clause for the token-string ($tokenString can be malformed!)
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
   * Function: store()
   *  Insert a new table-entry for the internal authorization-code and removes
   *  all existing codes for the same user and client (api-key).
   */
  public function store() {
    // Delete all existing entries for resource-owner and api-key
    $where = 'hash = {{hash}}';
    $this->delete($where);

    // Insert a new entry into DB
    $this->insert();
  }
}
