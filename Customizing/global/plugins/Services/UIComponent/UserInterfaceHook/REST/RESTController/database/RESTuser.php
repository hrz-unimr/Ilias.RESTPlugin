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
 * Class: RESTuser (Database-Table)
 *  Abstraction for 'ui_uihk_rest_user' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTuser extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_user';
  protected static $tableKeys   = array(
    'id'      => 'integer',
    'api_id'  => 'integer',
    'user_id' => 'integer'
  );


  /**
   * Function: fromApiId($apiId)
   *  Creates new instance(s) of RESTuser representing the table-entries with given aki-key id.
   *
   * Parameters:
   *  $apiId <String> - Api-Key id who's database entries should be returned
   *
   * Return:
   *  <RESTuser> - A new instance of RESTuser representing the table-entry with given aki-key id
   */
  public static function fromApiId($apiId) {
    // Generate a (save) where clause for the api_id ($apiId can be malformed!)
    $where  = sprintf('api_id = %d', self::quote($apiId, 'integer'));

    // Fetch matching object(s), could be multiple rows
    return self::fromWhere($where, null, true);
  }


  /**
   * Function: fromApiKey($apiKey)
   *  Creates new instance(s) of RESTuser representing the table-entries with given aki-key.
   *
   * Parameters:
   *  $apiKey <String> - Api-Key who's database entries should be returned
   *
   * Return:
   *  <RESTuser> - A new instance of RESTuser representing the table-entry with given aki-key
   */
  public static function fromApiKey($apiKey) {
    // Generate a (save) where clause for the api-key ($apiKey can be malformed!)
    $where  = sprintf('ui_uihk_rest_client.api_key = %s', self::quote($apiKey, 'text'));
    $join   = array(
      'ui_uihk_rest_client' => 'ui_uihk_rest_client.id = ui_uihk_rest_user.api_id'
    );

    // Fetch matching object(s), could be multiple rows (joined on api_id = api_key)
    return self::fromWhere($where, $join, true);
  }


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // Parse input based on key
    switch ($key) {
      // Convert int values
      case 'api_id':
      case 'user_id':
        $value = intval($value);
        break;

      // No default behaviour
      default:
    }

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }


  /**
   * Function: isUserAllowedByKey($apiKey, $userId) / isUserAllowedById($apiId, $userId)
   *  First checks if there exists any entries for the given api-key / api-key id.
   *  If not, no restriction is active, otherwise only users with
   *  their user-id (and given api-key / api-key id) who have an entry are allowed.
   *
   * Parameters:
   *  $apiKey <String> - API-Key used to fetch allowed users for
   *  $apiId <Integer> - API-Key id used to fetch allowed users for
   *  $userId <Integer> - User-Id to check wether he is allowed to use this api-key
   *
   * Return:
   *  <Boolean> - True if the user is allowed to use the given api-key / api-key id, false otherwise
   */
  public static function isUserAllowedByKey($apiKey, $userId) {
    // Fetch allowed user for given api-key
    $users = self::fromApiKey($apiKey);

    // Delegate to actual implementation
    return self::isUserAllowed($users, $userId);
  }
  public static function isUserAllowedById($apiId, $userId) {
    // Fetch allowed user for given api-key
    $users = self::fromApiId($apiId);

    // Delegate to actual implementation
    return self::isUserAllowed($users, $userId);
  }


  /**
   * Function: isUserAllowed($users, $userId)
   *  Utility-Function that actually implements the checks used by
   *  RESTuser::isUserAllowedByKey() and RESTuser::isUserAllowedById()
   *
   * @See RESTuser::isUserAllowedByKey() and RESTuser::isUserAllowedById()
   * for more information.
   */
  protected static function isUserAllowed($users, $userId) {
    // No users for a given api-key means no restriction active
    if (count($users) == 0)
      return true;

    // Otherwise...
    else
      // ... user needs to have a table-entry
      foreach($users as $user)
        if ($user->getKey('user_id') == $userId)
          return true;

    // Not returned by now means, user restriction is active, but given user did no match any allowed id
    return false;
  }
}
