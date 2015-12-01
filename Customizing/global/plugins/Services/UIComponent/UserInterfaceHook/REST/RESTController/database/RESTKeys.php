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
    'id'                              => 'integer',
    'api_key'                         => 'text',
    'api_secret'                      => 'text',
    'oauth2_redirection_uri'          => 'text',
    'oauth2_consent_message'          => 'text',
    'oauth2_gt_client_active'         => 'integer',
    'oauth2_gt_authcode_active'       => 'integer',
    'oauth2_gt_implicit_active'       => 'integer',
    'oauth2_gt_resourceowner_active'  => 'integer',
    'oauth2_user_restriction_active'  => 'integer',
    'oauth2_gt_client_user'           => 'integer',
    'oauth2_consent_message_active'   => 'integer',
    'oauth2_authcode_refresh_active'  => 'integer',
    'oauth2_resource_refresh_active'  => 'integer',
    'ip_restriction_active'           => 'integer',
    'description'                     => 'text'
  );


  /**
   * Function: setKey($key, $value, $write)
   *  @See RESTDatabase->setKey(...)
   */
  public function setKey($key, $value, $write = false) {
    // Parse input based on key
    switch ($key) {
      // Convert int values
      case 'oauth2_gt_client_user':
        $value = intval($value);
        break;

      // Convert (empty) string value
      case 'api_key':
      case 'api_secret':
      case 'oauth2_redirection_uri':
      case 'oauth2_consent_message':
      case 'description':
        $value = ($value == null) ? '' : $value;
        break;

      // Convert boolean values
      case 'oauth2_gt_client_active':
      case 'oauth2_gt_authcode_active':
      case 'oauth2_gt_implicit_active':
      case 'oauth2_gt_resourceowner_active':
      case 'oauth2_user_restriction_active':
      case 'oauth2_consent_message_active':
      case 'oauth2_authcode_refresh_active':
      case 'oauth2_resource_refresh_active':
      case 'ip_restriction_active':
        $value = ($value == '1');
        break;

      // No default behaviour
      default:
    }

    // Store key's value after convertion
    return parent::setKey($key, $value, $write);
  }
}
