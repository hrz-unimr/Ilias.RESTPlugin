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

/**
 * This is a tool for developers / admins to get
 * descriptions of objects or users specified by
 * obj_id, ref_id, usr_id or file_id
 *
 * Supported types: obj_id, ref_id, usr_id and file_id
 */
$app->group('/v1/admin', function () use ($app) {
    /**
     * Returns a description of an object or user specified by
     * its obj_id, ref_id, usr_id or file_id
     *
     * Supported types: obj_id, ref_id, usr_id and file_id
     */
    $app->get('/describe/:id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($id) use ($app) {
        $request = $app->request();
        $id_type = $request->getParameter('id_type', 'ref_id');

        $result = array('msg' => array());
        $model = new DescribrModel();
        if ($id_type == 'ref_id' || $id_type == 'obj_id') {
            if ($id_type == 'ref_id') {
                $app->log->debug('in route id: '.$id);
                $obj_id = Libs\RESTilias::getObjId($id);
                $app->log->debug('in route id: '.$obj_id);
                $id_type = 'obj_id';
            } else {
                $obj_id = $id;
            }

            if (!is_numeric($obj_id))
                $result['status'] = 'Object does not exist.';

            $app->log->debug('describe object with obj_id= '.$obj_id);
            $a_descr = $model->describeIliasObject($obj_id);

            $result['object_description'] = $a_descr;
            $result['status'] = 'Object found.';

            if ($a_descr['type'] == "file") {
                $id = $obj_id;
                $id_type = "file_id";
            }
        }

        if ($id_type == 'usr_id') {
            $username = Libs\RESTilias::getUserName($id);
            if ($username == 'User unknown') {
                $result['msg'][] = 'User not found.';
            } else {
                $usr_model = new \RESTController\extensions\users_v1\UsersModel();
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
