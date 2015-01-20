<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// REST - Client Administration

$app->get('/clients', 'authenticateTokenOnly',  function () use ($app) {
    try {
        $env = $app->environment();
        $client_id = $env['client_id'];


        $authorizedUser = $env['user'];

        //$iliasAuth = & ilAuthLib::getInstance();
        //$iliasAuth->setUserContext($authorizedUser);

        $result = array();


        // $usr_model = new ilUsersModel();
        $ilRest = new ilRestLib();
        if (!$ilRest->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role

            $result['status'] = 'failed';
            $result['msg'] = "Access denied. Administrator permissions required.";
            $result['authuser'] = $authorizedUser;

        } else {

            $admin_model = new ilClientsModel();
            $data = $admin_model->getClients();
            $result['status'] = 'success';
            $result['clients'] = $data;
            $result['authuser'] = $authorizedUser;

        }

        $admin_model = new ilClientsModel();
        $data = $admin_model->getClients();
        $result['status'] = 'success';
        $result['clients'] = $data;
        $result['authuser'] = $authorizedUser;

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);

    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

$app->put('/clients/:id', 'authenticateTokenOnly',  function ($id) use ($app){ // update
    $env = $app->environment();

    $authorizedUser = $env['user'];
    $app->log->debug("slim request: ".$app->request->getPathInfo());
    $result = array();

    //$usr_model = new ilUsersModel();
    $ilRest = new ilRestLib();
    if (!$ilRest->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role
        $result['status'] = 'failed';
        $result['msg'] = "Access denied. Administrator permissions required.";
        $result['authuser'] = $authorizedUser;
    } else {
        $admin_model = new ilClientsModel();

        $a_Requests = $app->request->put();
        if (count($a_Requests) == 0) {
            $a_Requests = array();
            $reqdata = $app->request()->getBody(); // json
            $a_data = json_decode($reqdata, true);
            error_log("(Slim) Updating client...".print_r($a_data,true));
            //var_dump($a_data);
            $a_Requests['client_id'] = $a_data['data']['client_id'];
            $a_Requests['client_secret'] = $a_data['data']['client_secret'];
            $a_Requests['oauth_consent_message'] = $a_data['data']['oauth_consent_message'];
            $a_Requests['redirection_uri'] = $a_data['data']['redirection_uri'];
            $a_Requests['permissions'] = addslashes ($a_data['data']['permissions']);
            }

        foreach ($a_Requests as $key => $value) {
            //$result["x$key"] = $value;
            $admin_model->updateClient($id, $key, $value);
        }
        $result['status'] = 'success';

    }
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($result);

});

$app->post('/clients/', 'authenticateTokenOnly', function () use ($app){ // create
    try {
        $env = $app->environment();
        $authorizedUser = $env['user'];
        $result = array();


        error_log("(Slim) Creating client...");

        //$usr_model = new ilUsersModel();
        $ilRest = new ilRestLib();
        if (!$ilRest->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role
            $result['status'] = 'failed';
            $result['msg'] = "Access denied. Administrator permissions required.";
            $result['authuser'] = $authorizedUser;

        } else {

            $input_complete = true;
            $new_client_id = "";
            $new_client_secret = "";
            $new_client_oauth_consent_message = "";
            $new_client_permissions = "";
            $new_client_redirect_url = "";

            $reqBodyData = $app->request()->getBody(); // json
            if ($reqBodyData != "") {
                $requestData = json_decode($reqBodyData, true);
                $new_client_id = array_key_exists('client_id', $requestData) ? $requestData['client_id'] : null;
                $new_client_secret = array_key_exists('client_secret', $requestData) ? $requestData['client_secret'] : null;
                $new_client_oauth_consent_message= array_key_exists('oauth_consent_message', $requestData) ? $requestData['oauth_consent_message'] : null;
                $new_client_permissions = array_key_exists('permissions', $requestData) ? $requestData['permissions'] : null;
                $new_client_redirect_url = array_key_exists('redirection_uri', $requestData) ? $requestData['redirection_uri'] : null;
            } else {
                $request = $app->request();
                $new_client_id = $request->params('client_id'); // aka api_key
                $new_client_secret = $request->params('client_secret');
                $new_client_oauth_consent_message = $request->params('oauth_consent_message');
                $new_client_permissions = $request->params('permissions');
                $new_client_redirect_url = $request->params('redirection_uri');
            }

            if (is_null($new_client_id)) {
                $input_complete = false;
            } else {
                $input_complete = true;
            }


            if (is_null($new_client_secret)) {
                $new_client_secret = "";
            }

            if (is_null($new_client_oauth_consent_message)) {
                $new_client_oauth_consent_message = "";
            }

            if (is_null($new_client_permissions)) {
                $new_client_permissions = "";
            }

            if (is_null($new_client_redirect_url)) {
                $new_client_redirect_url = "";
            }

            if ($input_complete == false) {
                $result['status'] = 'failed';
                $result['msg'] = "Mandatory data is missing.";
            } else {
                $admin_model = new ilClientsModel();
                $new_id = $admin_model->createClient($new_client_id, $new_client_secret, $new_client_redirect_url, $new_client_oauth_consent_message, $new_client_permissions);
                //var_dump($data);
                $result['id'] = $new_id;
                $result['status'] = 'success';
            }

        }

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);
    } catch (Exception $e) {
        $app->response()->status(400);
        $app->response()->header('X-Status-Reason', $e->getMessage());
    }
});

// 'authenticate',
$app->delete('/clients/:id', 'authenticateTokenOnly',  function ($id) use ($app) {

    $request = $app->request();
    $env = $app->environment();

    $app = \Slim\Slim::getInstance();
    $env = $app->environment();
    $authorizedUser = $env['user'];

    $result = array();

    //$usr_model = new ilUsersModel();//ilRestLib();
    $ilRest = new ilRestLib();
    if (!$ilRest->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role

        $result['status'] = 'failed';
        $result['msg'] = "Access denied. Administrator permissions required.";
        $result['authuser'] = $authorizedUser;

    } else {
        $admin_model = new ilClientsModel();
        $status = $admin_model->deleteClient($id);

        if ($status >0 ) {
            $result['msg'] = "Client with internal db ".$id." deleted.";
        }else {
            $result['msg'] = "Client with internal db ".$id." not deleted!";
        }
        $result['status'] = 'success';
    }

    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($result);

});


$app->get('/routes', function () use ($app) {

    $env = $app->environment();

    $result = array();

    $routes = $app->router()->getNamedRoutes();

    foreach($routes as $route) {
        //echo $route->getName();
        //echo print_r($route->getHttpMethods(),true);
        $multiVerbs = $route->getHttpMethods(); //the "head" verb occurs as second verb for the "get" verb, which we omit
        $verb = $multiVerbs[0];
        $result[] = array("pattern"=>$route->getPattern(), "verb"=>$verb);
    }
    $r = array("routes"=>$result);
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($r);

});

?>