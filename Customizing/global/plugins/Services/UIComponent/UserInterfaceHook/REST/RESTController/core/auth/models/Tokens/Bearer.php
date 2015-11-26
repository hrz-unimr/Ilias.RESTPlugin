<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Tokens;


/**
 * Class: Bearer (-Token)
 *  Contains both access- and refresh-tokens as type bearer
 *  with additional information about contained tokens.
 *  See: https://tools.ietf.org/html/rfc6750
 */
class Bearer extends Base {
  // List of fields (keys) for this kind of token
  protected static $fields = array(
    'access_token',
    'refresh_token',
    'scope'
  );


  /**
   * Static-Function: fromMixed($tokenSettings, $tokenArray)
   *  Generates a Bearer-Token from given input parameters.
   *  Expects settings-object and token-data as array.
   *
   * Parameters:
   *  $tokenSettings <Settings> - Internal settings of this token
   *  $tokenArray <Array[Mixed]> - Array of string (key & value) elements representing a valid token
   *
   * Return:
   *  <BearerToken> - Generated Bearer-Token
   */
  public static function fromMixed($tokenSettings, $tokenArray) {
    // Generate new token from token-data as array
    $bearerToken = new self($tokenSettings);
    $bearerToken->setToken($tokenArray);

    // Return new object
    return $bearerToken;
  }


  /**
   * Static-Function: fromMixed($tokenSettings, $user_id, $ilias_client, $api_key, $scope)
   *  Generates a Bearer-Token from given input parameters.
   *  Expects settings-object and token-data as additional parameters.
   *
   * Parameters:
   *  $tokenSettings <Settings> - Internal settings of this token
   *  $scope <String> - Allowed scope (only as representation for the user)
   *  @See Generic::fromFields(...) for parameter description
   *
   * Return:
   *  <BearerToken> - Generated Bearer-Token
   */
  public static function fromFields($tokenSettings, $user_id, $ilias_client, $api_key, $scope = null) {
    // Generate new token from token-data as parameters
    $bearerToken = new self($tokenSettings);
    $tokenArray  = $bearerToken->generateTokenArray($user_id, $ilias_client, $api_key, $scope);
    $bearerToken->setToken($tokenArray);

    // Return new object
    return $bearerToken;
  }


  /**
   * GetterFunctions:
   *  getAccessToken() - Returns stored AccessToken
   *  getRefreshToken() - Returns stored RefreshToken
   *  getType() - Returns Token-Type (Bearer)
   *  getScope() - Returns Scope/Permissions of this token
   */
  public function getAccessToken() {
    return $this->tokenArray['access_token'];
  }
  public function getRefreshToken() {
    return $this->tokenArray['refresh_token'];
  }
  public function getScope() {
    return $this->tokenArray['scope'];
  }


  /**
   * Function: getResponseObject()
   *  Generates an object that can be converted to JSON object and transmited.
   *
   * Return:
   *  Array<String> - Object containing all relevent information for transmission
   */
  public function getResponseObject() {
    // Generate transmitable object
    return array(
        'access_token'  => $this->getAccessToken()->getTokenString(),
        'refresh_token' => $this->getRefreshToken()->getTokenString(),
        'expires_in'    => $this->getAccessToken()->getRemainingTime(),
        'token_type'    => 'Bearer',
        'scope'         => $this->getScope()
    );
  }


  /**
   * Function: generateTokenArray($user_id, $ilias_client, $api_key, $scope)
   *  Generates and returns a token-data array generated from given parameters.
   *
   * Parameters:
   *  $scope <String> - Allowed scope (only as representation for the user)
   *  @See Generic::fromFields(...) for parameter description
   *
   * Return:
   *  <Array[Mixed]> - Generated array representing token-data given by parameters
   */
  protected function generateTokenArray($user_id, $ilias_client, $api_key, $scope = null) {
    // Generate generic token containing user and api-key
    $accessToken  = Access::fromFields( $this->tokenSettings, $user_id, $ilias_client, $api_key, 'Bearer');
    $refreshToken = Refresh::fromFields($this->tokenSettings, $user_id, $ilias_client, $api_key, 'Bearer');

    // Generate bearer-token containing the generic token and additional information
    return array(
        'access_token'  => $accessToken,
        'refresh_token' => $refreshToken,
        'scope'         => $scope
    );
  }
}
