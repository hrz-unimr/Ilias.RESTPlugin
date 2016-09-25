<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\users_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\core\auth as Auth;
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;

/**
 * (Admin) Retrieves a list of system users.
 */
$app->get('/v1/users', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
        $limit = 10;
        $offset = 0;

        $result = array();
        $usr_model = new UsersModel();

        $fields = array('login','email');
        $request = $app->request();
        $reqFields = $request->getParameter('fields');
        if (isset($reqFields)){
            $fields = explode(",",$reqFields);
        }
        if ($request->getParameter('limit')){
            $limit = $request->getParameter('limit');
        }
        if ($request->getParameter('offset')){
            $offset = $request->getParameter('offset');
        }
        $result['limit'] = $limit;
        $result['offset'] = $offset;
        $all_users = $usr_model->getAllUsers($fields); // TODO improve this!
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

/**
 * Search for users
 * Supported search criteria:
 *  - ldapext with extname: search for users that have authmode ldap and query (extname) matches with ext_account
 */
$app->get('/v1/search/user',RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
    $result = array();
    $request = $app->request();
    if ($request->getParameter('mode')) {
        $mode = $request->getParameter('mode');
    }

    if ($mode == "ldapext") {
        if ($request->getParameter('extname')) {
            $extname = $request->getParameter('extname');
            try {
                $usr_model = new UsersModel();
                $app->log->debug('Searching for user '.$extname.' with auth_mode ldap.');
                $userdata = $usr_model->findExtLdapUser($extname);
                //$app->log->debug('model response: '.print_r($userdata,true));
                $result['user'] = $userdata;
                $app->success($result);
            } catch (Libs\ReadFailed $e) {
                $app->halt(400, $e->getFormatedMessage());
            }
        } else {
            // no extname specified: retrieve ALL users with auth_mode 'ldap'
            try {
                $usr_model = new UsersModel();
                $app->log->debug('Searching for ALL users with  auth_mode ldap.');
                $userdata = $usr_model->findExtLdapUsers();
                $result['user'] = $userdata;
            } catch (Libs\ReadFailed $e) {
                $app->halt(400, $e->getFormatedMessage());
            }
        }
    }
    $app->success(array("msg"=>"Empty result."));
});

/**
 * Retrieves data of a user specified by its id.
 */
$app->get('/v1/users/:user_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($user_id) use ($app) {
        $id = $user_id;
        if ($user_id == "mine") {
            $accessToken = $app->request->getToken();
            $user = $accessToken->getUserName();
            $id = $accessToken->getUserId();
        }
        // $result['usr_id'] = $user_id;
        $usr_model = new UsersModel();
        $usr_basic_info =  $usr_model->getBasicUserData($id);

        $app->success($usr_basic_info);

});

/**
 * Creates a user entry.
 */
$app->post('/v1/users', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) { // create

        $request = $app->request();
        $attribs = array("login", "passwd", "firstname", "lastname", "email", "gender", "auth_mode");
        $user_data = array();
        foreach($attribs as $a) {
            $user_data[$a] = $request->getParameter($a);
        }
        // http://ildoc.hrz.uni-giessen.de/ildoc/Release_4_4_x_branch/html/de/da1/classilObjUser.html
        $user_data['profile_incomplete'] = false;

        $result = array();
        $usr_model = new UsersModel();
        $user_id = $usr_model->addUser($user_data);

        $result = array('id' => $user_id);
        $app->success($result);

});

/**
 * Modifies an existing user entry.
 * Note: it is important to provide all fields not just those which need to be changed. Otherwise
 * all fields except the login field will be overwritten with default values.
 */
$app->put('/v1/users/:user_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($user_id) use ($app){ // update
    try {
        $request = $app->request();
        $attribs = array("login", "passwd", "firstname", "lastname", "email", "gender", "auth_mode");
        $user_data = array();
        $usr_model = new UsersModel();
        foreach($attribs as $key) {
            $value = $request->getParameter($key);
            $usr_model->updateUser($user_id, $key, $value);
        }
        $usr_basic_info =  $usr_model->getBasicUserData($user_id);

        $app->success($usr_basic_info);

    } catch (LibExceptions\UpdateFailed $e) {
        $app->halt(400, $e->getFormatedMessage());
    }
});

/**
 * Deletes a user entry.
 */
$app->delete('/v1/users/:user_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($user_id) use ($app) {
        $result = array();

        Libs\RESTilias::loadIlUser();
        Libs\RESTilias::initAccessHandling();

        $usr_model = new UsersModel();
        $status = $usr_model->deleteUser($user_id);

        if ($status) {
            $app->success(array("msg"=>"User with id $user_id deleted."));
        } else {
            $app->halt(404, "Could not delete user " . $user_id . ".");
        }
});
