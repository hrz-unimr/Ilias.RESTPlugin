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


$app->group('/admin', function () use ($app) {
    /**
     * this is a tool for developers / admins to get
     * fast descriptions of objects or users specified by
     * obj_id, ref_id, usr_id or file_id
     *
     * Supported types: obj_id, ref_id, usr_id and file_id
     */
    $app->get('/describe/:id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($id) use ($app) {
        $request = $app->request();
        $id_type = $request->params('id_type', 'ref_id');

        $result = array('msg' => array());
        $model = new DescribrModel();
        if ($id_type == 'ref_id' || $id_type == 'obj_id') {
            if ($id_type == 'ref_id') {
                $obj_id = Libs\RESTLib::getObjIdFromRef($id);
                $id_type = 'obj_id';
            }

            if (!is_numeric($obj_id))
                $result['status'] = 'Object does not exist.';

            $a_descr = $model->describeIliasObject($obj_id);

            $result['object_description'] = $a_descr;
            $result['status'] = 'Object found.';

            if ($a_descr['type'] == "file") {
                $id = $obj_id;
                $id_type = "file_id";
            }
        }

        if ($id_type == 'usr_id') {
            $username = Libs\RESTLib::getUserNameFromId($id);
            if ($username == 'User unknown') {
                $result['msg'][] = 'User not found.';
            } else {
                $usr_model = new UsersModel();
                $usr_basic_info =  $usr_model->getBasicUserData($id);
                if (empty($usr_basic_info) == true) {
                    $result['status'] = 'Error: User not found.';
                } else {
                    $result['user'] = $usr_basic_info;
                    $result['status'] = 'User  found.';
                }
            }
        }

        if ($id_type == 'file_id') {
            try {
                $data = $model->describeFile($id);
                $result['file'] = $data;
                $result['status'] = 'Description of file with id = '.$id.'.';
            } catch (\Exception $e) {
                $result['status'] = 'Error: File not found.';
            }
        }
        $app->success($result);
    });

});
