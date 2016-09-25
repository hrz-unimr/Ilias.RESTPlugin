<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\auth as Auth;
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as Exceptions;
use \RESTController\extensions\admin_v1 as Admin;
use \RESTController\extensions\files_v1 as Files;


$app->group('/v1/m', function () use ($app) {
    /**
     * This route retrieves a listing of files that are contained in the user's 'personal workspace'.
     * In this version, the user file space is indeed the 'workspace'. In a future version it could be imagined, that
     * a special area within the global repository is used which is protected by role permissions. Therefore we use the placeholder 'myfilespace'.
     */
    $app->get('/myfilespace', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        Libs\RESTilias::initAccessHandling();
        $wa_model = new Admin\WorkspaceAdminModel();
        $ws_array = $wa_model->getUserWorkspaceItems($user_id);

        $app->success(array(
            'myfilespace' => $ws_array
        ));
    });

    /**
     * This route enables the user to add a file object from her/his personal file space to a location within the the repository.
     * The user needs write permission to copy the specified file to a chosen destination. The following parameters are required:
     * file_id (as obtainable by the /myfilespace endpoint) and a ref_id of the target container.
     */
    $app->get('/myfilespacecopy',RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();
        $request = $app->request();

        try {
            $file_id = $request->getParameter('file_id', null, false);
            $target_ref_id = $request->getParameter('target_ref_id', null, false);

            Libs\RESTilias::initAccessHandling();
            $model = new Files\PersonalFileSpaceModel();
            $status = $model->clone_file_into_repository($user_id, $file_id, $target_ref_id);

            if ($status == false) {
                $app->halt(500, 'File could not be copied!');
            } else {
                $app->success(array('msg' => 'Moved item from personal file space to repository.'));
            }

        } catch(Exceptions\MissingParameter $e) {
            $app->halt(400, $e->getFormatedMessage(), $e::ID);
        }
    });


    /**
     * see GET /myfilespacecopy
     */
    $app->post('/myfilespacecopy',RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();
        $request = $app->request();

        try {
            $file_id = $request->getParameter('file_id', null, false);
            $target_ref_id = $request->getParameter('target_ref_id', null, false);

            Libs\RESTilias::initAccessHandling();
            $model = new Files\PersonalFileSpaceModel();
            $status = $model->clone_file_into_repository($user_id, $file_id, $target_ref_id);

            if ($status == false) {
                $app->halt(500, 'File could not be copied!');
            } else {
                $app->success(array("msg"=>"Moved item from personal file space to repository."));
            }
        } catch(Exceptions\MissingParameter $e) {
            $app->halt(400, $e->getFormatedMessage(), $e::ID);
        }
    });


    /**
     * see POST /myfilespaceupload
     */
    $app->get('/myfilespaceupload', RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        $app->log->debug('Myfilespace upload via GET');
        $app->halt(422, 'Please use the POST method', 'RESTController\\extensions\\mobile_v1\\MyFileSpaceRoutes::ID_USE_GET');
    });


    /**
     * Uploads a single file via POST into the user's 'myfilespace'.
     */
    $app->post('/myfilespaceupload',RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        $app->log->debug('Myfilespace upload via POST');

        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        $errorCode = $_FILES['uploadfile']['error'];
        if ($errorCode > UPLOAD_ERR_OK) {
            $error = array(
                'msg' => 'Error during file upload',
                'code' => $errorCode,
                'Explanation' => 'http://php.net/manual/en/features.file-upload.errors.php'
            );
            $app->halt(400, $error, -1);
        }
        //error_log(1);
        // Try to upload file
        Libs\RESTilias::initAccessHandling();
        $model = new Files\PersonalFileSpaceModel();
        $resp = $model->handleFileUploadIntoMyFileSpace($_FILES['uploadfile'],$user_id,$user_id, 'Mobile Uploads');

        $result = array('id' => $resp->id, 'msg' => "File uploaded to the personal file space.");
        $app->success($result);
    });


    /**
     * Deletes a file from a user's filespace.
     */
    $app->delete('/myfilespacedelete', RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        $app->log->debug('Myfilespace delete');

        $accessToken = $app->request->getToken();
        $user = $accessToken->getUserName();
        $user_id = $accessToken->getUserId();

        $request = $app->request();
        try {
            $file_id = $request->getParameter('file_id', null, false);
            Libs\RESTilias::initAccessHandling();
            $model = new Files\PersonalFileSpaceModel();
            $model->deleteFromMyFileSpace($file_id, $user_id);
        } catch(Exceptions\MissingParameter $e) {
            $app->halt(400, $e->getFormatedMessage(), $e::ID);
        }

        $app->success(array('msg'=>'Deleted file from personal file space.'));
    });

});
