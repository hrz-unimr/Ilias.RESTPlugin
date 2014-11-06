<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */


$app->group('/v1', function () use ($app) {
    /*
     * File Download
     */
    $app->get('/files/:id',  function ($id) use ($app) {
        // should include middleware 'authenticateTokenOnly'
        $user_id = 6;//ilRestLib::loginToUserId($env['user']);


        if (count($app->request->post()) == 0 && count($app->request->get()) == 0) {
            $req_data = json_decode($app->request()->getBody(),true); // json
        } else {
            $req_data = $_REQUEST;
        }

        $meta_data = $req_data['meta_data'];
        $id_type = $req_data['id_type'];
        if (isset($id_type) == true) {
            if ($id_type == "ref_id") {
                $obj_id = ilRestLib::refid_to_objid($id);
            } else {
                $obj_id = $id;
            }
        } else {
            $obj_id = ilRestLib::refid_to_objid($id);
        }

        $result = array();
        if (isset($meta_data) == true)
        {
            $model = new ilFileModel();

            $fileObj = $model->getFileObjForUser($obj_id, $user_id);
            if (empty($fileObj)) {
                $result['status'] = 'fail';
                $result['msg'] = 'Could not retrieve file with obj_id = ' . $obj_id . ".";
            } else {
                $result['status'] = 'success';
                $result['msg'] = 'Meta-data of file with id = ' . $id;
                $result['file']['ext'] = $fileObj->getFileExtension();
                $result['file']['name'] = $fileObj->getFileName();
                $result['file']['size'] = $fileObj->getFileSize();
                $result['file']['type'] = $fileObj->getFileType();
                $result['file']['dir'] = $fileObj->getDirectory();
                $result['file']['version'] = $fileObj->getVersion();
                $result['file']['realpath'] = $fileObj->getFile();
            }
            $app->response()->header('Content-Type', 'application/json');
            echo json_encode($result);
        } else
        {
            $model = new ilFileModel();
            $fileObj = $model->getFileObjForUser($obj_id, $user_id);
            if (empty($fileObj)) {
                $result['status'] = 'fail';
                $result['msg'] = 'Could not retrieve file with obj_id = ' . $obj_id . ".";
                $app->response()->header('Content-Type', 'application/json');
                echo json_encode($result);
            } else {
                $fileObj->sendFile();
            }
        }
    });
});
