<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
// Requires: $app to be \RESTController\RESTController::getInstance()
use \RESTController\libs\RESTAuthFactory as AuthFactory;
use \RESTController\libs as Libs;


// Put implementation into own URI-Group
$app->group('/v1/challenge', function () use ($app) {
  /**
   * Route: POST /v1/challenge/client
   *  This route is used to authenticate a client using a shared secret
   *  (the refresh-token) without actually revealing the secret itself,
   *  but using challenge-response authentication:
   *
   * Authentification-Flow:
   *  Client sends a unique challenge value cc to the Server
   *  Server generates unique challenge value sc
   *  Server computes sr = hash(sc + cc + secret)
   *  Server sends sr and sc to the Client
   *  Client calculates the expected value of sr and ensures the Server responded correctly
   *  Client computes cr = hash(cc + sc + secret)
   *  Client sends cr
   *  Server calculates the expected value of cr and ensures the Client responded correctly
   *
   * This part manages the initial client_challenge.
   *
   * @See docs/api.pdf
   */
  $app->post('/client', AuthFactory::checkAccess(AuthFactory::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $auth         = new Util();
    $accessToken  = $auth->getAccessToken();

    // Fetch client-challenge or client-response
    $request    = $app->request;
    $cc         = $request->params('client_challenge');

    // Answer client-challenge?
    if ($cc) {
      // Verify input
      if (strlen($cc) != Challenge::challengeSize)
        $app->halt(422, sprintf('Client-Challenge does not have correct size. (Was: %d / Should: %d)', strlen($cc), Challenge::challengeSize));

      // Answer client-challenge
      $app->success(Challenge::answerClientChallange($accessToken, $cc));
    }
    else
      $app->halt(401, 'No Client-Challenge was given.');
  });


  /**
   * Route: GET\POST /v1/challenge/server
   *  This route is used to authenticate a client using a shared secret
   *  (the refresh-token) without actually revealing the secret itself,
   *  but using challenge-response authentication:
   *
   * Authentification-Flow:
   *  Client sends a unique challenge value cc to the Server
   *  Server generates unique challenge value sc
   *  Server computes sr = hash(sc + cc + secret)
   *  Server sends sr and sc to the Client
   *  Client calculates the expected value of sr and ensures the Server responded correctly
   *  Client computes cr = hash(cc + sc + secret)
   *  Client sends cr
   *  Server calculates the expected value of cr and ensures the Client responded correctly
   *
   * This part manages the server_challenge after the initial client_challenge.
   *
   * @See docs/api.pdf
   */
  $app->get('/server', AuthFactory::checkAccess(AuthFactory::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $auth         = new Util();
    $accessToken  = $auth->getAccessToken();

    // Fetch client-challenge or client-response
    $request    = $app->request;
    $cr         = $request->params('client_response');

    // Verify client-response
    if ($cr) {
     // Compare client-repsonse with expected response
     if (Challenge::checkClientResponse($accessToken, $cr))
       $app->success(array(
         'short_token' => Challenge::updateAccessToken($accessToken)->getTokenString()
       ));
     else
       $app->halt(401, 'Server-Challenge was not answered correctly.');
    }
    else
      $app->halt(401, 'No Client-Response was given.');
 });


// End of '/v1/challenge/' URI-Group
});

/*

  */
