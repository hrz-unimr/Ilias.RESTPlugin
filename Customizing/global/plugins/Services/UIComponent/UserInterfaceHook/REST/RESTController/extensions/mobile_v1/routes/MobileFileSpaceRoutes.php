<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use RESTController\libs\RESTException;
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \RESTController\extensions\admin\WorkspaceAdminModel;

/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/v1/m', function () use ($app) {

    /**
     * This route retrieves a listing of files that are contained in the user's "personal workspace".
     * In this version, the user file space is indeed the "workspace". In a future version it could be imagined, that
     * a special area within the global repository is used which is protected by role permissions. Therefore we use the placeholder "myfilespace".
     */
    $app->get('/myfilespace', '\RESTController\libs\AuthMiddleware::authenticate', function () use ($app) {
        $t_start = microtime();
        $env = $app->environment();
        $user_id = RESTLib::loginToUserId($env['user']);
        $response = new RESTResponse($app);
        RESTLib::initAccessHandling();
        $wa_model = new WorkspaceAdminModel();
        $ws_array = $wa_model->getUserWorkspaceItems($user_id);
        $response->addData("myfilespace", $ws_array);
        $t_end = microtime();
        $response->addData("execution_time", $t_end - $t_start);
        $response->setMessage('MyFilespace listing');
        $response->send();
    });

    /**
     * This route enables the user to add a file object from her/his personal file space to a location within the the repository.
     * The user needs write permission to copy the specified file to a chosen destination. The following parameters are required:
     * file_id (as obtainable by the /myfilespace endpoint) and a ref_id of the target container.
     */
    $app->get('/myfilespacecopy','\RESTController\libs\AuthMiddleware::authenticate', function() use ($app) {
        $t_start = microtime();
        $env = $app->environment();
        $user_id = RESTLib::loginToUserId($env['user']);
        $response = new RESTResponse($app);
        $request = new RESTRequest($app);

        try {
            $file_id = $request->getParam('file_id', null, false);
           // if ($file_id == null) throw RESTException::getWrongParamException('Parameter is missing', 'file_id');
            $target_ref_id = $request->getParam('target_ref_id', null, false);
           // if ($target_ref_id == null) throw RESTException::getWrongParamException('Parameter is missing', 'target_ref_id');

            RESTLib::initAccessHandling();
            $status = $model = new \RESTController\extensions\files_v1\PersonalFileSpaceModel();
            $responseMsg = "Success";
            if ($status == false) {
                $responseMsg = "Failed";
            }

            $model->clone_file_into_repository($user_id, $file_id, $target_ref_id);
            $t_end = microtime();
            $response->addData("execution_time", $t_end - $t_start);
            $response->addData("Status", $responseMsg);
            $response->setMessage('MyFilespaceCopy');
        } catch (RESTException $e) {
            $response->setRESTCode($e->getCode());
        }
        $response->send();
    });


    /**
     * see GET /myfilespacecopy
     */
    $app->post('/myfilespacecopy','\RESTController\libs\AuthMiddleware::authenticate', function() use ($app) {
        $t_start = microtime();
        $env = $app->environment();
        $user_id = RESTLib::loginToUserId($env['user']);
        $response = new RESTResponse($app);
        $request = new RESTRequest($app);

        try {
            $file_id = $request->getParam('file_id', null, false);
            // if ($file_id == null) throw RESTException::getWrongParamException('Parameter is missing', 'file_id');
            $target_ref_id = $request->getParam('target_ref_id', null, false);
            // if ($target_ref_id == null) throw RESTException::getWrongParamException('Parameter is missing', 'target_ref_id');

            RESTLib::initAccessHandling();
            $status = $model = new \RESTController\extensions\files_v1\PersonalFileSpaceModel();
            $responseMsg = "Success";
            if ($status == false) {
                $responseMsg = "Failed";
            }

            $model->clone_file_into_repository($user_id, $file_id, $target_ref_id);
            $t_end = microtime();
            $response->addData("execution_time", $t_end - $t_start);
            $response->addData("Status", $responseMsg);
            $response->setMessage('MyFilespaceCopy');
        } catch (RESTException $e) {
            $response->setRESTCode($e->getCode());
        }
        $response->send();
    });


});
