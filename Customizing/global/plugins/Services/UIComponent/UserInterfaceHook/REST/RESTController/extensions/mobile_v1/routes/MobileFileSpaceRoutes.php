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
            $model = new \RESTController\extensions\files_v1\PersonalFileSpaceModel();
            $status = $model->clone_file_into_repository($user_id, $file_id, $target_ref_id);
            $t_end = microtime();
            $responseMsg = "Success";
            if ($status == false) {
                $responseMsg = "Failed";
            }
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
            $model = new \RESTController\extensions\files_v1\PersonalFileSpaceModel();
            $status = $model->clone_file_into_repository($user_id, $file_id, $target_ref_id);
            $t_end = microtime();
            $responseMsg = "Success";
            if ($status == false) {
                $responseMsg = "Failed";
            }
            $response->addData("execution_time", $t_end - $t_start);
            $response->addData("Status", $responseMsg);
            $response->setMessage('MyFilespaceCopy');
        } catch (RESTException $e) {
            $response->setRESTCode($e->getCode());
        }
        $response->send();
    });

    /**
     * see POST /myfilespaceupload
     */
    $app->get('/myfilespaceupload', function() use ($app) {
        $app->log->debug("Myfilespace upload via GET");
        $response = new RESTResponse($app);
        $response->setMessage("Pls use the POST method");
        $response->send();
    });

    /**
     * Uploads a single file via POST into the user's "myfilespace".
     */
    $app->post('/myfilespaceupload','\RESTController\libs\AuthMiddleware::authenticate', function() use ($app) {
        $app->log->debug("Myfilespace upload via POST");
        $t_start = microtime();
        $env = $app->environment();
        $user_id = RESTLib::loginToUserId($env['user']);

        $response = new RESTResponse($app);
        $request = new RESTRequest($app);

        $errorCode = $_FILES["mupload"]["error"];
        if ($errorCode > UPLOAD_ERR_OK) {
            $response->setMessage("Error during file upload");
            $response->setData("Code", $errorCode);
            $response->setData("Explanation", "http://php.net/manual/en/features.file-upload.errors.php");
            $response->setHttpStatus("400");
            $response->setRestCode("400");
            $response->send();
            exit;
        }
        //error_log(1);
        // Try to upload
        RESTLib::initAccessHandling();
        $model = new \RESTController\extensions\files_v1\PersonalFileSpaceModel();
        $model->handleFileUploadIntoMyFileSpace($_FILES["mupload"],$user_id);
        $t_end = microtime();
        $response->addData('farraydump',print_r($_FILES["mupload"],true));
        $response->addMessage('Done Uploading File');
        $response->addData("execution_time", $t_end - $t_start);
        $response->send();
    });

    $app->delete('/myfilespacedelete', '\RESTController\libs\AuthMiddleware::authenticate', function() use ($app) {
        $app->log->debug("Myfilespace upload via GET");
        $t_start = microtime();
        $env = $app->environment();
        $user_id = RESTLib::loginToUserId($env['user']);

        $response = new RESTResponse($app);
        $request = new RESTRequest($app);

        try {
            $file_id = $request->getParam('file_id', null, false);
            RESTLib::initAccessHandling();
            $model = new \RESTController\extensions\files_v1\PersonalFileSpaceModel();
            $model->deleteFromMyFileSpace($file_id, $user_id);

        } catch (RESTException $e) {
            $response->setRESTCode($e->getCode());
        }
        $t_end = microtime();
        $response->addData("execution_time", $t_end - $t_start);
        $response->setMessage("Dev Delete File From MyFileSpace");
        $response->send();
    });

});
