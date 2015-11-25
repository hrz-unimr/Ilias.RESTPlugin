<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Token;


/**
 * Class: AccessToken
 *  Represents an actual Access-Token.
 *  Mainly a generic token with additional data in misc-field.
 */
class AccessToken extends Generic {
  /**
   * Function: fromFields($tokenSettings, $user_id, $ilias_client, $api_key, $type, $misc, $lifetime)
   *  Generates a Access-Token from given input parameters.
   *  Expects settings-object and token-data as additional parameters.
   *
   * Parameters:
   *  @See Generic::fromFields(...) for parameter description
   *
   * Return:
   *  <AccessToken> - Generated Access-Token
   */
  public static function fromFields($tokenSettings, $user_id, $ilias_client, $api_key, $type = null, $misc = null, $lifetime = null) {
    // Add custom-type info
    $misc = ($misc) ? $misc + '-access' : 'access';

    // Return generic token with some customized fieldsd
    return parent::fromFields($tokenSettings, $user_id, $ilias_client, $api_key, $type, $misc, $lifetime);
  }
}
