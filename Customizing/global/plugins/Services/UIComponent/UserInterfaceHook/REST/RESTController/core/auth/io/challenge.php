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
use \RESTController\core\auth as Models;
use \RESTController\core\auth\tokens as Tokens;
use \RESTController\core\auth\Exceptions as Exceptions;


/**
 * Class: Challenge (I/O)
 *  Handles I/O logic of all Challenge routes and delegates
 *  program-logic to model-classes.
 */
class Challenge extends Libs\RESTio {
  /**
   * Function: ClientChallenge($app)
   *  @See [POST] /v1/challenge/client
   */
  public static function ClientChallenge($app) {
    // Fetch userId & userName
    $accessToken  = Models\Util::getAccessToken();

    // Fetch client-challenge or client-response
    $request    = $app->request;
    $cc         = $request->params('client_challenge');

    // Answer client-challenge?
    if ($cc) {
      // Verify input
      if (strlen($cc) != Models\Challenge::challengeSize)
        $app->halt(422, sprintf('Client-Challenge does not have correct size. (Was: %d / Should: %d)', strlen($cc), Models\Challenge::challengeSize));

      // Answer client-challenge
      $app->success(Models\Challenge::answerClientChallange($accessToken, $cc));
    }
    else
      $app->halt(401, 'No Client-Challenge was given.');
  }


  /**
   * Function: ServerChallenge($app)
   *  @See [POST] /v1/challenge/server
   */
  public static function ServerChallenge($app) {
    // Fetch userId & userName
    $accessToken  = Models\Util::getAccessToken();

    // Fetch client-challenge or client-response
    $request    = $app->request;
    $cr         = $request->params('client_response');

    // Verify client-response
    if ($cr) {
     // Compare client-repsonse with expected response
     if (Models\Challenge::checkClientResponse($accessToken, $cr))
       $app->success(array(
         'short_token' => Models\Challenge::updateAccessToken($accessToken)->getTokenString()
       ));
     else
       $app->halt(401, 'Server-Challenge was not answered correctly.');
    }
    else
      $app->halt(401, 'No Client-Response was given.');
  }
}
