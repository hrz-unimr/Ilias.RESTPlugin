<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/admin', function () use ($app) {

    /**
     * this is a tool for developers / admins to get
     * fast descriptions of objects or users specified by
     * obj_id, ref_id, usr_id or file_id
     *
     * Supported types: obj_id, ref_id, usr_id and file_id
     */
    $app->get('/describe/:id', 'authenticateILIASAdminRole', function ($id) use ($app) {

        $app = \Slim\Slim::getInstance();
        $env = $app->environment();

        $id_type = $app->request()->params("id_type"); // ref_id, obj_id, usr_id
        if (!defined($id_type)) {
            $id_type = 'ref_id';
        }


       // echo "iD_type =".$id_type;
        $result = array();
        $model = new ilDescribrModel();
        if ($id_type == 'ref_id' || $id_type == 'obj_id') {
            if ($id_type == 'ref_id') {
                $obj_id = ilRestLib::refid_to_objid($id);
                $id_type = 'obj_id';
            }
            //echo "obj_id:".$obj_id;
            try {
                if (is_numeric($obj_id)==false) {
                    throw new Exception('Obj id does not exist');
                }
                $a_descr = $model->describeIliasObject($obj_id);
                $result['object_description'] = $a_descr;
            } catch (Exception $e) {
                $result['error'] = 'Object not found.';// Try to explain a user with id = '.$id.' instead.';
                $id_type = 'usr_id';
            }
        }

        if ($id_type == 'usr_id') {
            $username = ilRestLib::userIdtoLogin($id);
            //echo $username;
            try {
                if ($username == 'User unknown') {
                    $result['msg'] = 'User not found.';
                    throw new Exception('User does not exist');
                } else {
                    ilRestLib::initDefaultRestGlobals();
                    $usr_model = new ilUsersModel();
                    $usr_basic_info =  $usr_model->getBasicUserData($id);
                    if (empty($usr_basic_info) == true) {
                        $result['msg'] = 'User not found.';
                    } else {
                        $result['msg'] = 'User found.';
                        $result['user'] = $usr_basic_info;
                    }
                }
            } catch (Exception $e) {
                $result['error'] = $result['error'].'User not found.';// Try to explain a file with id = '.$id.' instead.';
                $id_type = 'file_id';
            }
        }

        if ($id_type == 'file_id') {
            try {
                $data = $model->describeFile($id);
                $result['msg'] = 'Description of file with id = '.$id;
                $result['file'] = $data;
            } catch (Exception $e) {
                $result['error'] = $result['error'].'File not found.';
            }
        }

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);

    });



});
