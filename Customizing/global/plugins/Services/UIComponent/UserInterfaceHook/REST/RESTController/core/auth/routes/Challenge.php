<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\auth\io as IO;


// Put implementation into own URI-Group
$app->group('/v1/challenge', function () use ($app) {
  /**
   * Route: POST /v1/challenge/client
   *  This route is used to authenticate a client using a shared secret
   *  (the refresh-token) without actually revealing the secret itself,
   *  but using challenge-response authentication:
   *
   * Authentification-Flow: (Client-Challange)
   *  Client sends a unique challenge value cc to the Server
   *  Server generates unique challenge value sc
   *  Server computes sr = hash(sc + cc + secret)
   *  Server sends sr and sc to the Client
   *  Client calculates the expected value of sr and ensures the Server responded correctly
   *  Client computes cr = hash(cc + sc + secret)
   *  Client sends cr
   *  Server calculates the expected value of cr and ensures the Client responded correctly
   *
   * Parameters:
   *
   *
   * Response:
   *
   */
  $app->post('/client', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { IO\Challenge::ClientChallenge($app); });


  /**
   * Route: GET\POST /v1/challenge/server
   *  This route is used to authenticate a client using a shared secret
   *  (the refresh-token) without actually revealing the secret itself,
   *  but using challenge-response authentication:
   *
   * Authentification-Flow: (Server-Challenge)
   *  Client sends a unique challenge value cc to the Server
   *  Server generates unique challenge value sc
   *  Server computes sr = hash(sc + cc + secret)
   *  Server sends sr and sc to the Client
   *  Client calculates the expected value of sr and ensures the Server responded correctly
   *  Client computes cr = hash(cc + sc + secret)
   *  Client sends cr
   *  Server calculates the expected value of cr and ensures the Client responded correctly
   *
   * Parameters:
   *
   *
   * Response:
   *
   */
  $app->get('/server', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { IO\Challenge::ServerChallenge($app); });
// End of '/v1/challenge/' URI-Group
});
