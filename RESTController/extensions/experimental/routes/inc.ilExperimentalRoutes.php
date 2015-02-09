<?php
/*
 * Prototypical implementation of some rest endpoints for development
 * and testing.
 */

$app->group('/dev', function () use ($app) {

    /**
     * Refresh-Token Part 1: extended token-endpoint: hier kann durch ein gültiges refresh-token ein bearer-token erzeugt werden. der code hier geht in jedem fall in den oauth2 token endpunkt ein.
     * TODO
    */
    $app->get('/reftoken', function () use ($app) {
        $env = $app->environment();
        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);

        $refresh_token = $request->getParam("refresh_token");

        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initAccessHandling();

        global $ilLog;
        $ilLog->write('Hello from REST Plugin - Experimental');
        $app->response()->header('Content-Type', 'application/json');

        $model = new ilOAuth2Model();
        $bearer_token = $model->getBearerTokenForRefreshToken($refresh_token);


        $response->setMessage("Refresh 2 Bearer.");
       // $response->addData("refresh_token",$refresh_token);
        $response->addData("bearerToken",$bearer_token['access_token']);
        $response->send();

    });

    /**
     * Refresh-Token Part 2.1: new refresh end-point ; erzeugt ein NEUES refresh-token für ein valides bearer token. der
     * zugang muss daher geschützt sein. teile des codes gehen entweder gemaess spec im oauth2 mechanismus ein oder die beantragung
     * von REFRESH tokens bleibt eine eigene route und der zugriff wird über api-key geregelt.
     * Status: DONE
     */
    $app->get('/refresh', 'authenticate', function () use ($app) {
        $env = $app->environment();
        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);
        $uid = ilRestLib::loginToUserId($env['user']);

        global $ilLog;
        $ilLog->write('Requesting new refresh token for user '.$uid);
        //ilRestLib::initDefaultRestGlobals();
        //ilRestLib::initAccessHandling();

        // Create new refresh token
        $bearerToken = $env['token'];
        $model = new ilOAuth2Model();
        $refreshToken = $model->getRefreshToken($bearerToken);


        $response->setMessage("Requesting new refresh token for user ".$uid.".");
        $response->setData("refresh-token", $refreshToken);
        $response->addData("maxint", PHP_INT_MAX);
        $response->addData("beareruser", $bearerToken['user']);
        $response->addData("api-key", $bearerToken['api_key']);
        $response->addData("ilias client: ", $env['client_id']);
        $response->send();
    });

    // -------------------------------------------------------------------
    $app->get('/hello', 'authenticateTokenOnly', function () use ($app) {

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

        $response = new ilRestResponse($app);
        $env = $app->environment();

        $response->addData('status',"success");
        $response->addData('time',time());
        $response->addData('host',$_SERVER['HTTP_HOST']);
        $response->addData('referrer', $_SERVER['HTTP_REFERER']);
        $somenumbers = array(0.5, 0.3, 0.2, 0.3, 0.5);
        $response->addData('mynumbers', $somenumbers);
        $response->setData('time',0);
        $response->addData('status',"full");

        $response->toJSON();
    });

});
