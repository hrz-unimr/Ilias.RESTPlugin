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
  const MSG_RESTRICTED_IP               = 'This client (api-key) is not allowed to be used from {{ip}} IP-Address.';
  const ID_RESTRICTED_IP                = 'RESTController\\core\\auth\\Authorize::ID_RESTRICTED_IP';
  const MSG_RESTRICTED_USER             = 'Resource-Owner \'{{username}}\' is not allowed to use this client (api-key).';
  const ID_RESTRICTED_USER              = 'RESTController\\core\\auth\\Authorize::ID_RESTRICTED_USER';
  const MSG_RESPONSE_TYPE               = 'Unknown response_type ({{response_type}}) needs to be \'code\' for Authorization-Code grant or \'token\' for Implicit grant.';
  const ID_RESPONSE_TYPE                = 'RESTController\\core\\auth\\Authorize::ID_RESPONSE_TYPE';
  const MSG_WRONG_OWNER_CREDENTIALS     = 'Resource-Owner credentials (Username & Password) could not be authenticated.';
  const ID_WRONG_OWNER_CREDENTIALS      = 'RESTController\\core\\auth\\Authorize::ID_WRONG_OWNER_CREDENTIALS';
  const MSG_AUTHORIZATION_CODE_DISABLED = 'Authorization-Code grant is disabled for this client (api-key).';
  const ID_AUTHORIZATION_CODE_DISABLED  = 'RESTController\\core\\auth\\Authorize::ID_AUTHORIZATION_CODE_DISABLED';
  const MSG_IMPLICIT_DISABLED           = 'Implicit grant is disabled for this client (api-key).';
  const ID_IMPLICIT_DISABLED            = 'RESTController\\core\\auth\\Authorize::ID_IMPLICIT_DISABLED';


  /**
   * Input-Function: FetchAuthorizationParameters($app)
   *  Fetch all parameters that are required for both /v1/oauth2/authorize endpoints.
   *
   * Parameters:
   *  $request <RESTRequest> - Managed RESTRequest object
   *
   * Return:
   *  <Array[String]> - See below for details...
   */
  public static function FetchClientParameters($request) {
    // Fetch parameters
    return array(
      'api_key'        => $request->params('api_key',       null, true),
      'response_type'  => $request->params('response_type', null, true),
      'redirect_uri'   => $request->params('redirect_uri'),
      'scope'          => $request->params('scope'),
      'state'          => $request->params('state')
    );
  }


  /**
   * Input-Function: FetchResourceOwnerCredentials($app)
   *  Fetch parameters required to authorize a given resource owner
   *
   * Parameters:
   *  $request <RESTRequest> - Managed RESTRequest object
   *
   * Return:
   *  <Array[String]> - See below for details...
   */
  public static function FetchResourceOwnerCredentials($app) {
    // Fetch parameters
    // Note: ILIAS client can't be changed after initialization, so it needs to be fixed to the current one!
    //       CLIENT_ID can only be controlled via GET (or COOKIE)...
    return array(
      'username'  => $request->params('username'),
      'password'  => $request->params('password'),
      'client_id' => CLIENT_ID
    );
  }


  public static function FlowGetAuthorizationCode($apiKey, $responseType) {
    // Check if client with api-key exists
    $client = Common::CheckApiKey($apiKey);

    // Check response-type is valid and enabled for this client
    Common::CheckResponseType($client, $type);

    // Check client fullfills ip-restriction
    Common::CheckIP($client, Common::FetchUserAgentIP());
  }


  /**
   * Function: CheckClientRequest($param)
   *  Checks wether the given client-parameters 'make sense'.
   *  This mostly means enforcing a valid parameter or stored
   *  redirect_uri, matching ip-restriction, supported and
   *  enabled grant-type, etc.
   *
   * Parameters:
   *  $param <Array[String]> - List of parameters describing the client-request. Required keys are:
   *                           api_key - Client-id(entification)
   *                           redirect_uri - URL to which the user-agent should be redirect to after permission was granted or denied
   *                           response_type - Needs to be 'code' for Authorization-Code Grant or 'token' for Impplicit Grant
   * Return:
   *  <Array[String]> - A updated list of client-parameters (with consent_message and redirect_uri)
   */
  public static function CheckClientRequest($client, $param) {
    // Check if grant-type is enabled for this client
    if ($param['response_type'] == 'code' || $param['response_type'] == 'token') {
      // Authorization-Code Grant is not enabled
      if ($param['response_type'] == 'code' && $client->getKey('grant_authorization_code') != true)
        throw new Exceptions\Denied(
          self::MSG_AUTHORIZATION_CODE_DISABLED,
          self::ID_AUTHORIZATION_CODE_DISABLED,
          array(
            'response_type' => $param['response_type']
          )
        );

      // Implicit Grant is not enabled
      if ($param['response_type'] == 'token' && $client->getKey('grant_implicit') != true)
        throw new Exceptions\Denied(
          self::MSG_IMPLICIT_DISABLED,
          self::ID_IMPLICIT_DISABLED,
          array(
            'response_type' => $param['response_type']
          )
        );
    }

    // Unsupported grant-type
    else
      throw new Exceptions\ResponseType(
        self::MSG_RESPONSE_TYPE,
        self::ID_RESPONSE_TYPE,
        array(
          'response_type' => $param['response_type']
        )
      );

    // Check ip-restriction
    // Note: If a (reverse-) proxy server is used, all workers need to set REMOTE_ADDR
    //       for example an apache worker (behind an nginx loadbalancer) by using mod_rpaf.
    if (!Database\RESTclient::isIpAllowed($_SERVER['REMOTE_ADDR']))
      throw new Exceptions\Denied(
        self::MSG_RESTRICTED_IP,
        self::ID_RESTRICTED_IP,
        array(
          'ip' => $_SERVER['REMOTE_ADDR']
        )
      );

    // Fetch redirect_uri from client db-entry if non was given
    if (!isset($param['redirect_uri'])) {
      // Fetch redirect_uri from client db-entry
      $param['redirect_uri'] = $client->getKey('redirect_uri');

      // If no redirect_uri was given and non is attached to the client, exit!
      if (!isset($param['redirect_uri']))
        throw new Exceptions\InvalidRequest(
          Libs\RESTRequest::MSG_MISSING,
          Libs\RESTRequest::ID_MISSING,
          array(
            'key' => 'redirect_uri'
          )
        );
    }

    // Attach consent-message to parameters:
    $param['consent_message'] = $client->getKey('consent_message');

    // Return (possible updated) parameters
    return $param;
  }


  /**
   * Function: CheckResourceOwnerCredentials($param)
   *  Checks wether the resource-owner credentials are valid
   *  and is allowed to use this api-key.
   *
   * Parameters:
   *  $param <Array[String]> - List of parameters describing from the resource-owner. Required keys are:
   *                           username - Username of resource-owner
   *                           password - Password of resource-owner
   *
   * Return:
   *  <Array[Mixed]> - Input-parameters with addition 'user_id' key (ILIAS user-id for given username)
   */
  static public function CheckResourceOwnerCredentials($param) {
    // This throws for wrong username (case-sensitive!)
    $userId = Libs\RESTilias::getUserId($ownerCredentials['username']);

    // Check user restriction
    if (!Database\RESTuser::isUserAllowedByKey($param['api_key'], $userId))
      throw new Exceptions\Denied(
        self::MSG_RESTRICTED_USER,
        self::ID_RESTRICTED_USER,
        array(
          'userID'    => $userId,
          'username'  => $ownerCredentials['username'],
        )
      );

    // Check wether the resource owner credentials are valid
    if (!Libs\RESTilias::authenticate($ownerCredentials['username'], $ownerCredentials['password']))
      throw new Exception\Credentials(
        self::MSG_WRONG_OWNER_CREDENTIALS,
        self::ID_WRONG_OWNER_CREDENTIALS
      );

    // All went fine...
    $param['user_id'] = $param;
    return $param;
  }


  /**
   * Function: GetRedirectURI($param)
   *  Generate final redirection URI (Step (C)) for implicit and authorization-Code grant.
   *
   * Parameters:
   *  @See Authorize::ShowWebsite(...) for additonal parameter information
   *
   * Return:
   *  <String> - Generated redirection-url
   */
  public static function GetRedirectURI($param) {
    // Extract required parameters
    $redirect_uri = $param['redirect_uri'];
    $state        = $param['state'];
    $user_id      = $param['user_Id'];
    $ilias_client = $param['client_id'];
    $api_key      = $param['api_key'];
    $scope        = $param['scope'];
    $misc         = $redirect_uri;

    // Authorization-Code Grant
    if ($param['response_type'] == 'code') {
      // Access granted?
      if (strtolower($param['grant']) == 'allow') {
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
    elseif ($param['response_type'] == 'token') {
      // Access granted?
      if (strtolower($param['grant']) == 'allow') {
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
   *                           user_Id - ILIAS user-id of resource-owner
   *                           grant - Will either be null, 'allow' or 'deny' depending on wether the user allowed
   *                                   the client application access to its resources or not.
   *                           exception - RESTException-Object that was thrown during authorization (eg. invalid login)
   *                           consent_message - Custom-Message to display on permission-request website.
   *                           <All keys from FetchClientParameters()>
   *                           <All keys from FetchResourceOwnerCredentials()>
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
    // Generate redirection url and redirect
    $url = self::GetRedirectURI($param);
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
