<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/dev', function () use ($app) {


    /**
     * Aim: Utilize the default ILIAS authentication mechanism instead of
     * only database authentication.
     *
     * TODO: This code must be included in the OAuth2 Core
     */
    $app->post('/login', function () use ($app) {

        $request = $app->request();

        $result = array();

        $user = $request->params('username');
        $pass = $request->params('password');


        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initAccessHandling();

        global $ilLog;
        $ilLog->write('Hello from REST Plugin - Experimental');

        $model = new ilExperimentalModel();
        $model::initSettings();
        // see initUser
        $_POST['username'] = $user;
        $_POST['password'] = $pass;

        // add code 1
        if (!is_object($GLOBALS["ilPluginAdmin"]))
       {
           ilRestLib::initGlobal("ilPluginAdmin", "ilPluginAdmin",
               "./Services/Component/classes/class.ilPluginAdmin.php");
       }
       // add code 2
       include_once "Services/Authentication/classes/class.ilSession.php";
       include_once "Services/Authentication/classes/class.ilSessionControl.php";

       require_once "Auth/Auth.php";
       require_once "./Services/AuthShibboleth/classes/class.ilShibboleth.php";
       include_once("./Services/Authentication/classes/class.ilAuthUtils.php");
        ilAuthUtils::_initAuth();
        global $ilAuth;

        $ilAuth->start();
        $checked_in = $ilAuth->getAuth();

        if ($checked_in == true)
        {
            $result['msg'] = "User logged in successfully.";
        } else
        {
            $result['msg'] = "User could not be logged in.";
        }
        //echo "sessid: ".session_name().' // '.session_id();
        $ilAuth->logout();

        session_destroy();
        header_remove('Set-Cookie');

        //$result['getdata'] = $user.':'.$pass;
        $result['auth'] = session_id(); // should be empty!
        echo json_encode($result);
    });



    /**
     * Aim: Utilize the default ILIAS authentication mechanism instead of
     * only database authentication.
     *
     * TODO: This code must be included in the OAuth2 Core
     */
    $app->post('/login2', function () use ($app) {

        $request = $app->request();

        $result = array();

        $user = $request->params('username');
        $pass = $request->params('password');

        $iliasAuth = & ilAuthLib::getInstance();
        $checked_in = $iliasAuth->authenticateViaIlias($user,$pass);


        global $ilLog;
        $ilLog->write('Hello from REST Plugin - Experimental');


        if ($checked_in == true)
        {
            $result['msg'] = "User logged in successfully.";
        } else
        {
            $result['msg'] = "User could not be logged in.";
        }


        //$result['getdata'] = $user.':'.$pass;
        $result['auth'] = session_id(); // should be empty!
        echo json_encode($result);
    });

    // -------------------------------------------------------------------
    $app->get('/hello', function () use ($app) {

        $app = \Slim\Slim::getInstance();

        $result = array();
        $result['status'] = 'success';
        $msg = 'Hello @ '.time();
        $referer = $_SERVER['HTTP_REFERER'];
        $host = $_SERVER['HTTP_HOST'];
        $result['msg'] = $msg;
        $result['referer'] = $referer;
        $result['host'] = $host;


        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);


    });

    $app->get('/roundtrip', function () use ($app) {


        $destiny_url = 'http://localhost/restplugin.php/experimental/hello';

        $curl = curl_init($destiny_url);
        /*$curl_post_data = array(
            "user_id" => 42,
            "emailaddress" => 'lorna@example.com',
        );*/

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        $t_start = microtime();
        $curl_response = curl_exec($curl);
        $t_end = microtime();
        curl_close($curl);

        $result = array();
        $result['status'] = 'success';
        $result['remote_response'] = $curl_response;
        $result['rtt'] = $t_end - $t_start;

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);


    });

    $app->get('/transportfile', function () use ($app) {


        // Get file info
        $self_service_url = 'http://localhost/restplugin.php/v1/files/96?meta_data=1';
        $curl = curl_init($self_service_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $file_metadata_json = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $file_metadata = json_decode($file_metadata_json,true);
        $file_metadata = json_decode($file_metadata_json,true);


        $destination_service_url = 'http://localhost/restplugin.php/v1/files';
        $curl_post_data = array(
            "ref_id" => 99,
            "title" => 'Sent by ilias rest',
            "uploadfile" => '@'.$file_metadata['file']['realpath'].';filename='.$file_metadata['file']['name'].';type='.$file_metadata['file']['type']
        );


        $curl = curl_init($destination_service_url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);



        $t_start = microtime();
        $curl_response = curl_exec($curl);
        $t_end = microtime();

        $result = array();
        $result['status'] = 'success';
        $result['remote_response'] = $curl_response;
        $result['rtt'] = $t_end - $t_start;

        $app->response()->header('Content-Type', 'application/json');
        echo json_encode($result);

    });

    $app->get('/responsetest', function () use ($app) {

        $response = new RestResponse();
        $env = $app->environment();

        $response->addData('status',"success");
        $response->addData('time',time());
        $response->addData('host',$_SERVER['HTTP_HOST']);
        $response->addData('referrer', $_SERVER['HTTP_REFERER']);
        $somenumbers = array(0.5, 0.3, 0.2, 0.3, 0.5);
        $response->addData('mynumbers', $somenumbers);
        $response->setData('time',0);
        $response->addData('status',"full");

        $app->response()->header('Content-Type', 'application/json');
        echo $response->getJSON();


    });

});
