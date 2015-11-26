<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\auth\io as IO;


// Group as version-1 implementation
$app->group('/v1', function () use ($app) {
  // Group as oauth2 implementation
  $app->group('/oauth2', function () use ($app) {
    /**
     * Route: [POST] /v1/oauth2/auth
     *  (RCF6749) Authorization Endpoint, used by the following grant-types:
     *   - authorization code grant
     *   - implicit grant type flows
     *  See http://tools.ietf.org/html/rfc6749
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->post('/auth', function () use ($app) { IO\oAuth2::AuthPost($app); });


    /**
     * Route: [GET] /v1/oauth2/auth
     *  Authorization Endpoint, this part covers only the first section of the auth
     *  flow and is included here, s.t. clients can initiate the "authorization or
     *  implicit grant flow" with a GET request.
     *  The flow after calling "oauth2loginform" continues with the POST
     *  version of "oauth2/auth".
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->get('/auth', function () use ($app) { IO\oAuth2::AuthGet($app); });


    /**
     * Route: [POST] /v1/oauth2/token
     *  Token Endpoint, supported grant types:
     *   - Resource Owner (User),
     *   - Client Credentials and
     *   - Authorization Code Grant
     *  See http://tools.ietf.org/html/rfc6749
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->post('/token', function () use ($app) { IO\oAuth2::TokenPost($app); });


    /**
     * Route: /v1/oauth2/token
     *  This endpoint allows a user to invalidate his refresh-token.
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->delete('/token', function () use ($app) { IO\oAuth2::TokenDelete($app); });


    /**
     * Route: [GET] /v1/oauth2/tokeninfo
     *  Token-info route, Tokens obtained via the implicit code grant
     *  MUST by validated by the Javascript client to prevent the
     *  "confused deputy problem".
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->get('/info', function () use ($app) { IO\oAuth2::Info($app); });


    /**
     * Route: [POST] /v1/ilauth/ilias2bearer
     *  Allows for exchanging an ilias session with a bearer token.
     *  This is used for administration purposes.
     *
     * Parameters:
     *
     *
     * Response:
     *
     */
    $app->post('/ilias', function () use ($app) { IO\oAuth2::ILIAS($app); });
  // End-Of /oauth2-group
  });
// End-Of /v1-group
});
