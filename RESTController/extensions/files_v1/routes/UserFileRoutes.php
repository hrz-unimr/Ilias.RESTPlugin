<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\files_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\auth as Auth;
use \RESTController\libs as Libs;


$app->group('/v1', function () use ($app) {
    /**
     * Retrieves a user file provided its ref_id or obj_id.
     * @param meta_data - if this field exists, the endpoints returns only a description of the file.
     * @param id_type - (optional) "ref_id" or "obj_id", if omitted the type ref_id is assumed.
     * @param id - the ref or obj_id of the file.
     */
    $app->get('/files/:id', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function ($id) use ($app) {
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();


        $request = $app->request();

        $meta_data = $request->getParameter('meta_data',false, false);

        $id_type = $request->getParameter('id_type', 'ref_id', false);



        if ($id_type == "ref_id") {
            $obj_id = Libs\RESTilias::getObjId($id);
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
                $result = array(
                    'ext' => $fileObj->getFileExtension(),
                    'name' => $fileObj->getFileName(),
                    'size' => $fileObj->getFileSize(),
                    'type' => $fileObj->getFileType(),
                    'dir' => $fileObj->getDirectory(),
                    'version' => $fileObj->getVersion(),
                    'realpath' => $fileObj->getFile()
                );

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
