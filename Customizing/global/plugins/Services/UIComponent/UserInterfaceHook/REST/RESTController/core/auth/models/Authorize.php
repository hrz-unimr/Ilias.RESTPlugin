<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


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
  const MSG_RESPONSE_TYPE = 'Unknown response_type ({{response_type}}) needs to be \'code\' for Authorization-Code grant or \'token\' for Implicit grant.';
  const ID_RESPONSE_TYPE  = 'RESTController\\core\\auth\\Authorize::ID_RESPONSE_TYPE';


  /**
   * Input-Function: FetchGetRouteParameters($app)
   *  Fetch all parameters that are required for [GET] on /v1/oauth2/authorize endpoints.
   *
   * Parameters:
   *  $request <RESTRequest> - Managed RESTRequest object
   *
   * Return:
   *  <Array[String]> - See below for details...
   */
  public static function FetchGetRouteParameters($request) {
    // Fetch parameters
    return array(
      'api_key'        => $request->params('api_key',       null, true),
      'api_secret'     => $request->params('api_secret'),
      'response_type'  => $request->params('response_type', null, true),
      'redirect_uri'   => $request->params('redirect_uri'),
      'scope'          => $request->params('scope'),
      'state'          => $request->params('state')
    );
  }


  /**
   * Input-Function: FetchPostRouteParameters($app)
   *  Fetch all parameters that are required for [POST] on /v1/oauth2/authorize endpoints.
   *
   * Parameters:
   *  $request <RESTRequest> - Managed RESTRequest object
   *
   * Return:
   *  <Array[String]> - See below for details...
   */
  public static function FetchPostRouteParameters($request) {
    // Also fetch get-parameters
    $data = self::FetchGetRouteParameters($request);

    // Fetch parameters
    // Note: ILIAS client can't be changed after initialization, so it needs to be fixed to the current one!
    //       CLIENT_ID can only be controlled via GET (or COOKIE)...
    return array_merge(array(
      'username'        => $request->params('username'),
      'password'        => $request->params('password'),
      'client_id'       => CLIENT_ID,
      'answer'          => $request->params('answer')
    ), $data);
  }


  /**
   * Function: FetchRedirectUri($client, $redirectUri)
   *
   *
   * Parameters:
   *  $client <RESTclient> -
   *  $redirectUri <String> -
   *
   * Return:
   *  <String> -
   */
  public static function FetchRedirectUri($client, $redirectUri) {
    // Fetch redirect_uri from client db-entry if non was given
    if (!isset($redirectUri)) {
      // Fetch redirect_uri from client db-entry
      $redirectUri = $client->getKey('redirect_uri');

      // If no redirect_uri was given and non is attached to the client, exit!
      if (!isset($redirectUri))
        throw new Exceptions\InvalidRequest(
          Libs\RESTRequest::MSG_MISSING,
          Libs\RESTRequest::ID_MISSING,
          array(
            'key' => 'redirect_uri'
          )
        );
    }

    // Fetch (updated) redirect_uri
    return $redirectUri;
  }


  /**
   * Function: CheckResponseType($client, $type)
   *
   *
   * Parameters:
   *  $client <RESTclient> -
   *  $type <String> -
   */
  public static function CheckResponseType($client, $type) {
    // Check if it is a valid response_type
    if (!in_array($type, array('code', 'token')))
      throw new Exceptions\ResponseType(
        self::MSG_RESPONSE_TYPE,
        self::ID_RESPONSE_TYPE,
        array(
          'response_type' => $param['response_type']
        )
      );

    // Check if response_type is enabled for this client (Autorization-Code)
    if ($type == 'code' && $client->getKey('grant_authorization_code') != true)
      throw new Exception\Denied(
        Common::MSG_AUTHORIZATION_CODE_DISABLED,
        Common::ID_AUTHORIZATION_CODE_DISABLED
      );

    // Check if response_type is enabled for this client (Implicit)
    if ($type == 'token' && $client->getKey('grant_implicit') != true)
      throw new Exception\Denied(
        Common::MSG_IMPLICIT_DISABLED,
        Common::ID_IMPLICIT_DISABLED
      );
  }


  /**
   * Function: FlowGetAuthorize($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state)
   *
   *
   * Parameters:
   *  $responseType <String> -
   *  $apiKey <String> -
   *  $apiSecret <String> -
   *  $apiCert - <Array[Mixed]> -
   *  $redirectUri <String> -
   *  $scope <String> -
   *  $state <String> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowGetAuthorize($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state) {
    // Check if client with api-key exists (throws on problem)
    $client = Common::CheckApiKey($apiKey);

    // Check response-type is valid and enabled for this client (throws on problem)
    self::CheckResponseType($client, $responseType);

    // Check client fullfills ip-restriction (throws on problem)
    Common::CheckIP($client, Common::FetchUserAgentIP());

    // Client client is authorized if enabled (throws on problem)
    Common::CheckClientCredentials($client, $apiSecret, $apiCert, $redirectUri);

    // Update redirectUri using stored client information (throws on problem)
    $redirectUri = self::FetchRedirectUri($client, $redirectUri);

    // Build data array that can be using by the template
    return array(
      'response_type'   => $responseType,
      'redirect_uri'    => $redirectUri,
      'api_key'         => $apiKey,
      'scope'           => $scope,
      'state'           => $state,
      'consent_message' => $client->getKey('consent_message')
    );
  }


  /**
   * Function: FlowGetAuthorize($responseType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $answer)
   *
   *
   * Parameters:
   *  $responseType <String> -
   *  $apiKey <String> -
   *  $apiSecret <String> -
   *  $apiCert - <Array[Mixed]> -
   *  $redirectUri <String> -
   *  $scope <String> -
   *  $state <String> -
   *  $userName <String> -
   *  $passWord <String> -
   *  $answer <String> -
   *
   * Return:
   *  <Array[Mixed]> -
   */
  public static function FlowPostAuthorize($responseType, $userName, $passWord, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state, $answer) {
    // Fetch same template data as fro get requests (throws on problem)
    $data = self::FlowGetAuthorize($responseType, $apiKey, $apiSecret, $apiCert, $redirectUri, $scope, $state);

    // Check username is correct (case-sensitive) (throws on problem)
    $userId = CheckUsername($userName);

    // Check that resource-owner is allowed to use this client (throws on problem)
    CheckUserRestriction($apiKey, $userId);

    // Check username and password match an ILIAS account (throws on problem)
    CheckResourceOwner($userName, $passWord);

    // Add additional fields to template data
    return array_merge(array(
      'username'  => $userName,
      'password'  => $passWord,
      'user_id'   => $userId,
      'answer'    => $answer
    ), $data);
  }



  /**
   * Function: GetRedirectURI($response_type, $answer, $redirect_uri, $state, $user_id, $ilias_client, $api_key, $scope)
   *  Generate final redirection URI (Step (C)) for implicit and authorization-Code grant.
   *
   * Parameters:
   *  $response_type <String> -
   *  $answer <String> -
   *  $redirect_uri <String> -
   *  $state <String> -
   *  $user_id <Integer> -
   *  $ilias_client <String> -
   *  $api_key <String> -
   *  $scope <String> -
   *
   * Return:
   *  <String> - Generated redirection-url
   */
  public static function GetRedirectURI($response_type, $answer, $redirect_uri, $state, $user_id, $ilias_client, $api_key, $scope) {
    // Extract required parameters
    $misc         = $redirect_uri;

    // Authorization-Code Grant
    if ($response_type == 'code') {
      // Access granted?
      if (strtolower($answer) == 'allow') {
        // Generate Authorization-Code
        $settings       = Tokens\Settings::load('authorization');
        $authorization  = Tokens\Authorization::fromFields($settings, $user_id, $ilias_client, $api_key, $scope, $misc);
        $authCode       = $authorization->getTokenString();

        // Store authorization-code token (rfx demands it only be used ONCE)
        $authDB         = Database\RESTauthorization::fromRow(array(
          'token'       => $authCode
        ));
        $authDB->insert();

        // Return redirection-url with data (Authorization-Code)
        return sprintf(
          '%s?code=%s&state=%s',
          $redirect_uri,
          $authCode,
          $state
        );
      }

      // Access-denied
      else return sprintf(
        '%s?error=access_denied&state=%s',
        $redirect_uri,
        $state
      );
    }

    // Implicit Grant
    elseif ($response_type == 'token') {
      // Access granted?
      if (strtolower($answer) == 'allow') {
        // Generate Access-Token
        $settings = Tokens\Settings::load('access');
        $access   = Tokens\Access::fromFields($settings, $user_id, $ilias_client, $api_key, $scope);

        // Return redirection-url with data (Access-Token)
        return sprintf(
          '%s?access_token=%s&token_type=%s&expires_in=%s&scope=%s&state=%s',
          $redirect_uri,
          $access->getTokenString(),
          'Bearer',
          $access->getRemainingTime(),
          $scope,
          $state
        );
      }

      // Access-denied
      else return sprintf(
        '%s#error=access_denied&state=%s',
        $redirect_uri,
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
    // Content and further logic is managed by the template
    $app->response()->setFormat('HTML');
    $app->render(
      'core/auth/views/authorization.php',
      array(
        'baseURL'     => ILIAS_HTTP_PATH,
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
    $response_type  = $param['response_type'];
    $answer         = $param['answer'];
    $redirect_uri   = $param['redirect_uri'];
    $state          = $param['state'];
    $user_id        = $param['user_id'];
    $ilias_client   = $param['ilias_client'];
    $api_key        = $param['api_key'];
    $scope          = $param['scope'];

    // Generate redirection url and redirect
    $url = self::GetRedirectURI($response_type, $answer, $redirect_uri, $state, $user_id, $ilias_client, $api_key, $scope);
    $app->redirect($url);
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
