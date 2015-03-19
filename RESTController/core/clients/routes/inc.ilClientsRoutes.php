<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// REST - Client / API-Key Administration
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//$app->get('/clients', 'authenticateTokenOnly',  function () use ($app) {
$app->get('/clients', function () use ($app) {
    try {
        $env = $app->environment();
        $client_id = $env['client_id'];

        $authorizedUser = $env['user'];
        $result = array();

        // check if authorized user has admin role
        $ilREST = new ilRESTLib();
        if (!$ilREST->isAdminByUsername($authorizedUser)) {  
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
        
        /*
        $admin_model = new ilClientsModel();
        $data = $admin_model->getClients();
        $result['status'] = 'success';
        $result['clients'] = $data;
        $result['authuser'] = $authorizedUser;
        */

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


    $request = new ilRESTRequest($app);
    $app->log->debug("Update data ".print_r($request->getRaw(),true));

    try {
        $aUpdateData = $request->getParam('data');
    } catch(Exception $e) {
        $aUpdateData = array();
    }
    $app->log->debug("Update Data ".print_r($aUpdateData,true));


    $ilREST = new ilRESTLib();
    if (!$ilREST->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role
        $result['status'] = 'failed';
        $result['msg'] = "Access denied. Administrator permissions required.";
        $result['authuser'] = $authorizedUser;
    } else {
        $admin_model = new ilClientsModel();


        $aUpdateData['permissions'] = addslashes ($aUpdateData['permissions']);

        if (isset($aUpdateData["oauth2_user_restriction_active"]) && $aUpdateData["oauth2_user_restriction_active"]==1) {
            if (isset($aUpdateData["access_user_csv"])) {
                $a_user_csv = explode(',', $aUpdateData["access_user_csv"]);
                $admin_model->fillApikeyUserMap($id, $a_user_csv);
            }
        }

        foreach ($aUpdateData as $key => $value) {
            if ($key != "access_user_csv") {
                $admin_model->updateClient($id, $key, $value);
            }
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
        $request = new ilRESTRequest($app);

        error_log("(Slim) Creating client...");

        $ilREST = new ilRESTLib();
        if (!$ilREST->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role
            $result['status'] = 'failed';
            $result['msg'] = "Access denied. Administrator permissions required.";
            $result['authuser'] = $authorizedUser;
        } else {

            $app->log->debug("Request data (Create Client)".print_r($request->getRaw(),true));

            try {
                $new_api_key = $request->getParam('api_key');
                $input_complete = true;
            } catch(Exception $e) {
                $new_api_key = "";
                $input_complete = false;
            }

            try {
                $new_api_secret = $request->getParam('api_secret');
            } catch(Exception $e) {
                $new_api_secret = "";
            }

            try {
                $new_client_oauth2_consent_message = $request->getParam('oauth2_consent_message');
            } catch(Exception $e) {
                $new_client_oauth2_consent_message = "";
            }

            try {
                $new_client_permissions = $request->getParam('permissions');                
            } catch(Exception $e) {
                $new_client_permissions = "";
            }

            try {
                $new_client_oauth2_redirect_url = $request->getParam('oauth2_redirection_uri');
            } catch(Exception $e) {
                $new_client_oauth2_redirect_url = "";
            }

            try {
                $oauth2_gt_client_active = $request->getParam('oauth2_gt_client_active');
            } catch(Exception $e) {
                $oauth2_gt_client_active = 0;
            }

            try {
                $oauth2_gt_client_user = $request->getParam('oauth2_gt_client_user');
            } catch(Exception $e) {
                $oauth2_gt_client_user = "";
            }

            try {
                $oauth2_gt_authcode_active = $request->getParam('oauth2_gt_authcode_active');
            } catch(Exception $e) {
                $oauth2_gt_authcode_active = 0;
            }

            try {
                $oauth2_gt_implicit_active = $request->getParam('oauth2_gt_implicit_active');
            } catch(Exception $e) {
                $oauth2_gt_implicit_active = 0;
            }

            try {
                $oauth2_gt_resourceowner_active = $request->getParam('oauth2_gt_resourceowner_active');
            } catch(Exception $e) {
                $oauth2_gt_resourceowner_active = 0;
            }

            try {
                $oauth2_user_restriction_active = $request->getParam('oauth2_user_restriction_active');
            } catch(Exception $e) {
                $oauth2_user_restriction_active = 0;
            }

            try {
                $oauth2_consent_message_active = $request->getParam('oauth2_consent_message_active');
            } catch(Exception $e) {
                $oauth2_consent_message_active = 0;
            }

            try {
                $oauth2_authcode_refresh_active = $request->getParam('oauth2_authcode_refresh_active');
            } catch(Exception $e) {
                $oauth2_authcode_refresh_active = 0;
            }

            try {
                $oauth2_resource_refresh_active = $request->getParam('oauth2_resource_refresh_active');
            } catch(Exception $e) {
                $oauth2_resource_refresh_active = 0;
            }




            try {
                $access_user_csv = $request->getParam('access_user_csv');
            } catch(Exception $e) {
                $access_user_csv = "";
            }

            if ($input_complete == false) {
                $result['status'] = 'failed';
                $result['msg'] = "Mandatory data is missing.";
            } else {
                $admin_model = new ilClientsModel();

                $new_id = $admin_model->createClient(
                    $new_api_key,
                    $new_api_secret,
                    $new_client_oauth2_redirect_url,
                    $new_client_oauth2_consent_message,
                    $oauth2_consent_message_active,
                    $new_client_permissions,
                    $oauth2_gt_client_active,
                    $oauth2_gt_authcode_active,
                    $oauth2_gt_implicit_active,
                    $oauth2_gt_resourceowner_active,
                    $oauth2_user_restriction_active,
                    $oauth2_gt_client_user,
                    $access_user_csv,$oauth2_authcode_refresh_active,$oauth2_resource_refresh_active
                );
                $app->log->debug('Result of createClient: '.$new_id);
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


$app->delete('/clients/:id', 'authenticateTokenOnly',  function ($id) use ($app) {

    $request = $app->request();
    $env = $app->environment();

    $app = \Slim\Slim::getInstance();
    $env = $app->environment();
    $authorizedUser = $env['user'];

    $result = array();

    //$usr_model = new ilUsersModel();//ilRESTLib();
    $ilREST = new ilRESTLib();
    if (!$ilREST->isAdminByUsername($authorizedUser)) {  // check if authorized user has admin role

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

$app->get('/rest/config', function () use ($app) {
    global $ilPluginAdmin;
    $ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");

    $app->redirect('../../' . $ilRESTPlugin->getDirectory() . '/apps/html5/admin/app/');
});

?>