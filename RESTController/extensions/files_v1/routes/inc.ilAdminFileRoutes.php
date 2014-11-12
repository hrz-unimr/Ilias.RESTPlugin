<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */


$app->group('/admin', function () use ($app) {
    /*
     * File Download
     */
    $app->get('/files/:id', 'authenticateILIASAdminRole', function ($id) use ($app) {



        if (count($app->request->post()) == 0 && count($app->request->get()) == 0) {
            $req_data = json_decode($app->request()->getBody(),true); // json
        } else {
            $req_data = $_REQUEST;
        }


        $meta_data = $req_data['meta_data'];
       // $meta_data = true;
/*        $id_type = $req_data['id_type'];
        if (isset($id_type) == true) {
            if ($id_type == "ref_id") {
                $obj_id = ilRestLib::refid_to_objid($id);
            } else {
                $obj_id = $id;
            }
        } else {
            $obj_id = ilRestLib::refid_to_objid($id);
        }
*/
      //  $meta_data = true;
        $result = array();
        if (isset($meta_data) == true)
        {

            $model = new ilFileModel();
            $obj_id = ilRestLib::refid_to_objid($id);
            $fileObj = $model->getFileObj($obj_id);
 //           $fileObj = $model->getFileObjForUser($obj_id,6);

            $result['status'] = 'success';
            $result['msg'] = 'Meta-data of file with id = '.$id;
            $result['file']['ext'] = $fileObj->getFileExtension();
            $result['file']['name'] = $fileObj->getFileName();
            $result['file']['size'] = $fileObj->getFileSize();
            $result['file']['type'] = $fileObj->getFileType();
            $result['file']['dir'] = $fileObj->getDirectory();
            $result['file']['version'] = $fileObj->getVersion();
            $result['file']['realpath'] = $fileObj->getFile();

            $app->response()->header('Content-Type', 'application/json');
            echo json_encode($result);
        } else
        {
            $model = new ilFileModel();
            $fileObj = $model->getFileObj($id);
            $fileObj->sendFile();
        }
        /*$res = $app->response();
        $res['Content-Description'] = 'File Transfer';
        $res['Content-Type'] = 'application/octet-stream';
        $res['Content-Disposition'] ='attachment; filename=' . $fileObj->getFileName();
        $res['Content-Transfer-Encoding'] = 'binary';
        $res['Expires'] = '0';
        $res['Cache-Control'] = 'must-revalidate';
        $res['Pragma'] = 'public';
        $res['Content-Length'] = $fileObj->getFileSize();
        */
    });

    /*
     * File Upload
     */
    $app->post('/files', 'authenticateILIASAdminRole', function () use ($app) { // create
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

            $model = new ilFileModel();
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
