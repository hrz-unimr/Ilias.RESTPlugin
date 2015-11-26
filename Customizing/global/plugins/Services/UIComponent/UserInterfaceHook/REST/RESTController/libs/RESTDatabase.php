<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Class: RESTDatabase
 *  Base-Class for all classes that represent/abstract
 *  a table (or table-entry) inside the SQL-Database.
 */
class RESTDatabase {
  /* TODO
   *  - Löschen ohne query
   *  - RESTConfig besser unterstützen
   */


  protected static $uniqueKey;
  protected static $tableName;
  protected static $tableKeys;


  protected function __construct($row) {
    $this->$row = array();
    foreach($row as $key => $value)
      $this->setKey($key, $value, false);
  }


  public static function fromRow($row) {
    return new static($row);
  }


  public static function fromUnique($value)  {
    $key    = static::$uniqueKey;
    $where  = sprintf('%s = %d', $key, intval($value));
    return self::fromWhere($where);
  }


  public static function fromWhere($where, $multiple = false)  {
    $sql    = sprintf('SELECT * FROM %s WHERE %s', static::$tableName, $where);
    $query  = self::getDB()->query($sql);
    if ($query) {
      if ($multiple){
        $rows = array();
        while ($row = self::getDB()->fetchAssoc($query))
          $rows[] = new static($row);
        return $rows;
      }

      else {
        $row = self::getDB()->fetchAssoc($query);
        return new static($row);
      }
    }
    else
      throw new \Exception('No entry in DB');
  }


  public function getKey($key, $read = false) {
    if (!array_key_exists($key, static::$tableKeys))
      throw new \Exception('No key in table');

    if ($read)
      $this->read();

    return $this->$row[$key];
  }


  public function setKey($key, $value, $write = false) {
    if (!array_key_exists($key, static::$tableKeys))
      throw new \Exception('No key in table');

    if ($key == static::$uniqueKey)
      $value = intval($value);

    $this->row[$key] = $value;

    if ($write)
      $this->write();
  }


  public function read() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];
    $sql    = sprintf('SELECT * FROM %s WHERE %s = %d', static::$tableName, $key, intval($value));
    $query  = self::getDB()->query($sql);
    if ($query) {
      $this->row = self::getDB()->fetchAssoc($query);
      return $this->row;
    }
    else
      throw news \Exceptions('No entry in DB');
  }


  public function write() {
    if ($this->exists())
      return $this->update();
    else
      return $this->write();
  }


  public function update() {
    $row    = $this->getDBRow();
    $where  = $this->getDBWhere();
    return self::getDB()->update(static::$table, $row, $where);
  }


  public function insert() {
    $row = $this->getDBRow();
    return self::getDB()->insert(static::$table, $row);
  }


  public function exists() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];
    return self::existsUnique($value);
  }


  public static function existsUnique($value) {
    $key    = static::$uniqueKey;
    $sql    = sprintf('SELECT count(1) FROM %s WHERE %s = %d', static::$tableName, $key, intval($value));
    if (self::getDB()->query($sql))
      return true;
    else
      return false;
  }


  public function getRow() {
    return $this->row;
  }


  public function getDBRow() {
    $row = array();
    foreach($this->row as $key => $value) {
      $type       = $this->keys[$key];
      $row[$key]  = array($type, $value);
    }

    return $row;
  }


  public function getDBWhere() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];
    $type   = $this->keys[$key];
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


  /**
   * Function: getDB() [STATIC]
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
