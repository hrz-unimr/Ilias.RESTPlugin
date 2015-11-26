<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\io;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\auth as Models;
use \RESTController\core\auth\tokens as Tokens;
use \RESTController\core\auth\Exceptions as Exceptions;


/**
 * Class: oAuth2 (I/O)
 *  Handles I/O logic of all oAuth2 routes and delegates
 *  program-logic to model-classes.
 */
class oAuth2 extends Libs\RESTIO {
  /**
   * Function: AuthPost($app)
   *  @See [POST] /v1/oauth2/auth
   */
  public static function AuthPost($app) {
    try {
      // Fetch request data
      $request = $app->request();

      // Mandatory parameters
      $api_key = $request->params('api_key', null, true);
      $redirect_uri = $request->params('redirect_uri', null, true);
      $response_type = $request->params('response_type', null, true);

      // Optional parameters (will be checked by model)
      $username = $request->params('username');
      $password = $request->params('password');
      $authenticity_token = $request->params('authenticity_token');

      // Proccess with OAuth2-Model
      if ($authenticity_token)
        $authenticityToken = Tokens\Generic::fromMixed(AuthEndpoint::tokenSettings('access'), $authenticity_token);
      $result = Models\AuthEndpoint::allGrantTypes($api_key, $redirect_uri, $username, $password, $response_type, $authenticityToken);

      // Process results (send response)
      if ($result['status'] == 'showLogin')
        Models\Util::renderWebsite('REST OAuth - Login für Tokengenerierung', 'oauth2loginform.php', $result['data']);
      elseif ($result['status'] == 'showPermission')
        Models\Util::renderWebsite('REST OAuth - Client autorisieren', 'oauth2grantpermissionform.php', $result['data']);
      elseif ($result['status'] == 'redirect')
        $app->redirect($result['data']);
    }
    catch (Exceptions\ResponseType $e) {
      $app->halt(422, $e->getMessage(), $e->getRestCode());
    }
    catch (Exceptions\LoginFailed $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (Exceptions\TokenInvalid $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (LibExceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRestCode());
    }
  }


  /**
   * Function: AuthGet($app)
   *  @See [GET] /v1/oauth2/auth
   */
  public static function AuthGet($app) {
    try {
      // Fetch request data
      $request = $app->request();

      // Mandatory parameters
      $api_key = $request->params('client_id');
      if (is_null($api_key))
        $api_key = $request->params('api_key', null, true);
      $redirect_uri = $request->params('redirect_uri', null, true);
      $response_type = $request->params('response_type', null, true);

      // Render oauth2login.php for authorization code and implicit grant type flow
      if ($response_type != 'code' && $response_type != 'token')
        throw new Exceptions\ResponseType(Exceptions\ResponseType::MSG);

      // Display login form
      Models\Util::renderWebsite(
        'REST OAuth - Login für Tokengenerierung',
        'oauth2loginform.php',
        array(
          'api_key' => $api_key,
          'redirect_uri' => $redirect_uri,
          'response_type' => $response_type
        )
      );
    }
    catch (Exceptions\ResponseType $e) {
      $app->halt(422, $e->getMessage(), $e->getRestCode());
    }
    catch (LibExceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRestCode());
    }
  }


  /**
   * Function: TokenPost($app)
   *  @See [POST] /v1/oauth2/token
   */
  public static function TokenPost($app) {
    try {
      // Get Request & OAuth-Model objects
      $request =  $app->request();
      $grant_type = $request->params('grant_type', null, true);

      // Resource Owner (User) grant type
      if ($grant_type == 'password') {
        // Fetch request data
        $api_key = $request->params('api_key', null, true);
        $user = $request->params('username', null, true);
        $password = $request->params('password', null, true);
        $new_refresh = $request->params('new_refresh');
        $ilias_client = $request->params('ilias_client', CLIENT_ID);

        // Invoke OAuth2-Model with data
        $result = Models\TokenEndpoint::userCredentials($api_key, $user, $password, $new_refresh, $ilias_client);

        // Send result
        $app->response()->disableCache();
        $app->success($result);
      }
      // grant type
      elseif ($grant_type == 'client_credentials') {
        // Fetch request data
        $api_key = $request->params('api_key', null, true);
        $api_secret = $request->params('api_secret', null, true);
        $ilias_client = $request->params('ilias_client', CLIENT_ID);

        // Invoke OAuth2-Model with data
        $result = Models\TokenEndpoint::clientCredentials($api_key, $api_secret, $ilias_client);
        $app->response()->disableCache();
        $app->success($result);
      }
      // grant type
      elseif ($grant_type == 'authorization_code') {
        // Fetch request data (POST-Form instead of body)
        $api_key = $_POST['api_key'];

        // Fetch request data
        $code = $request->params('code', null, true);
        $api_secret = $request->params('api_secret', null, true);
        $redirect_uri = $request->params('redirect_uri');
        $new_refresh = $request->params('new_refresh');

        // Invoke OAuth2-Model with data
        $authCodeToken = Tokens\Generic::fromMixed(TokenEndpoint::tokenSettings('access'), $code);
        $result = Models\TokenEndpoint::authorizationCode($api_key, $api_secret, $authCodeToken, $redirect_uri, $new_refresh);

        // Send result
        $app->response()->disableCache();
        $app->success($result);
      }
      // grant type
      elseif ($grant_type == 'refresh_token') {
        // Fetch request data
        $refresh_token = $request->params('refresh_token', null, true);
        $new_refresh = $request->params('new_refresh');

        // Invoke OAuth2-Model with data
        $refreshToken = Tokens\Refresh::fromMixed(TokenEndpoint::tokenSettings('refresh'), $refresh_token);
        $result = Models\TokenEndpoint::refresh2Access($refreshToken, $new_refresh);

        // Send result to client
        if ($result) {
          $app->response()->disableCache();
          $app->success($result);
        }
        else
          $app->halt(422, Models\TokenEndpoint::MSG_NO_REFRESH_LEFT, Models\TokenEndpoint::ID_NO_REFRESH_LEFT);
      }
      // Wrong grant-type
      else
          throw new Exceptions\GrantType('Parameter "grant_type" needs to match "password", "client_credentials", "authorization_code" or "refresh_token".');
    }
    catch (Exceptions\GrantType $e) {
      $app->halt(422, $e->getMessage(), $e->getRestCode());
    }
    catch (Exceptions\LoginFailed $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (Exceptions\TokenInvalid $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (LibExceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRestCode());
    }
  }


  /**
   * Function: TokenDelete($app)
   *  @See [DELETE] /v1/oauth2/token
   */
  public static function TokenDelete($app) {
    try {
      // Get Request & OAuth-Model objects
      $request = $app->request();

      // Fetch request data
      $refresh_token = $request->params('refresh_token', null, true);

      // Extract data
      $refreshToken = Tokens\Refresh::fromMixed(RefreshEndpoint::tokenSettings('refresh'), $refresh_token);
      $user_id = $refreshToken->getUserId();
      $api_key = $refreshToken->getApiKey();

      // Invoke OAuth2-Model with data
      $result = Models\RefreshEndpoint::deleteToken($user_id, $api_key);

      // Send response
      if ($result > 0)
        $app->success('Refresh-Token deleted.');
      else
        $app->halt(500, 'Refresh-Token could not be deleted, try requesting a new one instead.');
    }
    catch (Exceptions\LoginFailed $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (Exceptions\TokenInvalid $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (LibExceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRestCode());
    }
  }


  /**
   * Function: Info($app)
   *  @See [GET] /v1/oauth2/info
   */
  public static function Info($app) {
    try {
      // Fetch token
      $accessToken = Models\Util::getAccessToken();

      // Generate token-information
      $result = Models\MiscEndpoint::tokenInfo($accessToken);

      // Return status-data
      $app->success($result);
    }
    catch (Exceptions\TokenInvalid $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
  }


  /**
   * Function: ILIAS($app)
   *  @See [GET] /v1/oauth2/ilias
   */
  public static function ILIAS($app) {
    try {
      // Fetch parameters
      $request = $app->request();
      $api_key = $request->params('api_key', null, true);
      $user_id = $request->params('user_id', null, true);
      $rtoken = $request->params('rtoken', null, true);
      $session_id = $request->params('session_id', null, true);
      $ilias_client = $request->params('ilias_client', CLIENT_ID);

      // Convert userId, rToken and sessionId to bearer-token (using api-key)
      $result = Models\MiscEndpoint::rToken2Bearer($api_key, $user_id, $rtoken, $session_id, $ilias_client);

      // Return status-data
      $app->response()->disableCache();
      $app->success($result);
    }
    catch (Exceptions\TokenInvalid $e) {
      $app->halt(401, $e->getMessage(), $e->getRestCode());
    }
    catch (LibExceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRestCode());
    }
  }
}
