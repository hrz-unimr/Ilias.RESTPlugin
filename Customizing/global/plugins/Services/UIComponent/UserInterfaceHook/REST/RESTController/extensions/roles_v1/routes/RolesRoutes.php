<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\roles_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\auth as Auth;


$app->get('/v1/roles', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
    try {
        // Fetch authorized user
        $user = Auth\Util::getAccessToken()->getUserName();
        $roles = $app->request()->params('roles');

        $model = new RolesModel();
        $model->getAllRoles($user, $roles);

        $app->success($result);
    }
    catch(\Exception $e) {
        $app->halt(422, $e->getMessage());
    }
});
