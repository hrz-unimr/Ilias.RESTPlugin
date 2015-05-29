<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\roles_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\auth as Auth;


$app->get('/v1/roles', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
    try {
        // Fetch authorized user
        $auth = new Auth\Util();
        $user = $auth->getAccessToken()->getUserName();
        $roles = $app->request()->params('roles');

        $model = new RolesModel();
        $model->getAllRoles($user, $roles);

        $app->success($result);
    }
    catch(\Exception $e) {
        $app->halt(422, $e->getMessage());
    }
});
