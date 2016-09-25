<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// Requires <$ilDB>


/**
 * Abstract-Class: RESTDatabase
 *  Base-Class for all classes that represent/abstract
 *  a table (or table-entry) inside the SQL-Database.
 *  (Mostly usefull for the plugins own database tables,
 *  since ILIAS tables already should have class
 *  representations)
 *
 * Note:
 *  This class depends on the assumption that each table/table-entry
 *  that it manages is (or can be) attached to a UNIQUE primary-key.
 */
abstract class RESTDatabase {
  // Allow to re-use status messages and codes
  const MSG_WRONG_ROW_TYPE  = 'Constructor requires first parameter of type array, but it is: {{type}}.';
  const ID_WRONG_ROW_TYPE   = 'RESTController\\libs\\RESTDatabase::ID_WRONG_ROW_TYPE';
  const MSG_WRONG_ROW_SIZE  = 'Constructor requires first parameter to be an array of size {{required}} (with optional primary-key), but it is {{input}}.';
  const ID_WRONG_ROW_SIZE   = 'RESTController\\libs\\RESTDatabase::ID_WRONG_ROW_SIZE';
  const MSG_NO_ENTRY        = 'Could not find entry for query: {{sql}}';
  const ID_NO_ENTRY         = 'RESTController\\libs\\RESTDatabase::ID_NO_ENTRY';
  const MSG_NO_KEY          = 'There is no key "{{key}}" in table "{{table}}".';
  const ID_NO_KEY           = 'RESTController\\libs\\RESTDatabase::ID_NO_KEY';
  const MSG_NO_PRIMARY      = 'Operation not possible, missing value for primary-key ({{table}}.{{primary}}).';
  const ID_NO_PRIMARY       = 'RESTController\\libs\\RESTDatabase::ID_NO_PRIMARY';


  // This three variables contain information about the table layout
  protected static $primaryKey;    // Primary- or primary-key of the table (This will always be treated as integer)
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
   *                        primary-key is optional. (Without it, certain methods, like read() will not work!)
   */
  protected function __construct($row) {
    // Check that input is of correct type (array)
    if (!is_array($row))
      throw new Exceptions\Database(
        self::MSG_WRONG_ROW_TYPE,
        self::ID_WRONG_ROW_TYPE,
        array(
          'type' => gettype($row)
        )
      );

    // Since the primary-key is optional, remember (via false) if it wasn't given
    if (!isset($row[static::$primaryKey]))
      $row[static::$primaryKey] = false;

    // Check if input-data has correct number of keys
    if (count($row) != count(static::$tableKeys))
      throw new Exceptions\Database(
        self::MSG_WRONG_ROW_SIZE,
        self::ID_WRONG_ROW_SIZE,
        array(
          'required'  => count(static::$tableKeys),
          'input'     => count($row)
        )
      );

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
   * Factory-Method: RESTDatabase::fromPrimary($value)
   *  Creates a new RESTDatabase-Instance from given input parameters.
   *  This method recieves the table-data by fetching the table
   *  entry with primary-key matching the input parameter.
   *
   * Parameters:
   *  $value <Integer> - Primary-Key value used to fetch table-data from the database
   *
   * Return:
   *  <RESTDatabase> - New instance of RESTDatabase fetched via input parameters
   */
  public static function fromPrimary($value) {
    // Generate a where-clase for the primary-key
    $key    = static::$primaryKey;
    $where  = sprintf('%s = %d', $key, intval($value));

    // Return table-data by way of a where-query as new instance
    return self::fromWhere($where);
  }


  /**
   * Factory-Method: RESTDatabase::fromWhere($where, $limit, $offset)
   *  Creates a new RESTDatabase-Instance from given input parameters.
   *  This method recieves the table-data by fetching the table
   *  entry via a simple
   *    SELECT * FROM static::$tableName WHERE $where
   *  Were the $where-clause is given as parameter.
   *
   * Note 1:
   *  Unlike the other factory-methods the $where-Parameter can be exploited
   *  to generate malformed requests. Each caller is responsible to make
   *  sure $where is a valid where-clause using its own logic!
   *  (For example making sure all parameters are escaped correctly)
   *
   * Note 2:
   *  The table name will be aliased as the (late static) name of its class, eg.
   *  for RESTclient a where clause could also be 'RESTclient.id = 42' instead of 'ui_uihk_rest_client.id = 42'
   *  This also happens for joined tables, eg joinWith = 'RESTpermission' will create an alias RESTpermission for
   *  ui_uihk_rest_perm.
   *
   * Parameters:
   *  $where <String> - [Optional] Valid SQL where-clause (Needs to be validated by the caller!), leave empty to fetch all rows
   *  $limit <Boolean/Integer> - [Optional] Limit the number of fetches entries (default: 1), pass false to return array
   *  $offset <Boolean/Integer> - [Optional] Can be used in conjuction with $limit to fetch additional entries.
   *  $joinWith <String>/Array[String]> - [Optional] A list of database class names to join on
   *
   * Return:
   *  <Array[RESTDatabase]> - New instances of RESTDatabase fetched via input parameters
   */
  public static function fromWhere($where = null, $limit = false, $offset = false, $joinWith = null) {
    // Reset empty where-clause
    $whereSQL = '';
    if (!is_null($where))
      $whereSQL = sprintf('WHERE %s', $where);

    // Build a join query
    $joinSQL = '';
    if (!is_null($joinWith))
      $joinSQL = self::joinQuery($joinWith);

    // Generate LIMIT sub-queries
    $limitSQL = '';
    if (ctype_digit($limit))
      $limitSQL   = sprintf('LIMIT %d', intval($limit));

    // Generate OFFSET sub-queries
    $offsetSQL = '';
    if (ctype_digit($offset))
      $offsetSQL  = sprintf('OFFSET %d', intval($offset));

    // Build a simple where-based query
    $table  = static::getTableName();
    $class  = end(explode('\\', get_called_class()));
    $sql    = sprintf('SELECT %s.* FROM %s AS %s %s %s %s %s', $class, $table, $class, $joinSQL, $whereSQL, $limitSQL, $offsetSQL);

    // Generate ilDB query-object
    $query  = self::getDB()->query($sql);
    if ($query)
      if ($limit) {
        // Fetch all table-entrys matched by query
        $rows   = array();
        while ($row = self::getDB()->fetchAssoc($query))
          $rows[] = new static($row);

        // Return as array of RESTDatabase-Instances
        if (count($rows) > 0)
          return $rows;
      }

      // Only return a single (the first entry)
      elseif ($row = self::getDB()->fetchAssoc($query))
        return new static($row);

    // Throw exception if no row was found or query was empty
    throw new Exceptions\Database(
      self::MSG_NO_ENTRY,
      self::ID_NO_ENTRY,
      array(
        'sql' => $sql
      )
    );
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
      throw new Exceptions\Database(
        self::MSG_NO_KEY,
        self::ID_NO_KEY,
        array(
          'key'   => $key,
          'table' => static::$tableName
        )
      );

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
   *  here! Primary-Keys will be converted to integer by default.
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
      throw new Exceptions\Database(
        self::MSG_NO_KEY,
        self::ID_NO_KEY,
        array(
          'key'   => $key,
          'table' => static::$tableName
        )
      );

    // Convert primary-key to integer
    // Note: All further type-changes need to be managed by derived implementations!
    if ($key == static::$primaryKey && $value != false)
      $value = intval($value);

    // Update internal stored value for key
    $this->row[$key] = $value;

    // ... and write changes to database?
    if ($write)
      return $this->write($key);
  }


  /**
   * Function: read()
   *  This method will read the db entry given by the internally
   *  stored primary-key value and updates the internal storage
   *  with the recieved data.
   *
   * Note:
   *  Obviously this method will need to already know a valid primary-key
   *  for this table (either from one of the factories or via setKey(...))
   *  to work properly, since it ONLY looks for the table entry via its primary-key.
   *
   * Return:
   *  <Array[Mixed]> - Updated table-data
   */
  public function read() {
    // Fetch value of primary-key
    $key    = static::$primaryKey;
    $value  = $this->row[$key];

    // 'FALSE' Primary-Key explicitely means: non was set -> Use ALL keys
    if ($value === false) {
      // Build safe where-clause
      $where = array();
      foreach($this->row as $key => $value) {
        // Skip null primary-key
        if ($key == static::$primaryKey && $value === false)
          continue;

        // Add where clauses
        $type     = static::$tableKeys[$key];
        $where[]  = sprintf('%s.%s = %s.%s', static::$tableName, $key, static::$tableName, self::quote($value, $type));
      }
      $where = implode($where, ' AND ');
    }
    // Use primary-key only
    else
      $where = sprintf('%s = %d', $key, intval($value));

    // Build sql-query to fetch table-entry data
    $sql    = sprintf('SELECT * FROM %s WHERE %s', static::$tableName, $where);
    $query  = self::getDB()->query($sql);
    if ($query) {
      // Fetch and process first row (should be the only row, since primary-keys are unique)
      $row = self::getDB()->fetchAssoc($query);
      if ($row) {
        foreach($row as $key => $value)
          $this->setKey($key, $value);

        // Return proccessed row
        return $this->row;
      }
    }

    // If not returned by now, something must have gone wrong
    throw new Exceptions\Database(
      self::MSG_NO_ENTRY,
      self::ID_NO_ENTRY,
      array(
        'sql' => $sql
      )
    );
  }


  /**
   * Function: write($key)
   *  Writes the internally stored table-data, either inserting a new entry
   *  into the table or updating an existing one.
   *  If a table with the internally stored primary-key does already exist,
   *  it will be updated, otherwise a new table-entry will be generated and
   *  its (new) primary-key stored internally.
   *  To allways do an update or insert, use those methods instead.
   *
   * Parameters:
   *  $key <String> - [Optional] If this parameter is given, only the value
   *                  stored under the given key will be updated. (No effect on insert)
   *
   * Return:
   *  <ilDB.query> - Same return value as given by an ilDB->update() or ilDB->insert() operation
   */
  public function write($key = null) {
    // Update existing table-entry
    if ($this->exists())
      return $this->update($key);

    // Insert new table-entry
    else
      return $this->insert();
  }


  /**
   * Function: update($key)
   *  Tries to update the table which matches the internally stored
   *  primary-key with the internally stored table-data.
   *
   * Parameters:
   *  $key <String> - [Optional] If this parameter is given, only the value
   *                  stored under the given key will be updated.
   *
   * Return:
   *  <ilDB.query> - Same return value as given by an ilDB->update() operation
   */
  public function update($key = null) {
    // Generate parameters required for ilDB update-query (see methods of details)
    $row    = $this->getDBRow($key);
    $where  = $this->getDBWhere();

    // Invoke table-update via ilDB
    return self::getDB()->update(static::$tableName, $row, $where);
  }


  /**
   * Function: insert($newPrimary)
   *  Inserts a new table-entry with the given internally stored
   *  table-data. Unless explicitely requested a new primary-key
   *  will be generated by the database and stored internally.
   *  This behaviour can be changed by passing false as first paremeter
   *  which will try to insert the table-entry with the internally stored
   *  primary-key value.
   *
   * Parameters:
   *  $newPrimary <Boolean> - [Optional] Ignore internal primary-key and let the database assign one (Default: True)
   *
   * Return:
   *  <ilDB.query> - Same return value as given by an ilDB->insert() operation
   */
  public function insert($newPrimary = true) {
    // Generate parameter required for ilDB update-query (see methods of details)
    $row = $this->getDBRow();

    // Remove primary-key from query-parameter? ('FALSE' primary-key means: non was set in the first place)
    if ($newPrimary || $row[static::$primaryKey] === false)
      unset($row[static::$primaryKey]);

    // Insert data into db table and update internal primary-key
    $result = self::getDB()->insert(static::$tableName, $row);

    // Remove primary-key from query-parameter? ('FALSE' primary-key means: non was set in the first place)
    if ($newPrimary || $row[static::$primaryKey] === false) {
      $id   = self::getDB()->getLastInsertId();
      $this->setKey(static::$primaryKey, $id);
    }

    // Return ilDB result object
    return $result;
  }


  /**
   * Function: exists($where, $parse)
   *  By default the table-entry existance is checked by comparing ALL fields (null primary-keys are ignored).
   *  Optionally a (instance-parsed) where-clause can be used to make the check
   *  more flexible, see RESTDatabase->parseSQL($sql) for more information.
   *
   * Note 1: (with $where)
   *  The $where-Parameter can be exploited to generate malformed requests.
   *  Each caller is responsible to make sure $where is a valid where-clause
   *  using its own logic! (For example making sure all parameters are escaped correctly)
   *
   * Parameters:
   *  $where <String> - [Optional] Valid SQL where-clause (Needs to be validated by the caller!)
   *  $parse <Boolean> - [Optional] Pass false to disable parsing of {{}} entries in SQL query (Default: True)
   *
   * Return:
   *  <Boolean> - True if there already exists a table with the given primary-key, false otherwise
   */
  public function exists($where = null, $parse = true) {
    // Check existance by given (parsed) where-clause
    if (isset($where)) {
      // Parse the where-clause (replacing {{table}}, {{primary}} and {{'key'}})
      if ($parse)
        $where = self::parseSQL($where);

      // Delegate actual query to generalized implementation
      return self::existsByWhere($where);
    }

    // Check existence using all internal keys
    else {
      $primaryValue = $this->row[static::$primaryKey];
      if ($primaryValue === false) {
        // Build safe where-clause
        $where = array();
        foreach($this->row as $key => $value) {
          // Skip null primary-key
          if ($key == static::$primaryKey)
            continue;

          // Add where clauses
          $type     = static::$tableKeys[$key];
          $where[]  = sprintf('%s.%s = %s', static::$tableName, $key, self::quote($value, $type));
        }
        $where = implode($where, ' AND ');
      }
      else
        $where = sprintf('%s.%s = %s', static::$tableName, static::$primaryKey, $primaryValue);

      // Delete actual query
      return self::existsByWhere($where);
    }
  }


  /**
   * Function: existsByPrimary($value)
   *  Checks wether the table-entry given by the parameter-value primary-key
   *  exists. This will ONLY check using the primary-key, use existsByWhere(...)
   *  for more advanced queries.
   * Note: Prefer existsByPrimary() over exixts() when you know the correct primary-key
   *       (internally or externally as parameter), since primary-keys are always unique!
   *
   * Parameters:
   *  $value <Integer> - Value of primary-key to check existance of a table-entry for
   *                     If left empty, this will treat value as the current primary-key value
   *
   * Return:
   *  <Boolean> - True if there already exists a table with the given primary-key, false otherwise
   */
  public static function existsByPrimary($value) {
    // Generate a where-clause for the primary-key
    $key    = static::$primaryKey;
    $where  = sprintf('%s = %d', $key, $value);

    // Delegate actual query to generalized implementation
    return self::existsByWhere($where);
  }


  /**
   * Function: existsByWhere($where)
   *  Check wether the table-entry exists, by fetching number of affected
   *  table-entries via a simple:
   *    SELECT 1 FROM static::$tableName WHERE $where
   *  Were the $where-clause is given as parameter.
   *
   * Note 1:
   *  Unlike the other exists*-methods the $where-Parameter can be exploited
   *  to generate malformed requests. Each caller is responsible to make
   *  sure $where is a valid where-clause using its own logic!
   *  (For example making sure all parameters are escaped correctly)
   *
   * Note 2:
   *  The table name will be aliased as the (late static) name of its class, eg.
   *  for RESTclient a where clause could also be 'RESTclient.id = 42' instead of 'ui_uihk_rest_client.id = 42'
   *  This also happens for joined tables, eg joinWith = 'RESTpermission' will create an alias RESTpermission for
   *  ui_uihk_rest_perm.
   *
   * Parameters:
   *  $where <String> - Valid SQL where-clause (Needs to be validated by the caller!)
   *  $joinWith <String>/Array[String]> - [Optional] A list of database class names to join on
   *
   * Return:
   *  <Boolean> - True if there already exists a table with the given primary-key, false otherwise
   */
  public static function existsByWhere($where, $joinWith = null) {
    // Build a join query
    $joinSQL = '';
    if (!is_null($joinWith))
      $joinSQL = self::joinQuery($joinWith);

    // Generate query
    $class  = self::getName();
    $sql    = sprintf('SELECT 1 FROM %s AS %s %s WHERE %s', static::$tableName, $class, $joinSQL, $where);
    $query  = self::getDB()->query($sql);

    // Fetch number of returned rows
    return self::getDB()->numRows($query) > 0;
  }


  /**
   * Function: delete($where, $parse)
   *  By default the table-entry is deleted with a where-clause containing all table fields.
   *  (Only the primary-key is ignored if null)
   *  Optionally a (instance-parsed) where-clause can be used to make the deletion
   *  more flexible, see RESTDatabase->parseSQL($sql) for more information.
   *
   * Note 1: (with $where)
   *  The $where-Parameter can be exploited to generate malformed requests.
   *  Each caller is responsible to make sure $where is a valid where-clause
   *  using its own logic! (For example making sure all parameters are escaped correctly)
   *
   * Parameters:
   *  $where <String> - [Optional] Valid SQL where-clause (Needs to be validated by the caller!)
   *  $parse <Boolean> - [Optional] Pass false to disable parsing of {{}} entries in SQL query (Default: True)
   *
   * Return:
   *  <ilDB.query> - Same return value as given by an ilDB->manipulate() operation
   */
  public function delete($where = null, $parse = true) {
    // Delete by given (parsed) where-clause
    if (isset($where)) {
      // Parse the where-clause (replacing {{table}}, {{primary}} and {{'key'}})
      if ($parse)
        $where = self::parseSQL($where);

      // Delegate actual query to generalized implementation
      return self::deleteByWhere($where);
    }

    // Delete using ALL internal keys
    else {
      $class = self::getName();
      $primaryValue = $this->row[static::$primaryKey];

      if ($primaryValue === false) {
        // Build safe where-clause
        $where = array();
        foreach($this->row as $key => $value) {
          // Skip null primary-key
          if ($key == static::$primaryKey)
            continue;

          // Add where clauses
          $type     = static::$tableKeys[$key];
          $where[]  = sprintf('%s.%s = %s', $class, $key, self::quote($value, $type));
        }
        $where = implode($where, ' AND ');
      }
      else
        $where = sprintf('%s.%s = %s', $class, static::$primaryKey, $primaryValue);

      // Delete actual query
      return self::deleteByWhere($where);
    }
  }


  /**
   * Function: deleteByPrimary($value)
   *  Deletes the table-entry given by the parameter-value primary-key.
   *  This will select the table ONLY using the primary-key, use deleteByWhere(...)
   *  for more advanced delete-requests.
   * Note: Prefer deleteByPrimary() over delete() when you know the correct primary-key
   *       (internally or externally as parameter), since primary-keys are always unique!
   *
   * Parameters:
   *  $value <Integer> - Value of primary-key to delete table-entry of
   *                     If left empty, this will treat value as the current primary-key value
   *
   * Return:
   *  <ilDB.query> - Same return value as given by an ilDB->manipulate() operation
   */
  public static function deleteByPrimary($value) {
    // Generate a where-clause for the primary-key
    $key    = static::$primaryKey;
    $where  = sprintf('%s = %d', $key, intval($value));

    // Delegate actual query to generalized implementation
    return self::deleteByWhere($where);
  }


  /**
   * Function: deleteByWhere($where)
   *  Deletes the table-entries matching the given where-clause, via a simple:
   *    DELETE FROM static::$tableName WHERE $where
   *  Were the $where-clause is given as parameter.
   *
   * Note 1:
   *  Unlike the other exists*-methods the $where-Parameter can be exploited
   *  to generate malformed requests. Each caller is responsible to make
   *  sure $where is a valid where-clause using its own logic!
   *  (For example making sure all parameters are escaped correctly)
   *
   * Note 2:
   *  The table name will be aliased as the (late static) name of its class, eg.
   *  for RESTclient a where clause could also be 'RESTclient.id = 42' instead of 'ui_uihk_rest_client.id = 42'
   *  This also happens for joined tables, eg joinWith = 'RESTpermission' will create an alias RESTpermission for
   *  ui_uihk_rest_perm.
   *
   * Parameters:
   *  $where <String> - Valid SQL where-clause (Needs to be validated by the caller!)
   *  $joinWith <String>/Array[String]> - [Optional] A list of database class names to join on
   *
   * Return:
   *  <Boolean> - True if there already exists a table with the given primary-key, false otherwise
   */
  public static function deleteByWhere($where, $joinWith = null) {
    // Build a join query
    $joinSQL = '';
    if (!is_null($joinWith))
      $joinSQL = self::joinQuery($joinWith);

    // Generate query
    $class  = self::getName();
    $sql    = sprintf('DELETE %s FROM %s %s %s WHERE %s', $class, static::$tableName, $class, $joinSQL, $where);
    return self::getDB()->manipulate($sql);
  }


  /**
   * Function: fetch($sql, $parse)
   *  Convenience function for ilDB->fetchAssoc(...) that simplifies fetching
   *  multiple rows (as Array of Array of String) from the attached table.
   *  Furthermore $sql is instance-parsed, if not explicitely disabled, see
   *  $this->parseSQL($sql) for more information.
   *
   * Note:
   *  The $sql-Parameter can be exploited to generate malformed requests.
   *  Each caller is responsible to make sure $sql is a valid sql-query that
   *  does not contain any 'exploitable' sql-statements using its own logic!
   *  (For example making sure all parameters are escaped correctly)
   *
   * Parameters:
   *  $sql <String> - Valid SQL query, with optional parsing of internal table-data
   *  $parse <Boolean> - [Optional] Pass false to disable parsing of {{}} entries in SQL query (Default: True)
   *
   * Return:
   *  <Array[Array[String]]> - Array containing all rows affected by SQL query
   */
  public function fetch($sql, $parse = true) {
    // Instance-Parse sql-request, replacing {{'keys'}}, {{table}}, {{primary}}, etc.
    if ($parse)
      $sql = $this->parseSQL($sql);

    // Delegate actual query to generalized implementation
    return self::fetchStatic($sql, false);
  }


  /**
   * Function: fetchStatic($sql, $parse)
   *  @See RESTDatabase->fetch($sql, $parse), for detailed description.
   *
   *  Only static-parses the sql-query, see RESTDatabase::parseStaticSQL($sql)
   *  for more information.
   */
  public static function fetchStatic($sql, $parse = true) {
    // Static-Parse sql-request, replacing {{table}}, {{primary}}, etc.
    if ($parse)
      $sql = self::parseStaticSQL($sql);

    // Generate ilDB query object
    $rows   = array();
    $query  = self::getDB()->query($sql);
    if ($query) {
      // Fetch all matching rows
      while($row = self::getDB()->fetchAssoc($query))
        $rows[] = $row;
    }

    // Return array containing all affected rows (or empty array on error)
    return $rows;
  }


  /**
   * Function: manipulate($sql, $parse)
   *  Convenience function for ilDB->manipulate(...) that simplifies executing
   *  arbitrary sql queires on the attached table.
   *  Furthermore $sql is instance-parsed, if not explicitely disabled, see
   *  $this->parseSQL($sql) for more information.
   *
   * Note:
   *  The $sql-Parameter can be exploited to generate malformed requests.
   *  Each caller is responsible to make sure $sql is a valid sql-query that
   *  does not contain any 'exploitable' sql-statements using its own logic!
   *  (For example making sure all parameters are escaped correctly)
   *
   * Parameters:
   *  $sql <String> - Valid SQL query, with optional parsing of internal table-data
   *  $parse <Boolean> - [Optional] Pass false to disable parsing of {{}} entries in SQL query (Default: True)
   *
   * Return:
   *  <ilDB.query> - Same return value as given by an ilDB->manipulate() operation
   */
  public function manipulate($sql, $parse = true) {
    // Instance-Parse sql-request, replacing {{'keys'}}, {{table}}, {{primary}}, etc.
    if ($parse)
      $sql = $this->parseSQL($sql);

    // Delegate actual query to generalized implementation
    return self::manipulateStatic($sql, false);
  }


  /**
   * Function: manipulateStatic($sql, $parse)
   *  @See RESTDatabase->manipulate($sql, $parse), for detailed description.
   *
   *  Only static-parses the sql-query, see RESTDatabase::parseStaticSQL($sql)
   *  for more information.
   */
  public static function manipulateStatic($sql, $parse = true) {
    // Static-Parse sql-request, replacing {{table}}, {{primary}}, etc.
    if ($parse)
      $sql = self::parseStaticSQL($sql);

    // Execute sql query and return query object
    return self::getDB()->manipulate($sql);
  }


  /**
   * Function: parseSQL($sql)
   *  Replaces certain 'needles' inside the sql query (string) with internally stored values.
   *  Currently supported needles are:
   *   {{table}} - Will be replaced by static::$tableName (which should contain the name of the attached table)
   *   {{name}} - Will be replaced with the (late binding) class name (without namespaces)
   *   {{primary}} - Will be replaced by static::$primaryKey /which should be the name of the tables primary-key)
   *   {{KEY}} - Will be replaced by $this->getKey(KEY)
   *   {{%KEY}} - Will be replaced by $this->getKey(KEY), usefull if table contains a key named after one of the above
   *
   * Note:
   *  All {{}} needles will be correctly quoted (and escaped) by ilDB when replacing them with the internal values.
   *
   * Parameters:
   *  $sql <String> - SQL query that should be parsed
   *
   * Return:
   *  <String> - Parsed SQL query
   */
  public function parseSQL($sql) {
    // Delegate parsing of static value to generalized implementation
    $sql = self::parseStaticSQL($sql);

    // Replace {{KEY}} with the (correctly quoted) value of getKey(KEY)
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

    // Return parsed sql query
    return $sql;
  }


  /**
   * Function: parseStaticSQL($sql)
   *  Replaces certain 'needles' inside the sql query (string) with internally stored values.
   *  Currently supported needles are:
   *   {{table}} - Will be replaced by static::$tableName (which should contain the name of the attached table)
   *   {{name}} - Will be replaced with the (late binding) class name (without namespaces)
   *   {{primary}} - Will be replaced by static::$primaryKey /which should be the name of the tables primary-key)
   *  Obviously the static method can not access instance information, such as getKey(...).
   *
   * Note:
   *  All {{}} needles will be correctly quoted (and escaped) by ilDB when replacing them with the internal values.
   *
   * Parameters:
   *  $sql <String> - SQL query that should be parsed
   *
   * Return:
   *  <String> - Parsed SQL query
   */
  public static function parseStaticSQL($sql) {
    // Replace {{table}} and {{primary}} as described
    $sql = str_replace('{{table}}',   static::$tableName, $sql);
    $sql = str_replace('{{name}}',    static::getName(), $sql);
    $sql = str_replace('{{primary}}', static::$primaryKey, $sql);

    // Return parsed sql query
    return $sql;
  }


  /**
   * Function: safeSQL($sql, ...)
   *  Correctly quotes the additional input parameters given by '...' and inserts
   *  them into the sql-query given as first parameter using vsprintf on the
   *  (correclty quoted) array remaining parameters.
   *
   * Note:
   *  Use this to insert variables safely into you own (arbitrary) SQL statement.
   *
   * Parameters:
   *  $sql <String> - SQL-Query that contains placeholders for the elements from '...'
   *  ... <Mixed> - [Optional Parameter-List] Values that should be quoted and inserted into $sql
   *                Each additional parameter can also be an array with the first element the value and
   *                the second value its desired ilDB->quote(value, type) type.
   *
   * Return:
   *  <String> - Input sql query with all placeholders (see vsprintf()) replaced with correctly quoted
   *             elements from the array of remaining parameters
   */
  public static function safeSQL($sql) {
    // Fetch list of parameters
    $params = func_get_args();
    $params = array_slice($params, 1);

    // Correctly quote parameters (as good as can be automated)
    $quoted = array();
    foreach ($params as $key => $value)
      // Got (value, type) array as parameter
      if (is_array($value) && count($value) == 2)
        $quoted[$key] = self::quote($value[0], $value[1]);

      // Simply for value as parameter
      else
        $quoted[$key] = self::quote($value);

    // Replace placeholders in $sql with correctly quoted parameters
    return vsprintf($sql, $quoted);
  }


  /**
   * Function: quote($value, $type)
   *  Correctly quotes the given value such that it can be savely used inside an sql-query even if
   *  value may contain user-defined data. (This asumes ilDB is smart enough to quote AND escape quotes!)
   *  The second parameter can (and should) be used to set the correct ilDB->quote(value, type) type
   *  used for quoting. Otherwise its derived from the values type at run-time.
   *
   * Parameters:
   *  $value <Mixed> - Value that should be quoted (Can be any value that can be converted to string)
   *  $type <String> - [Optional] Type used to for ilDb->quote($value, $type) to ensure correct escaping
   *                   If no type is given, it is tried to detect the correct type from $value's type,
   *                   so make sure $value if of correct type (supported boolean, string, int, float, double,
   *                   or any numeric value and arrays of depth one with mentioned keys as content)
   *
   * Return:
   *  <String> - Correctly quoted (and escaped) string that can savely be used inside a sql query.
   */
  public static function quote($value, $type = null) {
    // Return quoted as given type
    if (isset($type))
      return self::getDB()->quote($value, $type);

    // Return by detected type: Boolean
    else if (is_bool($value))
      return self::getDB()->quote($value, 'integer');

    // Return by detected type: Integer
    elseif (ctype_digit($value))
      return self::getDB()->quote($value, 'integer');

    // Return by detected type: Float/double
    elseif (is_float($value) || is_double($value) || is_numeric($value))
      return self::getDB()->quote($value, 'float');

    // Return by detected type: String
    elseif (is_string($value))
      return self::getDB()->quote($value, 'text');

    // Return by detected type: Array
    // Note: This makes only sense for arrays of max-depth 1!
    else if (is_array($value)) {
      // Array contains ($value, $type) array?
      if (is_array($value) && count($value) == 2)
        $value = self::quote($value[0], $value[1]);

      // Array contains list of $values
      else
        $value = self::quote($value);

      // All return values need to be strings!
      return sprintf('(%s)', implode(', ', $value));
    }

    // Return by detected type: Unsupported!
    else
      return $value;
  }


  /**
   * Function: joinQuery($joinWith)
   *  Utility function to build a join-on query using the getJoinKey, getTableName and getName methods.
   *  This reduces the need of one table to know the internals of another table.
   *
   * Parameters:
   *  $joinWith <String> - Classname (without namespace) of databse implementation to join with
   *
   * Return:
   *  <String> - Join statement between two tables using their shared/foreign keys
   */
  public static function joinQuery($joinWith) {
    // Information about underlying table
    $table        = static::getTableName();
    $class        = static::getName();

    // Make sure joinWith is an array
    if (!is_array($joinWith))
      $joinWith = array($joinWith);

    // Build complete join query
    $joinTables = array();
    $joinOns    = array();
    foreach ($joinWith as $join) {
      // Construct full class name
      $joinClass    = 'RESTController\\database\\' . $join;

      // Table table-name to join WITH
      $joinTable    = call_user_func(array($joinClass, 'getTableName'));
      $joinTables[] = sprintf('%s AS %s', $joinTable, $join);

      // Tables keys to join ON
      $joinKey      = sprintf('%s.%s',   $join,    call_user_func(array($joinClass, 'getJoinKey'), $class));
      $tableKey     = sprintf('%s.%s',   $class,   static::getJoinKey($join));
      $joinOns[]    = sprintf('%s = %s', $joinKey, $tableKey);
    }

    // Build JOIN sub-query
    $joinTables = implode(', ', $joinTables);
    $joinOns    = implode(' AND ', $joinOns);

    // Return final join query
    return sprintf('JOIN %s ON %s', $joinTables, $joinOns);
  }


  /**
   * Function: getRow($key)
   *  Either returns the internally stored table-entry, aka the 'row'
   *  representing the table-entry, or if an optional key is given,
   *  only return this keys value in the internally stored table-entry.
   *
   * Parameters:
   *  $key <String> - [Optional] Only fetch the value of given keys
   *
   * Return:
   *  <Mixed>/<Array[Mixed]> - Returned row representation of internally stored table-entry (or value of single key)
   */
  public function getRow($key = null) {
    // Return single key?
    if (isset($key))
      return $this-getKey($key);

    // ... or complete table-entry (aka row)
    else
      return $this->row;
  }


  /**
   * Function: getDBRow($key)
   *  Returns the table-entry representation required by ilDB to insert/update **row information**.
   *
   * Note:
   *  While getDBRow() and getDBWhere() can potentially return the same values, they do have different use-cases.
   *  the former providing the table-row data, the later indicating which table row is targeted.
   *
   * Example:
   *    array(
   *      'id'      => array('integer', 10),
   *      'content' => array('text',    'Hello World!')
   *    )
   *
   * Parameters:
   *  $key <String> - [Optional] Only fetch the ilDB (type, value) pair for given keys
   *
   * Return:
   *  <Array[Array[String]]> - List (array) of ilDB (type, value) pairs used to query, insert or update table entries.
   *                           See input of ilDB->insert(...) for additional details.
   */
  public function getDBRow($key = null) {
    // With a given key, only return (type, value) pair for given key
    $row = array();
    if (isset($key)) {
      // Fetch type and value
      $value      = $this->row[$key];
      $type       = static::$tableKeys[$key];

      // Combine (type, value) into pair
      $row[$key]  = array($type, $value);
    }

    // ... otherwise return (type, value) pair for all keys
    else
      foreach($this->row as $key => $value) {
        // Fetch type and combine (type, value) into pair
        $type       = static::$tableKeys[$key];
        $row[$key]  = array($type, $value);
      }

    // Return ilDB table-entry (aka row) representation
    return $row;
  }


  /**
   * Function: getDBWhere()
   *  Returns the **where-clause** representation required by ilDB.
   *  Will use the primary-key if it is known, otherwise all other keys will be used.
   *
   * Note:
   *  While getDBRow() and getDBWhere() can potentially return the same values, they do have different use-cases.
   *  the former providing the table-row data, the later indicating which table row is targeted.
   *
   * Example:
   *    array(
   *      'id'      => array('integer', 10),
   *      'content' => array('text',    'Hello World!')
   *    )
   *
   * Return:
   *  <Array[Array[String]]> - List (array with one element) of ilDB (type, value) pairs used to query, insert or update
   *                           table entries, unlike getDBRow() this only contains the primary-key.
   *                           See input of ilDB->insert(...) for additional details.
   */
  public function getDBWhere() {
    // Fetch type and value of the primary-key
    $key    = static::$primaryKey;
    $value  = $this->row[$key];
    $type   = static::$tableKeys[$key];

    // 'FALSE' Primary-Key explicitely means: non was set -> use other keys
    if ($value === false) {
      $where = array();
      foreach($this->row as $key => $value) {
        // Skip null primary-key
        if ($key == static::$primaryKey && $value === false)
          continue;

        // Fetch type and combine (type, value) into pair
        $type         = static::$tableKeys[$key];
        $where[$key]  = array($type, $value);
      }
      return $where;
    }
    // Combine (type, value) into pair and pack into associative array
    else
      return array(
        $key => array($type, $value)
      );
  }


  /**
   * Function: getPrimaryKey()
   *  Utility-function to return the primary-key used by the attached table.
   *
   * Return:
   *  <String> - Name of primary-key of the attached table
   */
  public static function getPrimaryKey() {
    return static::$primaryKey;
  }


  /**
   * Function: getTableName()
   *  Utility-function to return the name of the attached table.
   *
   * Return:
   *  <String> - Name of the attached table
   */
  public static function getTableName() {
    return static::$tableName;
  }


  /**
   * Function: getTableKeys()
   *  Utility-function to return a list of all keys (and their ilDB type) inside by the attached table.
   *
   * Return:
   *  <String> - List of all keys (and their ilDB type) inside the attached table
   */
  public static function getTableKeys() {
    return static::$tableKeys;
  }


  /**
   * Function: getName()
   *  Returns the (late static binding) class-name (without namespace).
   *
   * Return:
   *  <String> - Short name of current class name (late static binding)
   */
  public static function getName() {
    return end(explode('\\', get_called_class()));
  }


  /**
   * Function: getJoinKey($joinWith)
   *  This method should return the name of the OWN key
   *  (not the key of the $joinTable) which should be used
   *  to join with $joinTable ON.
   *  For example:
   *   IF 'ui_uihk_rest_keys' and 'ui_uihk_rest_perm' want to be joined,
   *   ui_uihk_rest_keys should return 'id' (its primary-key) and
   *   ui_uihk_rest_perm should return 'api_id' when given
   *   each others table-name as input-parameter.
   *   Obviously this means this method needs to be overwriten in
   *   all derived classes that can be joined with another table.
   *
   * Parameters:
   *  $joinWith <String> - Name of CLASS (derived from RESTDatabase) that represents the table with which to join
   *
   * Return:
   *  <String> - OWN Key used to join on with $joinWith
   */
  public static function getJoinKey($joinWith) {
    // By default return own primary-key...
    // Add conditional return values based on $joinWith in derived classes supporting joining
    return static::$primaryKey;
  }


  /**
   * Function: getDB()
   *  Use this to inject global ILIAS-Database into this class.
   *  In a perfect world this would be done via DI instead... >_>
   *
   * Return:
   *  <ilDB> - ILIAS-Database Object
   */
  public static function getDB() {
    return $GLOBALS['ilDB'];
  }
}
