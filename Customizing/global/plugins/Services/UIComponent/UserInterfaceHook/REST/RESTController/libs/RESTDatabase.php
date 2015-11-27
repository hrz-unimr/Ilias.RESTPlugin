<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Abstract-Class: RESTDatabase
 *  Base-Class for all classes that represent/abstract
 *  a table (or table-entry) inside the SQL-Database.
 *  (Mostly usefull for the plugins own database tables,
 *  since ILIAS tables allready should have class
 *  representations)
 * Note:
 *  This class depends on the assumption that each
 *  table /table-entry that it manages contains a
 *  unique-key (also called primary-key).
 */
abstract class RESTDatabase {
  // Allow to re-use status messages and codes
  const MSG_WRONG_ROW_TYPE  = 'Constructor requires first parameter of type array, but it is: %s.';
  const ID_WRONG_ROW_TYPE   = 'RESTController\\libs\\RESTDatabase::ID_WRONG_ROW_TYPE';
  const MSG_WRONG_ROW_SIZE  = 'Constructor requires first parameter to be an array of size %d, but it is %d.';
  const ID_WRONG_ROW_SIZE   = 'RESTController\\libs\\RESTDatabase::ID_WRONG_ROW_SIZE';
  const MSG_NO_ENTRY        = 'Could not find entry for query: %s.';
  const ID_NO_ENTRY         = 'RESTController\\libs\\RESTDatabase::ID_NO_ENTRY';
  const MSG_NO_KEY          = 'There is no key "%s" in table "%s".';
  const ID_NO_KEY           = 'RESTController\\libs\\RESTDatabase::ID_NO_KEY';
  const MSG_NO_UNIQUE       = 'Operation not possible, missing value for unique-key (%s.%s).';
  const ID_NO_UNIQUE        = 'RESTController\\libs\\RESTDatabase::ID_NO_UNIQUE';


  // This three variables contain information about the table layout
  protected static $uniqueKey;    // Unique- or primary-key of the table (This will always be treated as integer)
  protected static $tableName;    // Name of the table
  protected static $tableKeys;    // List (Associative-Array) of table keys (or fields) together
                                  // with the corresponding ilDB type (integer, float, boolean, text, etc.)


  // This variable stores exactly one table entry (attached to class instance)
  protected $row;


  /**
   * Constructor: RESTDatabase($row)
   *  This constructor is intentionally privat (or protected)
   *  to prevent it from beeing called directly, use one of
   *  the RESTDatabase::from*(...) factory-methods instead.
   *  It will create a new instance from the data provided
   *  by the $row parameter.
   *
   * Parameters:
   *  $row <Array[Mixed]> - An array with the -exact- same keys as set by static::$tableKeys, only the
   *                        unique-key is optional. (Without it, certain methods, like read() will not work!)
   */
  protected function __construct($row) {
    // Check that input is of correct type (array)
    if (!is_array($row))
      throw new Exceptions\Database(sprintf(self::MSG_WRONG_ROW_TYPE, gettype($row)), self::ID_WRONG_ROW_TYPE);

    // Since the unique-key is optional, remember (via false) if it wasn't given
    if (!isset($row[static::$uniqueKey]))
      $row[static::$uniqueKey] = false;

    // Check if input-data has correct number of keys
    if (count($row) != count(static::$tableKeys))
      throw new Exceptions\Database(sprintf(self::MSG_WRONG_ROW_SIZE, count(static::$tableKeys), count($row)), self::ID_WRONG_ROW_SIZE);

    // Update internal storage with given data
    // Note: This method will fail, if data contains a wrong key, one not contained in static::$tableKeys!
    $this->row = array();
    foreach($row as $key => $value)
      $this->setKey($key, $value, false);
  }


  /**
   * Factory-Method: RESTDatabase::fromRow($row)
   *  Creates a new RESTDatabase-Instance from given input parameters.
   *  This method accepts a table-entry represented as an array.
   *  Since all data is contained inside $row no DB query is needed.
   *
   * Parameters:
   *  @See RESTDatabase($row) for parameter description
   *
   * Return:
   *  <RESTDatabase> - New instance of RESTDatabase created from input parameters
   */
  public static function fromRow($row) {
    // Return table-data as new instance
    return new static($row);
  }


  /**
   * Factory-Method: RESTDatabase::fromUnique($value)
   *  Creates a new RESTDatabase-Instance from given input parameters.
   *  This method recieves the table-data by fetching the table
   *  entry with unique-key matching the input parameter.
   *
   * Parameters:
   *  $value <Integer> - Unique-Key value used to fetch table-data from the database
   *
   * Return:
   *  <RESTDatabase> - New instance of RESTDatabase fetched via input parameters
   */
  public static function fromUnique($value) {
    // Generate a where-clase for the unique-key
    $key    = static::$uniqueKey;
    $where  = sprintf('%s = %d', $key, intval($value));

    // Return table-data by way of a where-query as new instance
    return self::fromWhere($where);
  }


  /**
   * Factory-Method: RESTDatabase::fromWhere($where, $joinWith, $limit)
   *  Creates a new RESTDatabase-Instance from given input parameters.
   *  This method recieves the table-data by fetching the table
   *  entry via a simple
   *    SELECT * FROM static::$tableName WHERE $where
   *  Were the $where-clause is given as parameter. Additionally it
   *  supports INNER-JOIN queries, such as
   *    SELECT * FROM static::$tableName JOIN $joinWith::$tableName ON ... WHERE $where
   *  See RESTDatabase::getJoinKey($joinKey) for additional details.
   *  Furthermore $where is stati-parsed, see RESTDatabase::parseStaticSQL($sql)
   *  for more information.
   *
   * Note:
   *  Unlike the other factory-methods the $where-Parameter can be exploited
   *  to generate malformed requests. Each caller is responsible to make
   *  sure $where is a valid where-clause using its own logic!
   *
   * Parameters:
   *  $where <String> - Valid SQL where-clause (Needs to be validated by the caller!)
   *  $joinWith <String> - [Optional] Allows to run a INNER-JOIN query on supported join-tables, use fully quantified classname
   *  $limit <Boolean/Integer> - [Optional] Limit the number of fetches entries (default: 1)
   *  $offset <Boolean/Integer> - [Optional] Can be used in conjuction with $limit to fetch additional entries.
   *
   * Return:
   *  <RESTDatabase/Array[RESTDatabase]> - New instance(s) of RESTDatabase fetched via input parameters
   */
  public static function fromWhere($where, $joinWith = null, $limit = false, $offset = false) {
    // Static-Parse the where-clause (replacing {{table}} and {{unique}})
    $where = self::parseStaticSQL($where);

    // Optional additions to sql-query
    $limitSQL   = '';
    $offsetSQL  = '';
    $joinSql    = '';
    $sql        = '';

    // Generate LIMIT and OFFSET sql sub-queries
    if ($limit)
      $limitSQL   = sprintf('LIMIT %d', $limit);
    if ($offset)
      $offsetSQL  = sprintf('OFFSET %d', $offset);

    // Generate JOIN sql sub-query
    $table        = static::getTableName();
    if (isset($joinWith)) {
      // Fetch key which should be joined against
      $key        = static::getJoinKey($joinWith);

      // Fetch table-name and table-key which should be joined against
      $joinTable  = call_user_func(array($joinWith, 'getTableName'));
      $joinKey    = call_user_func(array($joinWith, 'getJoinKey'), get_called_class());

      // Build JOIN sub-query
      $joinSql    = sprintf('JOIN %s ON %s.%s = %s.%s', $joinTable, $table, $key, $joinTable, $joinKey);
    }

    // Combine all sub-queries into final sql-query
    $sql = sprintf('SELECT %s.* FROM %s %s WHERE %s %s %s', $table, $table, $joinSQL, $where, $limitSQL, $offsetSQL);

    // Generate ilDB query-object
    $query  = self::getDB()->query($sql);
    if ($query)
      // Return more then one table-entry
      if (is_int($limit) && $limit > 0) {
        // Fetch all table-entrys matched by query
        $rows = array();
        while ($row = self::getDB()->fetchAssoc($query))
          $rows[] = new static($row);

        // Return as array of RESTDatabase-Instances
        return $rows;
      }

      // Only return a single (the first entry)
      elseif ($row = self::getDB()->fetchAssoc($query))
        return new static($row);

    // If the function hasn't returned until here, the query must have failed
    throw new Exceptions\Database(sprintf(self::MSG_NO_ENTRY, $sql), self::ID_NO_ENTRY);
  }


  /**
   * Function: getKey($key, $read)
   *  Return the value of $key field inside the table.
   *  The second parameter can be used to controll wether a database query
   *  should be performed to fetch an up-to-date value, otherwise the internally
   *  stored one is used.
   *
   * Parameters:
   *  $key <String> - The key whose data-value should be returned
   *  $read <Boolean> - [Optional] Wether to read the value from the database first [Default: False]
   *
   * Return:
   *  <Mixed> - The value that is attached to the given key
   */
  public function getKey($key, $read = false) {
    // Only keys available inside this table are allowed to be fetched
    if (!array_key_exists($key, static::$tableKeys))
      throw new Exceptions\Database(sprintf(self::MSG_NO_KEY, $key, static::$tableName), self::ID_NO_KEY);

    // Read (possibly updated) value from database first
    if ($read)
      $this->read();

    // Return internal value for key
    return $this->row[$key];
  }


  /**
   * Function: setKey($key, $value, $write)
   *  Change the data-value that is attached to the given key.
   *  The second parameter can be used to controll wether
   *  the database should be updated with the changed value,
   *  or if it should only be stored internally for now.
   *
   * Note:
   *  This method should be overwriten in any implementation
   *  in order to make sure all values get converted to the
   *  right type. This method is called for every key by the
   *  constructor (eg. called by one of the factories) or
   *  after a database query wants to update the internal
   *  $this->row storage.
   *  By default all data read from the database is of type
   *  String and should be converted to its desired format
   *  here! Unique-Keys will be converted to integer by default.
   *
   * Parameters:
   *  $key <String> - The key that should have its data-value changed
   *  $value <Mixed> - The value that should be attached to the given key
   *  $write <Boolean> - [Optional] Set to true to write change to database (Default: False)
   *
   * Return:
   *  <ilDB.query> - ilDB-Object, only returned on failure
   */
  public function setKey($key, $value, $write = false) {
    // Only keys available inside this table are allowed to be changed
    if (!array_key_exists($key, static::$tableKeys))
      throw new Exceptions\Database(sprintf(self::MSG_NO_KEY, $key, static::$tableName), self::ID_NO_KEY);

    // Convert unique-key to integer
    // Note: All further type-changes need to be managed by derived implementations!
    if ($key == static::$uniqueKey && $value != false)
      $value = intval($value);

    // Update internal stored value for key
    $this->row[$key] = $value;

    // ... and write changes to database?
    if ($write)
      return $this->write($key);
  }


  // Requires $uniqueKey
  public function read() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];

    if ($value == false)
      throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey), self::ID_NO_UNIQUE);

    $sql    = sprintf('SELECT * FROM %s WHERE %s = %d', static::$tableName, $key, intval($value));
    $query  = self::getDB()->query($sql);
    if ($query) {
      $row = self::getDB()->fetchAssoc($query);

      foreach($row as $key => $value)
        $this->setKey($key, $value);

      return $row;
    }

    throw new Exceptions\Database(sprintf(self::MSG_NO_ENTRY, $sql), self::ID_NO_ENTRY);
  }


  public function write($key = null) {
    if ($this->exists())
      return $this->update($key);
    else
      return $this->insert();
  }


  // Requires $uniqueKey
  public function update($key = null) {
    $row    = $this->getDBRow($key);
    $where  = $this->getDBWhere();
    return self::getDB()->update(static::$tableName, $row, $where);
  }


  public function insert($newUnique = false) {
    $row = $this->getDBRow();

    if ($newUnique || $row[static::$uniqueKey] == false)
      unset($row[static::$uniqueKey]);

    $result = self::getDB()->insert(static::$tableName, $row);
    $id     = self::getDB()->getLastInsertId();
    $this->setKey(static::$uniqueKey, $id);

    return $result;
  }


  // Requires $uniqueKey
  public function exists() {
    $key    = static::$uniqueKey;
    $value  = $this->row[$key];

    if ($value == false)
      throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey), self::ID_NO_UNIQUE);

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

    if ($value == false)
      throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey), self::ID_NO_UNIQUE);

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
    if ($parse)
      $sql = $this->parseSQL($sql);

    return self::fetchStatic($sql, false);
  }


  public static function fetchStatic($sql, $parse = true) {
    if ($parse)
      $sql = self::parseStaticSQL($sql);

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
    if ($parse)
      $sql = $this->parseSQL($sql);

    return self::manipulateStatic($sql, false);
  }


  public static function manipulateStatic($sql, $parse = true) {
    if ($parse)
      $sql = self::parseStaticSQL($sql);

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
    if (isset($key))
      return $this-getKey($key);
    else
      return $this->row;
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

    if ($value == false)
      throw new Exceptions\Database(sprintf(self::MSG_NO_UNIQUE, static::$tableName, static::$uniqueKey), self::ID_NO_UNIQUE);

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
