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
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/events
   *  [Without HTTP-GET Parameters] Gets all events (appointments) of the user given by the access-token.
   *  [With HTTP-GET Parameters] Get the events with given eventIds for the user given by the access-token.
   *  [This endpoint CAN parse HTTP-GET parameters, eg. ...?eventids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/events', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    try {
      $request        = $app->request;
      $eventIdString  = $request->params('eventids', null);

      // With HTTP-GET Parameter (fetch by contactIds)
      if ($eventIdString) {
        $eventIds   = Libs\RESTLib::parseIdsFromString($eventIdString, true);
        $events     = Events::getEvents($accessToken, $eventIds);
      }
      // Fetch all events
      else
        $events       = Events::getAllEvents($accessToken);

      // Output result
      $app->success($events);
    }
    catch (Libs\Exceptions\IdParseProblem $e) {
      $app->halt(422, $e->getMessage(), $e->getRESTCode());
    }
  });


  /**
   * Route: GET /v1/umr/events/:eventId
   *  Get the events with given eventIds for the user given by the access-token.
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/events/:eventId', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($eventId) use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    // Fetch user-information
    $events       = Events::getEvents($accessToken, $eventId);

    // Output result
    $app->success($events);
  });


  /**
   * Route: POST /v1/umr/events
   *  Adds an event (appointments) to a calendar of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->post('/events', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: PUT /v1/umr/events
   *  Updates an event (appointments) of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->put('/events', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: DELETE /v1/umr/events
   *  Deletes an event (appointments) of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->delete('/events', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
