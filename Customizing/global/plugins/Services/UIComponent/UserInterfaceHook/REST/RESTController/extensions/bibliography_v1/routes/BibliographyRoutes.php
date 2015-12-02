<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\bibliography_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;


$app->group('/v1', function () use ($app) {
    /**
     * Returns the personal ILIAS contacts for a user specified by id.
     */
    $app->get('/biblio/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $accessToken = Auth\Util::getAccessToken();
        $authorizedUserId = $accessToken->getUserId();
         try {
            $model = new BibliographyModel();
            $data = $model->getBibliography($ref_id,$authorizedUserId);
            $app->success($data);
        } catch (Libs\Exceptions\ReadFailed $e) {
            $app->halt(404, $e->getMessage(), -15);
        }

    });
});
