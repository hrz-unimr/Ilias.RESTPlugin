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
   * Route: GET /v1/umr/events
   *  [Without HTTP-GET Parameters] Gets all events (appointments) of the user given by the access-token.
   *  [With HTTP-GET Parameters] Get the events with given eventIds for the user given by the access-token.
   *  [This endpoint CAN parse HTTP-GET parameters, eg. ...?eventids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/events', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      $request        = $app->request;
      $eventIdString  = $request->getParameter('eventids', null);

      // With HTTP-GET Parameter (fetch by contactIds)
      if ($eventIdString) {
        $eventIds   = Libs\RESTRequest::parseIDList($eventIdString, true);
        $events     = Events::getEvents($accessToken, $eventIds);
      }
      // Fetch all events
      else
        $events       = Events::getAllEvents($accessToken);

      // Output result
      $app->success($events);
    }
    catch (Libs\Exceptions\StringList $e) {
      $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
    }
    catch (Exceptions\Events $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: GET /v1/umr/events/:eventId
   *  Get the events with given eventIds for the user given by the access-token.
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/events/:eventId', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($eventId) use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch user-information
      $events       = Events::getEvents($accessToken, $eventId);

      // Output result
      $app->success($events);
    }
    catch (Exceptions\Events $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: POST /v1/umr/events
   * Adds an event (appointment) to a calendar of the authenticated user.
   *
   * Example with IShell:
   * i.post('v1/umr/events',{'cal_id':'10','title':'test','description':'created with ishell','full_day':'0','start_hour':'10','start_minute':'0','start_month':'7','start_day':'1','start_year':'2016', 'end_hour':'11','end_minute':'30','end_month':'7','end_day':'1','end_year':'2016'})
   *
   * @See docs/api.pdf
   */
  $app->post('/events', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch refIds
      $request      = $app->request;
      $calId  = $request->getParameter('cal_id', null, true);
      $title = $request->getParameter('title', null, true);
      $description = $request->getParameter('description', "", false);

      $fullDayFlag =  $request->getParameter('full_day', false, false);
      $startTime = array();
      $startTime['hour'] =  $request->getParameter('start_hour', null, true);
      $startTime['minute']=  $request->getParameter('start_minute', null, true);
      $startTime['month'] =  $request->getParameter('start_month', null, true);
      $startTime['day'] =  $request->getParameter('start_day', null, true);
      $startTime['year'] =  $request->getParameter('start_year', null, true);

      $endTime = array();
      $endTime['hour'] =  $request->getParameter('end_hour', null, true);
      $endTime['minute']=  $request->getParameter('end_minute', null, true);
      $endTime['month'] =  $request->getParameter('end_month', null, true);
      $endTime['day'] =  $request->getParameter('end_day', null, true);
      $endTime['year'] =  $request->getParameter('end_year', null, true);


      $newEventId = Events::addEvent($accessToken, $calId, $title, $description, $fullDayFlag, $startTime, $endTime);

      // Output result
      if ($newEventId > -1) {
        $app->success(array("msg"=>"Created new event with eventId $newEventId in calendar $calId."));
      } else {
        $app->halt(403, array("msg"=>"Not allowed to create a new event in calendar $calId."));
      }

    }
    catch (Libs\Exceptions\StringList $e) {
      $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
    }
    catch (Libs\Exceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRESTCode());
    }
    catch (Exceptions\Events $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: PUT /v1/umr/events
   *  Updates an event (appointment) of the user given by the access-token.
   *
   * IShell examples:
   *  i.put('v1/umr/events/13',{'title':'Renamed title','description':'Test'})
   *  i.put('v1/umr/events/13',{'title':'Renamed title2','description':'Test2','full_day':'0','start_hour':'10','start_minute':'0','start_month':'7','start_day':'1','start_year':'2016', 'end_hour':'11','end_minute':'30','end_month':'7','end_day':'1','end_year':'2016'})
   *  i.put('v1/umr/events/13',{'title':'Another event','description':'','full_day':'1'})
   *
   * @See docs/api.pdf
   */
  $app->put('/events/:eventId', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($eventId) use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch refIds
      $request      = $app->request;
      $title = $request->getParameter('title', null, true);
      $description = $request->getParameter('description', "", false);

      $fullDayFlag =  $request->getParameter('full_day', null, false);
      $startTime = array();
      $startTime['hour'] =  $request->getParameter('start_hour', null, false);
      $startTime['minute']=  $request->getParameter('start_minute', null, false);
      $startTime['month'] =  $request->getParameter('start_month', null, false);
      $startTime['day'] =  $request->getParameter('start_day', null, false);
      $startTime['year'] =  $request->getParameter('start_year', null, false);

      $endTime = array();
      $endTime['hour'] =  $request->getParameter('end_hour', null, false);
      $endTime['minute']=  $request->getParameter('end_minute', null, false);
      $endTime['month'] =  $request->getParameter('end_month', null, false);
      $endTime['day'] =  $request->getParameter('end_day', null, false);
      $endTime['year'] =  $request->getParameter('end_year', null, false);

      $isUpdated = Events::updateEvent($accessToken, $eventId, $title, $description, $fullDayFlag, $startTime, $endTime);

      // Output result
      if ($isUpdated == true) {
        $app->success(array("msg"=>"Updated the event with eventId $eventId."));
      } else {
        $app->halt(403, array("msg"=>"Not allowed to modify event $eventId"));
      }

    }
    catch (Libs\Exceptions\StringList $e) {
      $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
    }
    catch (Libs\Exceptions\MissingParameter $e) {
      $app->halt(400, $e->getFormatedMessage(), $e->getRESTCode());
    }
    catch (Exceptions\Events $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });


  /**
   * Route: DELETE /v1/umr/events
   *  Deletes an event (appointment) of the user given by the access-token.
   *
   * @See docs/api.pdf
   */
  $app->delete('/events/:eventId', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($eventId) use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // Fetch user-information
      $isDeleted = Events::deleteEvent($accessToken, $eventId);
      // Output result
      if ($isDeleted==true) {
        $app->success(array("msg"=>"Deleted the event with eventId $eventId."));
      } else {
        $app->halt(403, array("msg"=>"Could not delete the eventId $eventId."));
      }

    }
    catch (Exceptions\Events $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });
// End of '/v1/umr/' URI-Group
});
