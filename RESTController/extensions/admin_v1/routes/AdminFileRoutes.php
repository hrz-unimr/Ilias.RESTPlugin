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
use \RESTController\extensions\files_v1 as Files;

$app->group('/v1/admin', function () use ($app) {
    /*
     * File Download
     */
    $app->get('/files/:id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($id) use ($app) {
        $request = $app->request();

        try {
            $meta_data = filter_var($request->getParameter('meta_data', null, true), FILTER_VALIDATE_BOOLEAN);
        } catch (\Exception $e) {
            $meta_data = false;
        }

        if ($meta_data == true) {

            $model = new Files\FileModel();
            $obj_id = Libs\RESTilias::getObjId($id);
            $fileObj = $model->getFileObj($obj_id);

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
            $obj_id = Libs\RESTilias::getObjId($id);
            $fileObj = $model->getFileObj($obj_id);
            $fileObj->sendFile();
        }

    });


    /*
     * File Upload
     */
    $app->post('/files', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) { // create
        $request = $app->request();
        $repository_ref_id = $request->getParameter("ref_id");
        $title = $request->getParameter("title");
        $description = $request->getParameter("description");

        $result = array();
        if (isset($_FILES['uploadfile'])) {
            $_FILES['uploadfile']['name'];
            $_FILES['uploadfile']['size'];

            $file_upload = $_FILES['uploadfile'];
            $file_upload['title']= $title==null ? "" : $title;
            $file_upload['description'] = $description == null ? "" : $description;
            try {
                Libs\RESTilias::getObjId($repository_ref_id); // sanity check
            } catch (\Exception $e) {
                $app->halt(404, $e->getMessage());
            }
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
