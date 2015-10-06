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
   * Route: GET /v1/umr/calendars
   *  [Without HTTP-GET Parameters] Fetches all calendars of the user given by the access-token.
   *  [With HTTP-GET Parameters] Get the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint CAN parse HTTP-GET parameters, eg. ...?eventids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendars', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    try {
      $request          = $app->request;
      $calendarIdString = $request->params('calendarids', null);

      // With HTTP-GET Parameter (fetch by contactIds)
      if ($calendarIdString) {
        $calendarIds   = Libs\RESTLib::parseIdsFromString($calendarIdString, true);
        $calendars     = Calendars::getCalendars($accessToken, $calendarIds);
      }
      // Fetch all events
      else
        $calendars     = Calendars::getAllCalendars($accessToken);

      // Output result
      $app->success($calendars);
    }
    catch (Libs\Exceptions\IdParseProblem $e) {
      $app->halt(422, $e->getMessage(), $e->getRESTCode());
    }
  });


  /**
   * Route: GET /v1/umr/calendars/:calendarIds
   *  Get the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendars/:calendarIds', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($calendarIds) use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    // Fetch user-information
    $calendars    = Calendars::getCalendars($accessToken, $calendarIds);

    // Output result
    $app->success($calendars);
  });


  /**
   * Route: GET /v1/umr/calendars/events
   *  Get all events of the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint CAN parse HTTP-GET parameters, eg. ...?eventids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendars/events', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
    // TODO: Implement for mobile stuff
  });


  /**
   * Route: GET /v1/umr/calendars/:calendarIds/events
   *  Get all events of the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendars/:calendarIds/events/', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($calendarIds) use ($app) {
    // TODO: Implement for mobile stuff
  });


  /**
   * Route: POST /v1/umr/calendars
   *  Adds a calendar to the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->post('/calendars', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: PUT /v1/umr/calendars
   *  Updates a calendar of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->put('/calendars', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: DELETE /v1/umr/calendars
   *  Deletes a calendar of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->delete('/calendars', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
