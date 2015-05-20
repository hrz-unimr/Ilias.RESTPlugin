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
use \RESTController\extensions\files_v1 as Files;


/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */


$app->group('/admin', function () use ($app) {
    /*
     * File Download
     */
    $app->get('/files/:id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuthILIASAdminRole', function ($id) use ($app) {

        $env = $app->environment();
        //$user_id = RESTLib::loginToUserId($env['user']);

        $request = new Libs\RESTRequest($app);
        $response = new Libs\RESTResponse($app);

        try {
            $meta_data = $request->getParam('meta_data');
            if (isset($meta_data)) {
                $meta_data = true;
            }
        } catch (\Exception $e) {
            $meta_data = false;
        }

        if ($meta_data == true) {

            $model = new Files\FileModel();
            $obj_id = Libs\RESTLib::refid_to_objid($id);
            $fileObj = $model->getFileObj($obj_id);
 //           $fileObj = $model->getFileObjForUser($obj_id,6);

            $response->setMessage('Meta-data of file with id = ' . $id . '.');

            $result = array();
            $result['file']['ext'] = $fileObj->getFileExtension();
            $result['file']['name'] = $fileObj->getFileName();
            $result['file']['size'] = $fileObj->getFileSize();
            $result['file']['type'] = $fileObj->getFileType();
            $result['file']['dir'] = $fileObj->getDirectory();
            $result['file']['version'] = $fileObj->getVersion();
            $result['file']['realpath'] = $fileObj->getFile();
            $response->addData("file", $result['file']);
            $response->send();
        } else
        {
            $model = new Files\FileModel();
            $fileObj = $model->getFileObj($id);
            $fileObj->sendFile();
        }

    });

    /*
     * File Upload
     */
    $app->post('/files', '\RESTController\libs\OAuth2Middleware::TokenRouteAuthILIASAdminRole', function () use ($app) { // create
        $repository_ref_id = $app->request()->params("ref_id");
        $title = $app->request()->params("title");
        $description = $app->request()->params("description");

        $result = array();
        if (isset($_FILES['uploadfile'])) {
            $_FILES['uploadfile']['name'];
            $_FILES['uploadfile']['size'];

            //echo "Repository ID: ".$repository_ref_id;

            $file_upload = $_FILES['uploadfile'];

            $file_upload['title']= $title==null ? "" : $title;
            $file_upload['description'] = $description == null ? "" : $description;
            //var_dump($file_upload);

            $model = new Files\FileModel();
            $uploadresult = $model->handleFileUpload($file_upload, $repository_ref_id);
            //var_dump($result);
            $result['status'] = "success";
            $result['msg'] = sprintf("Uploaded = [%s] [%d]", $_FILES['uploadfile']['name'], $_FILES['uploadfile']['size']);
            $result['target_in_repository'] = $repository_ref_id;
        } else {
            $result['status'] = "upload failed";
        }

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);

    });

});
