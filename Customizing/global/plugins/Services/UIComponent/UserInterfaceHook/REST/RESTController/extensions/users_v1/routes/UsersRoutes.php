<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\users_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// users
$app->get('/v1/users', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
    try {

        $limit = 10;
        $offset = 0;

        $result = array();
        $usr_model = new UsersModel();

        $fields = array('login','email');
        $request = $app->request();
        $reqFields = $request->params('fields');
        if (isset($reqFields)){
            $fields = explode(",",$reqFields);
        }
        if ($request->params('limit')){
            $limit = $request->params('limit');
        }
        if ($request->params('offset')){
            $offset = $request->params('offset');
        }
        $result['_metadata']['limit'] = $limit;
        $result['_metadata']['offset'] = $offset;
        $all_users = $usr_model->getAllUsers($fields);
        $totalCount = count($all_users);
        $result['_metadata']['totalCount'] = $totalCount;
        // TODO: Sanity check on $offset parameter

        for ($i = $offset; $i<min($totalCount, $offset+$limit); $i++) {
            $current_user = array('user'=>$all_users[$i]);
            $result['users'][] = $current_user;
        }

        $app->success($result);

    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});

$app->get('/v1/users/:user_id', '\RESTController\libs\OAuth2Middleware::TokenAuth', function ($user_id) use ($app) {
    try {
        $id = $user_id;
        if ($user_id == "mine") {
            $auth = new Auth\Util();
            $accessToken = $auth->getAccessToken();
            $user = $accessToken->getUserName();
            $id = $accessToken->getUserId();
        }
        $result = array();
        // $result['usr_id'] = $user_id;
        $usr_model = new UsersModel();
        $usr_basic_info =  $usr_model->getBasicUserData($id);
        $result['user'] = $usr_basic_info;

        $app->success($result);

    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});



$app->post('/v1/users', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { // create
    try { // root only

        $request = $app->request();
        $attribs = array("login", "passwd", "firstname", "lastname", "email", "gender", "auth_mode");
        $user_data = array();
        foreach($attribs as $a) {
            $user_data[$a] = $request->params($a);
        }
        //$user = $request->params('login');
//        $pass = $request->params('passwd');

        // http://ildoc.hrz.uni-giessen.de/ildoc/Release_4_4_x_branch/html/de/da1/classilObjUser.html
        $user_data['profile_incomplete'] = false;

        $result = array();
        $usr_model = new UsersModel();
        $user_id = $usr_model->addUser($user_data);

        $status = true;

        if ($status == true) {
            $result['status'] = "User ".$user_id." created.";
            $result['data'] = array("id" => $user_id);
        }else {
            $result['status'] = "User could not be created!";
        }

        $app->success($result);

    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});


$app->put('/v1/users/:user_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($user_id) use ($app){ // update
    try {
        $request = $app->request();
        $attribs = array("login", "passwd", "firstname", "lastname", "email", "gender", "auth_mode");
        $user_data = array();
        foreach($attribs as $key) {
            $value = $request->params($key);
            $usr_model->updateUser($user_id, $key, $value);
        }

        $result = array();
        $result['status'] = 'success';
        $usr_basic_info =  $usr_model->getBasicUserData($user_id);
        $result['user'] = $usr_basic_info;

        $app->success($result);

    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});

$app->delete('/v1/users/:user_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($user_id) use ($app) {
    try {
        $result = array();
        $usr_model = new UsersModel();
        $status = $usr_model->deleteUser($user_id);

        if ($status == true) {
            $result['status'] = "User ".$user_id." deleted.";
        }else {
            $result['status'] = "User ".$user_id." not deleted!";
        }

        $app->success($result);

    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});
?>
