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
use \RESTController\core\auth as Auth;

$app->group('/v1/umr', function () use ($app) {
    /**
     * Route: GET /v1/umr/news
     *  [Without HTTP-GET Parameters] Gets all news for the user encoded by the access-token.
     * @See docs/api.pdf
     */
    $app->get('/news', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        // Fetch userId & userName
        $accessToken  = Auth\Util::getAccessToken();
        try {
            $news  = News::getAllNews($accessToken);
            //$user_id = $accessToken->getUserId();
            //$news       = News::getPDNewsForUser($user_id);

            // Output result
            $app->success($news);
        }
        catch (Libs\Exceptions\IdParseProblem $e) {
            $app->halt(422, $e->getMessage(), $e->getRESTCode());
        }
        catch (Exceptions\Events $e) {
            $responseObject         = Libs\RESTLib::responseObject($e->getMessage(), $e->getRestCode());
            $responseObject['data'] = $e->getData();
            $app->halt(500, $responseObject);
        }
    });
});