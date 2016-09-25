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
   * Route: GET /v1/umr/objects
   *  Returns all relevant data for the ILIAS-Object given by the provided refId(s).
   *  [This endpoint parses HTTP-GET parameters, eg. ...?refids=1,2,3,10]
   *
   * @See docs/api.pdf
   */
  $app->get('/objects', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
      // Fetch userId & userName
      $accessToken = $app->request->getToken();

      try {
        // Fetch refIds
        $request      = $app->request;
        $refIdString  = $request->getParameter('refids', null, true);
        $refIds       = Libs\RESTRequest::parseIDList($refIdString, true);

        // Fetch data for refIds
        $data         = Objects::getData($accessToken, $refIds);

        // Output result
        $app->success($data);
      }
      catch (Libs\Exceptions\StringList $e) {
        $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
      }
      catch (Libs\Exceptions\MissingParameter $e) {
          $app->halt(400, $e->getFormatedMessage(), $e->getRESTCode());
      }
      catch (Exceptions\Objects $e) {
        $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
        $responseObject['data'] = $e->getData();
        $app->halt(500, $responseObject);
      }
  });


  /**
   * Route: GET /v1/umr/objects
   *  Returns all relevant data for the ILIAS-Object given by the provided refId(s).
   *  [This endpoint parses one URI parameter, eg. .../10]
   *
   * @See docs/api.pdf
   */
  $app->get('/objects/:refId', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($refId) use ($app) {
    // Fetch userId & userName
    $accessToken = $app->request->getToken();

    try {
      // RefId needs to be numeric (integer preferably)
      if (!is_numeric($refId)){
        $message = sprintf(Libs\RESTLib::MSG_PARSE_ISSUE, '\'' . $refId . '\'', 'URI-Parameter');

        throw new Libs\Exceptions\StringList($message, Libs\RESTLib::ID_PARSE_ISSUE);
      }

      // Fetch data for refIds
      $data    = Objects::getData($accessToken, intval($refId));

      // Output result
      $app->success($data);
    }
    catch (Libs\Exceptions\StringList $e) {
      $app->halt(422, $e->getRESTMessage(), $e->getRESTCode());
    }
    catch (Libs\Exceptions\MissingParameter $e) {
        $app->halt(400, $e->getFormatedMessage(), $e->getRESTCode());
    }
    catch (Exceptions\Objects $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getRESTMessage(), $e->getRESTCode());
      $responseObject['data'] = $e->getData();
      $app->halt(500, $responseObject);
    }
  });

// End of '/v1/umr/' URI-Group
});
