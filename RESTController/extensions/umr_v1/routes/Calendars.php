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
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\oauth2_v2 as Auth;


// Put implementation into own URI-Group
$app->group('/v1/umr', function () use ($app) {
  /**
   * Route: GET /v1/umr/calendars
   *  [Without HTTP-GET Parameters] Fetches all calendars of the user given by the access-token.
   *  [With HTTP-GET Parameters] Get the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint CAN parse HTTP-GET parameters, eg. ...?calendarids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendars', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      $request          = $app->request;
      $calendarIdString = $request->getParameter('calendarids', null);

      // With HTTP-GET Parameter (fetch by contactIds)
      if ($calendarIdString) {
        $calendarIds   = Libs\RESTRequest::parseIDList($calendarIdString, true);
        $calendars     = Calendars::getCalendars($accessToken, $calendarIds);
      }
      // Fetch all events
      else
        $calendars     = Calendars::getAllCalendars($accessToken);

      // Output result
      $app->success($calendars);
    }
    catch (Libs\Exceptions\StringList $e) {
      $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
    }
    catch (Exceptions\Calendars $e) {
      $responseObject         = Libs\RESTResponse::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: GET /v1/umr/calendars/:calendarId
   *  Returns a calendar of the authenticated user specified by calendarId.
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendars/:calendarId', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($calendarId) use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch user-information
      $calendars    = Calendars::getCalendars($accessToken, $calendarId);

      // Output result
      $app->success($calendars);
    }
    catch (Exceptions\Calendars $e) {
      $responseObject         = Libs\RESTResponse::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: GET /v1/umr/calendars/events
   *  Get all events of the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint CAN parse HTTP-GET parameters, eg. ...?calendarids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendar/events', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {

    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      $request          = $app->request;
      $calendarIdString = $request->getParameter('calendarids', null, true);

      // With HTTP-GET Parameter (fetch by contactIds)
      $calendarIds   = Libs\RESTRequest::parseIDList($calendarIdString, true);
      $calendars     = Calendars::getAllEventsOfCalendars($accessToken, $calendarIds);

      // Output result
      $app->success($calendars);
    }
    catch (Libs\Exceptions\StringList $e) {
      $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
    }
    catch (Libs\Exceptions\MissingParameter $e) {
        $app->halt(400, $e->getFormatedMessage(), $e->getRESTCode());
    }
    catch (Exceptions\Calendars $e) {
      $responseObject         = Libs\RESTResponse::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: GET /v1/umr/calendars/:calendarIds/events
   *  Get all events of the calendars with given calendarIds for the user given by the access-token.
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/calendar/:calendarId/events', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($calendarId) use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch user-information
      $calendars    = Calendars::getAllEventsOfCalendars($accessToken, $calendarId);

      // Output result
      $app->success($calendars);
    }
    catch (Exceptions\Calendars $e) {
      $responseObject         = Libs\RESTResponse::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: POST /v1/umr/calendars
   *  Adds a calendar to the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->post('/calendars', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: PUT /v1/umr/calendars
   *  Updates a calendar of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->put('/calendars', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });


  /**
   * Route: DELETE /v1/umr/calendars
   *  Deletes a calendar of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->delete('/calendars', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { $app->halt(500, '<STUB - IMPLEMENT ME!>'); });

// End of '/v1/umr/' URI-Group
});
