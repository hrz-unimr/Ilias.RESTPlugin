<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Class: RESTDBTable
 *  Base-Class for all classes that represent/abstract
 *  a table (or table-entry) inside the SQL-Database.
 */
abstract class RESTDatabase {


  /**
   * TODO:
   *  - Kommentieren
   */


  const MSG_WRONG_ROW_TYPE  = 'Constructor requires first parameter of type array, but it is: %s.';
  const MSG_WRONG_ROW_SIZE  = 'Constructor requires first parameter to be an array of size %d, but it is %d.';
  const MSG_NO_ENTRY        = 'Could not find entry for query: %s.';
  const MSG_NO_KEY          = 'There is no key "%s" in table "%s".';
  const MSG_NO_UNIQUE       = 'Operation not possible, missing value for unique-key (%s.%s).';


  protected static $uniqueKey;
  protected static $tableName;
  protected static $tableKeys;


  protected function __construct($row) {
    if (!is_array($row)) throw new Exceptions\Database(sprintf(self::MSG_WRONG_ROW_TYPE, gettype($row)));
    if (!isset($row[static::$uniqueKey])) $row[static::$uniqueKey] = false;
    if (count($row) != count(static::$tableKeys)) throw new Exceptions\Database(sprintf(self::MSG_WRONG_ROW_SIZE, count(static::$tableKeys), count($row)));

    $this->row = array();
    foreach($row as $key => $value)
      $this->setKey($key, $value, false);
  }


  public static function fromRow($row) {
    return new static($row);
  }


  public static function fromUnique($value) {
    $key    = static::$uniqueKey;
    $where  = sprintf('%s = %d', $key, intval($value));
    return self::fromWhere($where);
  }


  public static function fromWhere($where, $joinWith = null, $multiple = false) {
    $where = self::parseStaticSQL($where);

    if (isset($joinWith)) {
      $table      = static::getTableName();
      $key        = static::getJoinKey($joinWith);
      $joinTable  = call_user_func(array($joinWith, 'getTableName'));
      $joinKey    = call_user_func(array($joinWith, 'getJoinKey'), get_called_class());
      $sql        = sprintf(
                      'SELECT %s.* FROM %s JOIN %s ON %s.%s = %s.%s WHERE %s',
                      $table, $table, $joinTable, $table, $key, $joinTable, $joinKey, $where
                    );
    }
    else
      $sql  = sprintf('SELECT * FROM %s WHERE %s', static::$tableName, $where);

    $query  = self::getDB()->query($sql);
    if ($query)
      if ($multiple) {
        $rows = array();
        while ($row = self::getDB()->fetchAssoc($query))
          $rows[] = new static($row);
        return $rows;
      }

      elseif ($row = self::getDB()->fetchAssoc($query))
        return new static($row);

    throw new Exceptions\Database(sprintf(self::MSG_NO_ENTRY, $sql));
  }


  public function getKey($key, $read = false) {
    if (!array_key_exists($key, static::$tableKeys)) throw new Exceptions\Database(sprintf(self::MSG_NO_KEY, $key, static::$tableName));

    if ($read) $this->read();

    return $this->row[$key];
  }


  // Overwrite to ensure correct type
  public function setKey($key, $value, $write = false) {
    if (!array_key_exists($key, static::$tableKeys)) throw new Exceptions\Database(sprintf(self::MSG_NO_KEY, $key, static::$tableName));

    if ($key == static::$uniqueKey && $value != false) $value = intval($value);

    $this->row[$key] = $value;

    if ($write) return $this->write($key);
  }


  // Requires $uniqueKey
  public function read() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];

    if ($value == false) throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey));

    $sql    = sprintf('SELECT * FROM %s WHERE %s = %d', static::$tableName, $key, intval($value));
    $query  = self::getDB()->query($sql);
    if ($query) return $this->row = self::getDB()->fetchAssoc($query);
    else        throw new Exceptions\Database(sprintf(self::MSG_NO_ENTRY, $sql));
  }


  public function write($key = null) {
    if ($this->exists())  return $this->update($key);
    else                  return $this->insert();
  }


  // Requires $uniqueKey
  public function update($key = null) {
    $row    = $this->getDBRow($key);
    $where  = $this->getDBWhere();
    return self::getDB()->update(static::$tableName, $row, $where);
  }


  public function insert($newUnique = false) {
    $row = $this->getDBRow();

    if ($newUnique || $row[static::$uniqueKey] == false) unset($row[static::$uniqueKey]);

    $result = self::getDB()->insert(static::$tableName, $row);
    $id     = self::getDB()->getLastInsertId();
    $this->setKey(static::$uniqueKey, $id);

    return $result;
  }


  // Requires $uniqueKey
  public function exists() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];

    if ($value == false) throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey));

    return self::existsByUnique($value);
  }


  public static function existsByUnique($value) {
    $key    = static::$uniqueKey;
    $where  = sprintf('%s = %d', $key, intval($value));
    return self::existsByWhere($where);
  }


  public static function existsByWhere($where) {
    $sql    = sprintf('SELECT 1 FROM %s WHERE %s', static::$tableName, $where);
    $query  = self::getDB()->query($sql);
    return self::getDB()->numRows($query) > 0;
  }


  // Requires $uniqueKey
  public function delete() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];

    if ($value == false) throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey));

    return self::deleteByUnique($value);
  }


  public static function deleteByUnique($value) {
    $key    = static::$uniqueKey;
    $where  = sprintf('%s = %d', $key, intval($value));
    return self::deleteByWhere($where);
  }


  public static function deleteByWhere($where) {
    $sql    = sprintf('DELETE FROM %s WHERE %s', static::$tableName, $where);
    return self::getDB()->manipulate($sql);
  }


  public function fetch($sql, $parse = true) {
    if ($parse) $sql = $this->parseSQL($sql);

    return self::fetchStatic($sql, false);
  }


  public static function fetchStatic($sql, $parse = true) {
    if ($parse) $sql = self::parseStaticSQL($sql);

    $query  = self::getDB()->query($sql);
    if ($query) {
      $rows = array();
      while($row = self::getDB()->fetchAssoc($query))
        $rows[] = $row;
      return $rows;
    }

    else
     return $query;
  }


  public function manipulate($sql, $parse = true) {
    if ($parse) $sql = $this->parseSQL($sql);

    return self::manipulateStatic($sql, false);
  }


  public static function manipulateStatic($sql, $parse = true) {
    if ($parse) $sql = self::parseStaticSQL($sql);
    var_dump($sql);
    return self::getDB()->manipulate($sql);
  }


  public function parseSQL($sql) {
    $sql = self::parseStaticSQL($sql);
    $sql = preg_replace_callback(
      '/{{%?([^}]+)}}/',
      function($match) {
        $key    = $match[1];
        $value  = $this->row[$key];
        $type   = static::$tableKeys[$key];
        return self::getDB()->quote($value, $type);
      },
      $sql
    );
    return $sql;
  }


  protected static function parseStaticSQL($sql) {
    $sql = str_replace('{{table}}',   static::$tableName, $sql);
    $sql = str_replace('{{unique}}',  static::$uniqueKey, $sql);
    return $sql;
  }


  public function getRow($key = null) {
    if (isset($key))  return $this-getKey($key);
    else              return $this->row;
  }


  public function getDBRow($key = null) {
    $row = array();
    if (isset($key)) {
      $value      = $this->row[$key];
      $type       = $this->keys[$key];
      $row[$key]  = array($type, $value);
    }
    else
      foreach($this->row as $key => $value) {
        $type       = $this->keys[$key];
        $row[$key]  = array($type, $value);
      }

    return $row;
  }


  // Requires $uniqueKey
  public function getDBWhere() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];
    $type   = $this->keys[$key];

    if ($value == false) throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey));

    return array(
      $key => array($type, $value)
    );
  }


  public static function getUniqueKey() {
    return static::$uniqueKey;
  }


  public static function getTableName() {
    return static::$tableName;
  }


  public static function getTableKeys() {
    return static::$tableKeys;
  }


  public static function getJoinKey($joinWith) {
    return static::$uniqueKey;
  }


  /**
   * Function: [STATIC] getDB()
   *  Use this to inject global ILIAS-Database
   *  into this class.
   *
   * Return:
   *  <ilDB> - ILIAS-Database Object
   */
  public static function getDB() {
    return $GLOBALS['ilDB'];
  }
}
