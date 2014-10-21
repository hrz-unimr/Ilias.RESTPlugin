<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/admin', function () use ($app) {

    $app->get('/workspaces', 'authenticateILIASAdminRole', function () use ($app) {
        try {
            $app = \Slim\Slim::getInstance();
            $env = $app->environment();

            if (count($app->request->post()) == 0 && count($app->request->get()) == 0) {
                $req_data = json_decode($app->request()->getBody(),true); // json
            } else {
                $req_data = $_REQUEST;
            }

            $limit = $req_data['limit'];
            $offset = $req_data['offset'];
            if (!isset($limit)) {
                $limit = 25;

            }
            if (!isset($offset)) {
                $offset = 0;
            }

            //echo $limit." ".$offset;


            $t_start = microtime();
            $result = array();
            $result['msg'] = 'Overview Workspaces';
            ilRestLib::initDefaultRestGlobals();
            ilRestLib::initAccessHandling();
            $wa_model = new ilWorkspaceAdminModel();

            $result['result'] = $wa_model->scanUsersForWorkspaces($limit, $offset);

            $t_end = microtime();
            $result['execution_time'] = $t_end - $t_start;

            $result['offset'] = $offset;
            $result['limit'] = $limit;

            $app->response()->header('Content-Type', 'application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            $app->response()->status(400);
            $app->response()->header('X-Status-Reason', $e->getMessage());
        }
    });


    $app->get('/workspaces/:user_id', 'authenticateILIASAdminRole', function ($user_id) use ($app) {
        try {
            $app = \Slim\Slim::getInstance();
            $env = $app->environment();
            $t_start = microtime();
            $result = array();
            $result['msg'] = 'Workspaces of user.';

            ilRestLib::initDefaultRestGlobals();
            ilRestLib::initAccessHandling();
            $wa_model = new ilWorkspaceAdminModel();
            $ws_array = $wa_model->getUserWorkspaceItems($user_id);
            $result['data'] = $ws_array;

            $t_end = microtime();
            $result['execution_time'] = $t_end - $t_start;
            $app->response()->header('Content-Type', 'application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            $app->response()->status(400);
            $app->response()->header('X-Status-Reason', $e->getMessage());
        }
    });
});
?>