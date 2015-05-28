<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\files_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


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
    $app->get('/files/:id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth',  function ($id) use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $user_id = $accessToken->getUserId();

        $request = new Libs\RESTRequest($app);
        $response = new Libs\RESTResponse($app);

        try {
            $meta_data = $request->params('meta_data');
            if (isset($meta_data)) {
                $meta_data = true;
            }
        } catch (\Exception $e) {
            $meta_data = false;
        }

        try {
            $id_type = $request->params('$id_type');
        } catch (\Exception $e) {
            $id_type = "ref_id";
        }


        if ($id_type == "ref_id") {
            $obj_id = Libs\RESTLib::getObjIdFromRef($id);
        } else {
            $obj_id = $id;
        }


        if ($meta_data == true) {
            $model = new FileModel();
            $fileObj = $model->getFileObjForUser($obj_id, $user_id);

            if (empty($fileObj)) {
                $response->setRESTCode("-1");
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
            $model = new FileModel();
            $fileObj = $model->getFileObjForUser($obj_id, $user_id);
            if (empty($fileObj)) {
                $response->setRESTCode("-1");
                $response->setMessage('Could not retrieve file with obj_id = ' . $obj_id . '.');
                $response->send();
                echo json_encode($result);
            } else {
                $fileObj->sendFile();
            }
        }
    });
});
