<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;


// This allows us to use shortcuts instead of full quantifier
// Requires: $app to be \RESTController\RESTController::getInstance()
use \RESTController\core\auth as Auth;
use \RESTController\libs as Libs;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/goto/:type/:refId
   *  Generates a redirect to a permanent-link for
   *  the object given by the Reference-Id (and
   *  for the given object-type).
   *  This route also generates an ILIAS-Session
   *  on the server and transmits the corresponding
   *  cookies for use by the client. (eg. browser)
   *
   * @See docs/api.pdf
   */
  $app->get('/gotolink/:type/:refId', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($type, $refId) use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();
    $userName     = $accessToken->getUserName();

    // Login user (since token is valid, should not fail)
    GotoLink::createSession($userName);

    // Fetch session cookies, such that slim can use them
    $cookies = GotoLink::getSessionCookies();
    foreach ($cookies as $cookie)
      $app->setCookie($cookie['key'], $cookie['value'], $cookie['expires'], $cookie['path']);

    // Fetch permanent-link
    $link = GotoLink::getLink(intval($refId), $type);

    // Output result
    $app->response->redirect($link, 303);
  });


  /**
   * Route: GET /v1/umr/goto/:type/:refId
   *  Generates a redirect to a permanent-link for
   *  the object given by the Reference-Id (and
   *  for the given object-type).
   *  This route does not generate any ILIAS-Session
   *
   * @See docs/api.pdf
   */
  $app->get('/gotolink/:type/:refId', function ($type, $refId) use ($app) {
    // Fetch permanent-link
    $link = GotoLink::getLink(intval($refId), $type);

    // Output result
    $app->response->redirect($link, 303);
  });

// End of '/v1/umr/' URI-Group
});

/*

  */
