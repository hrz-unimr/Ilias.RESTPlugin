<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\users_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\auth as Auth;


$app->get('/v1/users', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
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
        $result['limit'] = $limit;
        $result['offset'] = $offset;
        $all_users = $usr_model->getAllUsers($fields);
        $totalCount = count($all_users);
        $result['totalCount'] = $totalCount;
        // TODO: Sanity check on $offset parameter

        $result['users'] = array();
        for ($i = $offset; $i<min($totalCount, $offset+$limit); $i++) {
            $current_user = array('user'=>$all_users[$i]);
            $result['users'][] = $current_user;
        }

        $app->success($result);

});


$app->get('/v1/users/:user_id', '\RESTController\libs\OAuth2Middleware::TokenAuth', function ($user_id) use ($app) {
        $id = $user_id;
        if ($user_id == "mine") {
            $auth = new Auth\Util();
            $accessToken = $auth->getAccessToken();
            $user = $accessToken->getUserName();
            $id = $accessToken->getUserId();
        }
        // $result['usr_id'] = $user_id;
        $usr_model = new UsersModel();
        $usr_basic_info =  $usr_model->getBasicUserData($id);

        $app->success($usr_basic_info);

});


$app->post('/v1/users', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) { // create

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

        $app->success($user_id);

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
        $usr_basic_info =  $usr_model->getBasicUserData($user_id);

        $app->success($usr_basic_info);

    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});


$app->delete('/v1/users/:user_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function ($user_id) use ($app) {
    try {
        $result = array();
        $usr_model = new UsersModel();
        $status = $usr_model->deleteUser($user_id);

        if ($status)
            $app->success();
        else
            $app->halt(500, "Coulld not delete user ".$user_id.".");
    } catch (\Exception $e) {
        $app->halt(400, $e->getMessage());
    }
});
