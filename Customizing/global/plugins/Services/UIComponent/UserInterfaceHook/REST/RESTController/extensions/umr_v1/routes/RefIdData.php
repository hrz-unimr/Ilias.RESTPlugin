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
   * Route: GET /v1/umr/refiddata
   *
   * @See docs/api.pdf
   */
  $app->get('/refiddata', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
      // Fetch userId & userName
      $auth         = new Auth\Util();
      $accessToken  = $auth->getAccessToken();

      try {
        // Fetch refIds
        $request      = $app->request;
        $refIdString  = $request->params('refids', null, true);
        $refIds       = libs\RESTLib::parseIdsFromString($refIdString, true);

        // Fetch data for refIds
        $data         = RefIdData::getData($accessToken, $refIds);

        // Output result
        $app->success($data);
      }
      catch (Libs\Exceptions\IdParseProblem $e) {
        $app->halt(422, $e->getMessage(), $e->getRESTCode());
      }
      catch (Exceptions\RefIdData $e) {
        $responseObject         = Libs\RESTLib::responseObject($e->getMessage(), $e->getRestCode());
        $responseObject['data'] = $e->getData();
        $app->halt(422, $responseObject);
      }
      catch (Libs\Exceptions\MissingParameter $e) {
          $app->halt(400, $e->getFormatedMessage(), $e::ID);
      }
  });
  $app->get('/refiddata/:refId', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($refId) use ($app) {
    // Fetch userId & userName
    $auth         = new Auth\Util();
    $accessToken  = $auth->getAccessToken();

    try {
      // RefId needs to be numeric (integer preferably)
      if (!is_numeric($refId)){
        $message = sprintf(Libs\RESTLib::MSG_PARSE_ISSUE, '\'' . $refId . '\'', 'URI-Parameter');

        throw new Libs\Exceptions\IdParseProblem($message, Libs\RESTLib::ID_PARSE_ISSUE);
      }

      // Fetch data for refIds
      $data    = RefIdData::getData($accessToken, intval($refId));

      // Output result
      $app->success($data);
    }
    catch (Libs\Exceptions\IdParseProblem $e) {
      $app->halt(422, $e->getMessage(), $e->getRESTCode());
    }
    catch (Exceptions\RefIdData $e) {
      $responseObject         = Libs\RESTLib::responseObject($e->getMessage(), $e->getRestCode());
      $responseObject['data'] = $e->getData();
      $app->halt(422, $responseObject);
    }
    catch (Libs\Exceptions\MissingParameter $e) {
        $app->halt(400, $e->getFormatedMessage(), $e::ID);
    }
  });

// End of '/v1/umr/' URI-Group
});