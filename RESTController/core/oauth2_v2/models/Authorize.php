<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


/**
 * Class: Authorize
 *  This class handles input, buisness-logic and request-parsing
 *  for the Autorization-Code Grant und Implicit Grant
 *  during steps (A), (B) and (C).
 *
 *  See https://tools.ietf.org/html/rfc6749#section-4 for more information.
 */
class Authorize extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_RESPONSE_TYPE     = 'Unknown response_type ({{response_type}}) needs to be \'code\' for Authorization-Code grant or \'token\' for Implicit grant.';
  const ID_RESPONSE_TYPE      = 'RESTController\\core\\auth\\Authorize::ID_RESPONSE_TYPE';
  const MSG_IMPLICIT_DISABLED = 'Implicit grant is disabled for this client (api-key).';
  const ID_IMPLICIT_DISABLED  = 'RESTController\\core\\auth\\Authorize::ID_IMPLICIT_DISABLED';


  /**
   * Function: CheckResponseType($client, $type)
   *  Check wether the given request response_type is supported and enabled for the given client
   *  throws an exception if at least one of the above is false.
   *
   * Parameters:
   *  $client <RESTclient> - Client object used to check wether given response_type is enabled
   *  $type <String> - The response_type that was given as request parameter
   */
  public static function CheckResponseType($client = null, $type) {
    // Check if it is a valid response_type
    if (!in_array($type, array('code', 'token')))
      throw new Exceptions\ResponseType(
        self::MSG_RESPONSE_TYPE,
        self::ID_RESPONSE_TYPE,
        array(
          'response_type' => $type
        )
      );

    // Without a given client, only check grant_type is supported
    if (isset($client)) {
      // Check if response_type is enabled for this client (Autorization-Code)
      if ($type == 'code' && $client->getKey('grant_authorization_code') != true)
        throw new Exceptions\Denied(
          Common::MSG_AUTHORIZATION_CODE_DISABLED,
          Common::ID_AUTHORIZATION_CODE_DISABLED
        );

      // Check if response_type is enabled for this client (Implicit)
      if ($type == 'token' && $client->getKey('grant_implicit') != true)
        throw new Exceptions\Denied(
          self::MSG_IMPLICIT_DISABLED,
          self::ID_IMPLICIT_DISABLED
        );
    }
  }


  /**
   * Function: FlowAll()
   *  Utility function used by both authorization flows (GET and POST)
   *
   * Parameters:
   *  $responseType <String> - Given response_type request parameter
   *  $apiKey <String> - Given api-key request parameter representing a client
   *  $apiSecret <String> - Given api-secret used to authorize the client
   *  $apiCert - <Array[Mixed]> - Given client-certificate values to authorize the client
   *  $redirectUri <String> - Given redirect_uri used for redirection after termination of grant flow
   *  $scope <String> - Requested scope
   *  $state <String> - Additional state-information
   *
   * Return:
   *  <Array[Mixed]> - Unpack using list($client, $redirectUri) = self::FlowAll(...)
   */
  protected static function FlowAll($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP) {
    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check response-type is valid and enabled for this client (throws on problem)
    self::CheckResponseType($client, $responseType);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, $remoteIP);

    // Check requested scope...
    Common::CheckScope($client, $scope);

    // Update redirectUri using stored client information (throws on problem)
    $redirectUri = Common::FetchRedirectUri($client, $redirectUri);

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Return client and updated redirect-uri
    return array($client, $redirectUri);
  }


  /**
   * Function: FlowGetAuthorize($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state)
   *  Handles the overall grant flow for the initial part (GET on /authorize) for the Authorization-Code and
   *  Implicit grant.
   *
   * Parameters:
   *  $responseType <String> - Given response_type request parameter
   *  $apiKey <String> - Given api-key request parameter representing a client
   *  $apiSecret <String> - Given api-secret used to authorize the client
   *  $apiCert - <Array[Mixed]> - Given client-certificate values to authorize the client
   *  $redirectUri <String> - Given redirect_uri used for redirection after termination of grant flow
   *  $scope <String> - Requested scope
   *  $state <String> - Additional state-information
   *
   * Return:
   *  <Array[Mixed]> - List of parameters that will get passed to the template engine, see actual return-value for details
   */
  public static function FlowGetAuthorize($responseType, $iliasClient, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP) {
    // Incoke common flow code
    list($client, $redirectUri) = self::FlowAll($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP);

    // Build data array that can be using by the template
    return array(
      'response_type'   => $responseType,
      'redirect_uri'    => $redirectUri,
      'api_key'         => $apiKey,
      'api_id'          => $client->getKey('id'),
      'scope'           => $scope,
      'state'           => $state,
      'consent_message' => $client->getKey('consent_message'),
      'ilias_client'    => $iliasClient
    );
  }


  /**
   * Function: FlowGetAuthorize($responseType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $answer)
   *  Handles the overall grant flow after the initial GET part (POST on /authorize) for the Authorization-Code and
   *  Implicit grant.
   *
   * Parameters:
   *  $responseType <String> - Given response_type request parameter
   *  $apiKey <String> - Given api-key request parameter representing a client
   *  $apiSecret <String> - Given api-secret used to authorize the client
   *  $apiCert - <Array[Mixed]> - Given client-certificate values to authorize the client
   *  $redirectUri <String> - Given redirect_uri used for redirection after termination of grant flow
   *  $scope <String> - Requested scope
   *  $state <String> - Additional state-information
   *  $userName <String> - Resource-Owner username used to grant permission
   *  $passWord <String> - Resource-Owner password matching given username used to grant permission
   *  $answer <String> -  Answer for the grant-permission request given by the user (null, 'allow', 'deny')
   *                      (NULL means no answer was given yet, any other value other then 'allow' will be treated as 'deny')
   *
   * Return:
   *  <Array[Mixed]> - List of parameters that will get passed to the template engine, see actual return-value for details
   */
  public static function FlowPostAuthorize($responseType, $iliasClient, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP, $answer) {
    // Incoke common flow code
    list($client, $redirectUri) = self::FlowAll($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $remoteIP);

    // Only continue with this path if username was given
    if (isset($userName)) {
      // Check username is correct (case-sensitive) (throws on problem)
      $userId = Common::CheckUsername($userName);

      // Check that resource-owner is allowed to use this client (throws on problem)
      Common::CheckUserRestriction($client, $userId);

      // Check username and password match an ILIAS account (throws on problem)
      if (isset($passWord))
        Common::CheckResourceOwner($userName, $passWord);
    }

    // Add additional fields to template data
    return array(
      // Same as GET
      'response_type'   => $responseType,
      'redirect_uri'    => $redirectUri,
      'api_key'         => $apiKey,
      'api_id'          => $client->getKey('id'),
      'scope'           => $scope,
      'state'           => $state,
      'consent_message' => $client->getKey('consent_message'),
      'ilias_client'    => $iliasClient,

      // Added by POST
      'username'      => $userName,
      'password'      => $passWord,
      'user_id'       => $userId,
      'answer'        => $answer
    );
  }


  /**
   * Function: GetAuthorizationCode($userId, $iliasClient, $apiKey, $scope, $redirectUri)
   *  Generates a new Authorization-Code with the given parameters and stores it inside the Database
   *  for later lookup/comparison.
   *
   * Parameters:
   *  $apiKey <String> - Given api-key request parameter representing a client
   *  $redirectUri <String> - Given redirect_uri used for redirection after termination of grant flow
   *  $scope <String> - Requested scope
   *  $iliasClient - Given ILIAS client-id
   *  $answer - [Optional] Answer given by the resource-owner in regards to denying/allowing client access to his resources
   *  $userId - ILIAS User-ID of the resource-owner
   *
   * Return:
   *  <AuthorizationToken> - The generated Authorization-Code token that is required by the /token endpoint
   */
  public static function GetAuthorizationCode($userId, $iliasClient, $apiKey, $scope, $redirectUri) {
    // Generate Authorization-Code
    $settings       = Tokens\Settings::load('authorization');
    $authorization  = Tokens\Authorization::fromFields($settings, $userId, $iliasClient, $apiKey, $scope, $redirectUri);

    // Store authorization-code token (rfx demands it only be used ONCE)
    $authDB         = Database\RESTauthorization::fromRow(array(
      'token'   => $authorization->getTokenString(),
      'hash'    => $authorization->getUniqueHash(),
      'expires' => date("Y-m-d H:i:s", time() + $authorization->getRemainingTime())
    ));
    $authDB->store();

    // Return authorization-code token
    return $authorization;
  }


  /**
   * Function: GetRedirectURI($responseType, $answer, $redirectUri, $state, $userId, $iliasClient, $apiKey, $scope)
   *  Generate final redirection URI (Step (C)) for implicit and authorization-Code grant.
   *
   * Parameters:
   *  $responseType <String> - Given response_type request parameter
   *  $apiKey <String> - Given api-key request parameter representing a client
   *  $apiSecret <String> - Given api-secret used to authorize the client
   *  $apiCert - <Array[Mixed]> - Given client-certificate values to authorize the client
   *  $redirectUri <String> - Given redirect_uri used for redirection after termination of grant flow
   *  $scope <String> - Requested scope
   *  $state <String> - Additional state-information
   *  $iliasClient - Given ILIAS client-id
   *  $answer - [Optional] Answer given by the resource-owner in regards to denying/allowing client access to his resources
   *  $userId - ILIAS User-ID of the resource-owner
   *
   * Return:
   *  <String> - Generated redirection-url
   */
  public static function GetRedirectURI($responseType, $answer, $redirectUri, $state, $userId, $iliasClient, $apiKey, $scope) {
    // Authorization-Code Grant
    if ($responseType == 'code') {
      // Access granted?
      if (strtolower($answer) == 'allow') {
        // Generate Authorization-Code and store in DB
        $authorization  = self::GetAuthorizationCode($userId, $iliasClient, $apiKey, $scope, $redirectUri);

        // Return redirection-url with data (Authorization-Code)
        return sprintf(
          '%s?code=%s&state=%s',
          $redirectUri,
          $authorization->getTokenString(),
          $state
        );
      }

      // Access-denied
      else return sprintf(
        '%s?error=access_denied&state=%s',
        $redirectUri,
        $state
      );
    }

    // Implicit Grant
    elseif ($responseType == 'token') {
      // Access granted?
      if (strtolower($answer) == 'allow') {
        // Generate Access-Token and store in DB
        $access = Common::GetAccessToken($apiKey, $userId, $iliasClient, $scope);

        // Return redirection-url with data (Access-Token)
        return sprintf(
          '%s?access_token=%s&token_type=%s&expires_in=%s&scope=%s&state=%s',
          $redirectUri,
          $access->getTokenString(),
          'bearer',
          $access->getRemainingTime(),
          $scope,
          $state
        );
      }

      // Access-denied
      else return sprintf(
        '%s#error=access_denied&state=%s',
        $redirectUri,
        $state
      );
    }
  }


  /**
   * Output-Function: ShowWebsite($app, $param)
   *  Display the authorization website where the user first needs to login and
   *  is then offered the possibility to allow or deny the client application
   *  access to his resources.
   *  The underlying template is responsible for using the given parameters correctly.
   *
   * Parameters:
   *  $app <RESTController> - The RESTController
   *  $param <Array[Mixed]> - List of parameters that will get passed to the template engine. Usefull keys will be:
   *                           <All keys from FlowGetAuthorize()>
   *                           <All keys from FlowPostAuthorize()>
   *                           exception - RESTException-Object that was thrown during authorization (eg. invalid login)
   *                           Note: Not all keys may be present at all times.
   */
  public static function ShowWebsite($app, $param) {
    // fetch full route-url
    $route      = $app->router()->getCurrentRoute();
    $routeURL   = $route->getPattern();

    // fetch absolute dirictory of view folder
    $plugin     = Libs\RESTilias::getPlugin();
    $pluginDir  = str_replace('./', '', $plugin->getDirectory());
    $pluginDir  = $pluginDir . '/RESTController/core/oauth2_v2/views/';



    // Content and further logic is managed by the template
    $app->response()->setFormat('HTML');
    $app->render(
      'core/oauth2_v2/views/index.php',
      array(
        'baseURL'     => ILIAS_HTTP_PATH,
        'viewURL'     => ILIAS_HTTP_PATH . '/' . $pluginDir,
        'endpoint'    => ILIAS_HTTP_PATH . '/restplugin.php' . $routeURL,
        'client'      => CLIENT_ID,
        'parameters'  => $param,
      )
    );
  }


  /**
   * Output-Function: LoginFailed($app, $param, $exception)
   *  Add the exception to the list of template parameters on render the website again.
   *  The underlying template is responsible for using the given parameters correctly.
   *
   * Parameters:
   *  $exception <RESTException> - An exception that should be added to the template parameters
   *                               Should contain information about login-failure.
   *  @See Authorize::ShowWebsite(...) for additonal parameter information
   */
  public static function LoginFailed($app, $param, $exception) {
    // Show login-page again (with added exception), content and further logic is managed by the template
    $param['exception'] = $exception;
    Authorize::ShowWebsite($app, $param);
  }


  /**
   * Output-Function: RedirectUserAgent($app, $param)
   *  Once the resource-owner was authenticated and has made a desicion (deny/allow)
   *  his user-agent will be redirected back to the client-application using the redirect_uri.
   *
   * Parameters:
   *  @See Authorize::ShowWebsite(...) for additonal parameter information
   */
  public static function RedirectUserAgent($app, $param) {
    // Extract parameters:
    $responseType   = $param['response_type'];
    $answer         = $param['answer'];
    $redirectUri    = $param['redirect_uri'];
    $state          = $param['state'];
    $userId         = $param['user_id'];
    $iliasClient    = $param['ilias_client'];
    $apiKey         = $param['api_key'];
    $scope          = $param['scope'];

    // Generate redirection url and redirect
    $url = self::GetRedirectURI($responseType, $answer, $redirectUri, $state, $userId, $iliasClient, $apiKey, $scope);
    $app->redirect($url, 303);
  }


  /**
   * Output-Function: AskPermission($app, $param)
   *  Displays a website where the resource-owner is able to either allow or deny the client application
   *  access to his resources.
   *  The underlying template is responsible for using the given parameters correctly.
   *
   * Parameters:
   *  @See Authorize::ShowWebsite(...) for additonal parameter information
   */
  public static function AskPermission($app, $param) {
    // Content and further logic is managed by the template
    Authorize::ShowWebsite($app, $param);
  }
}
