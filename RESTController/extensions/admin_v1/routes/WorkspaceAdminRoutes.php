<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;


$app->group('/v1/admin', function () use ($app) {
    /**
     * Provides an overview of workspaces of a limited amount of users.
     */
    $app->get('/workspaces', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

            $request = $app->request;
            $limit = $request->getParameter('limit', 25);
            $offset = $request->getParameter('offset', 0);

            $t_start = microtime();
            Libs\RESTilias::initAccessHandling();
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

    });

    /**
     * Returns the content of the workspace from a user specified by her/his user id.
     */
    $app->get('/workspaces/:user_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($user_id) use ($app) {

            $t_start = microtime();
            $result = array();
            $result['msg'] = sprintf('Workspaces of user %d.', $user_id);

            Libs\RESTilias::initAccessHandling();
            $wa_model = new WorkspaceAdminModel();
            $ws_array = $wa_model->getUserWorkspaceItems($user_id);
            $t_end = microtime();

            // TODO: Remove timing, just return data
            $app->success(array(
                'data' => $ws_array,
                'execution_time' => $t_end - $t_start
            ));

    });
});
