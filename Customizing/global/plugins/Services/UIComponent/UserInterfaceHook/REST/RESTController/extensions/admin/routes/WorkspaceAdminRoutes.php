<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


$app->group('/admin', function () use ($app) {
    $app->get('/workspaces', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
        try {
            $request = $app->request;
            $limit = $request->params('limit', 25);
            $offset = $request->params('offset', 0);

            $t_start = microtime();
            Libs\RESTLib::initAccessHandling();
            $wa_model = new WorkspaceAdminModel();
            $result = $wa_model->scanUsersForWorkspaces($limit, $offset);
            $t_end = microtime();

            // TODO: Remove timing, just return data
            $app->success(array(
                'data' => $result,
                'execution_time' => $t_end - $t_start,
                'offset' => $offset,
                'limit' => $limit
            ));
        } catch (\Exception $e) {
            $app->halt(400, $e->getMessage());
        }
    });


    $app->get('/workspaces/:user_id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($user_id) use ($app) {
        try {
            $t_start = microtime();
            $result = array();
            $result['msg'] = sprintf('Workspaces of user %d.', $user_id);

            Libs\RESTLib::initAccessHandling();
            $wa_model = new WorkspaceAdminModel();
            $ws_array = $wa_model->getUserWorkspaceItems($user_id);
            $t_end = microtime();

            // TODO: Remove timing, just return data
            $app->success(array(
                'data' => $ws_array,
                'execution_time' => $t_end - $t_start
            ));

        } catch (\Exception $e) {
            $app->halt(400, $e->getMessage());
        }
    });
});
