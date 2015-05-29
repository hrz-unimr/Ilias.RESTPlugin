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

        $request = $app->request();
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
                // TODO: Replace string with const class-variable und error-code too!
                $app->halt(500, 'Could not retrieve file with obj_id = ' . $obj_id . '.', -1);
            } else {
                $result = array();
                $result['ext'] = $fileObj->getFileExtension();
                $result['name'] = $fileObj->getFileName();
                $result['size'] = $fileObj->getFileSize();
                $result['type'] = $fileObj->getFileType();
                $result['dir'] = $fileObj->getDirectory();
                $result['version'] = $fileObj->getVersion();
                $result['realpath'] = $fileObj->getFile();

                $app->success($result);
            }
        }
        else {
            $model = new FileModel();
            $fileObj = $model->getFileObjForUser($obj_id, $user_id);
            
            if (empty($fileObj))
                // TODO: Replace string with const class-variable und error-code too!
                $app->halt(500, 'Could not retrieve file with obj_id = ' . $obj_id . '.', -1);
            else
                $fileObj->sendFile();
        }
    });
});
