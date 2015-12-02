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
 * Class: RESTKeys (Database-Table)
 *  Abstraction for 'ui_uihk_rest_keys' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTKeys extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_keys';
  protected static $tableKeys   = array(
    'id'                          => 'integer',
    'api_key'                     => 'text',
    'api_secret'                  => 'text',
    'redirect_uri'                => 'text',
    'consent_message'             => 'text',
    'client_credentials_userid'   => 'integer',
    'grant_client_credentials'    => 'integer',
    'grant_authorization_code'    => 'integer',
    'grant_implicit'              => 'integer',
    'grant_resource_owner'        => 'integer',
    'refresh_authorization_code'  => 'integer',
    'refresh_resource_owner'      => 'integer',
    'description'                 => 'text'
  );


  /**
   * Static-Function: fromApiKey($apiKey)
   *  Creates a new instance of RESTKeys representing the table-entry with given aki-key.
   *
   * Parameters:
   *  $apiKey <String> - Api-Key who's database entry should be returned
   *
   * Return:
   *  <RESTKeys> - A new instance of RESTKeys representing the table-entry with given aki-key
   */
  public static function fromApiKey($apiKey) {
    // Generate a (save) where clause for the api-key ($apiKey can be malformed!)
    $where  = sprintf('api_key = \'%s\'', addslashes($apiKey));

    // Fetch matching object
    return self::fromWhere($where);
  }


  /**
   * Function: getKey($key)
   *  @See RESTDatabase->getKey(...)
   */
  public function getKey($key) {
    // Fetch internal value from parent
    $value = parent::getKey($key);

    // Convert internal value when publshing
    // Note: Make sure to 'revert' those changes in setKey(...)!
    switch ($key) {
      // Convert string/boolean values
      case 'consent_message':
        return ($value == null) ? false : $value;

      default:
        return $value;
    }
  }


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // Parse input based on key
    switch ($key) {
      // Convert string/boolean values
      case 'consent_message':
        $value = ($value == FALSE) ? null : $value;
        break;

      // Convert int values
      case 'client_credentials_userid':
        $value = intval($value);
        break;

      // Convert (empty) string value
      case 'api_key':
      case 'api_secret':
      case 'description':
        $value = ($value == null) ? '' : $value;
        break;

      // Convert boolean values
      case 'grant_client_credentials':
      case 'grant_authorization_code':
      case 'grant_implicit':
      case 'grant_resource_owner':
      case 'refresh_authorization_code':
      case 'refresh_resource_owner':
        $value = ($value == '1');
        break;

      // No default behaviour
      default:
    }

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }
}
