<?php
/*
 * Route definitions for the ILIAS File REST API
 */


$app->group('/v1', function () use ($app) {
    /**
     * Retrieves a user file provided its ref_id or obj_id.
     * @param meta_data - if this field exists, the endpoints returns only a description of the file.
     * @param id_type - (optional) "ref_id" or "obj_id", if ommited the type ref_id is assumed.
     * @param id - the ref or obj_id of the file.
     */
    $app->get('/files/:id', 'authenticate',  function ($id) use ($app) {
        $env = $app->environment();
        $user_id = ilRestLib::loginToUserId($env['user']);

        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);

        try {
            $meta_data = $request->getParam('meta_data');
            if (isset($meta_data)) {
                $meta_data = true;
            }
        } catch (Exception $e) {
            $meta_data = false;
        }

        try {
            $id_type = $request->getParam('$id_type');
        } catch (Exception $e) {
            $id_type = "ref_id";
        }


        if ($id_type == "ref_id") {
            $obj_id = ilRestLib::refid_to_objid($id);
        } else {
            $obj_id = $id;
        }


        if ($meta_data == true) {
            $model = new ilFileModel();
            $fileObj = $model->getFileObjForUser($obj_id, $user_id);

            if (empty($fileObj)) {
                $response->setRestCode("-1");
                $response->setMessage('Could not retrieve file with obj_id = ' . $obj_id . '.');
               // $result['status'] = 'fail';
               // $result['msg'] = 'Could not retrieve file with obj_id = ' . $obj_id . ".";
            } else {
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
            }
        } else
        {
            $model = new ilFileModel();
            $fileObj = $model->getFileObjForUser($obj_id, $user_id);
            if (empty($fileObj)) {
                $response->setRestCode("-1");
                $response->setMessage('Could not retrieve file with obj_id = ' . $obj_id . '.');
                $response->send();
                echo json_encode($result);
            } else {
                $fileObj->sendFile();
            }
        }
    });
});
