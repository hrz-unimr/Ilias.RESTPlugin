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


$app->group('/admin', function () use ($app) {
    /*
     * File Download
     */
    $app->get('/files/:id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($id) use ($app) {
        $request = $app->request();
        try {
            $meta_data = $request->params('meta_data');
            if (isset($meta_data)) {
                $meta_data = true;
            }
        } catch (\Exception $e) {
            $meta_data = false;
        }

        if ($meta_data == true) {

            $model = new Files\FileModel();
            $obj_id = Libs\RESTLib::getObjIdFromRef($id);
            $fileObj = $model->getFileObj($obj_id);
 //           $fileObj = $model->getFileObjForUser($obj_id,6);

            $result = array();
            $result['msg'] = 'Meta-data of file with id = ' . $id . '.';
            $result['file']['ext'] = $fileObj->getFileExtension();
            $result['file']['name'] = $fileObj->getFileName();
            $result['file']['size'] = $fileObj->getFileSize();
            $result['file']['type'] = $fileObj->getFileType();
            $result['file']['dir'] = $fileObj->getDirectory();
            $result['file']['version'] = $fileObj->getVersion();
            $result['file']['realpath'] = $fileObj->getFile();

            $app->success($result);
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
    $app->post('/files', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) { // create
        $request = $app->request();
        $repository_ref_id = $request->params("ref_id");
        $title = $request->params("title");
        $description = $request->params("description");

        $result = array();
        if (isset($_FILES['uploadfile'])) {
            $_FILES['uploadfile']['name'];
            $_FILES['uploadfile']['size'];

            $file_upload = $_FILES['uploadfile'];
            $file_upload['title']= $title==null ? "" : $title;
            $file_upload['description'] = $description == null ? "" : $description;

            $model = new Files\FileModel();
            $uploadresult = $model->handleFileUpload($file_upload, $repository_ref_id);
            $result['msg'] = sprintf("Uploaded = [%s] [%d]", $_FILES['uploadfile']['name'], $_FILES['uploadfile']['size']);
            $result['target_in_repository'] = $repository_ref_id;

            $app->success($result);
        }
        else
            $app->halt(400, 'Upload failed');
    });
});
