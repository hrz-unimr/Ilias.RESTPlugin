<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\experimental;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\core\clients as Clients;


// NOTE: The routes here have usually no access restrictions. They're therefore disabled by default and should only be enabled for testing/development purposes.
/*$app->group('/dev', function () use ($app) {

    $app->get('/checkip', function () use ($app) {

        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else{
            $ip=$_SERVER['REMOTE_ADDR'];
        }

        $result = array(
            'server' => print_r($_SERVER,true),
            'ip' => $ip
        );
        $app->success($result);
    });

    $app->get('/clientcheck', function () use ($app) {
        $model = new Clients\Clients();
        $data1 = $model->getAllowedUsersForApiKey('9065710a-16b9-4b4c-9230-f76dc72d2a2d');
        $data2 = $model->getClientCredentialsUser('9065710a-16b9-4b4c-9230-f76dc72d2a2d');

        $result = array(
            'allowed_users' => $data1,
            'cc_user' => $data2
        );
        $app->success($result);
    });

    // -------------------------------------------------------------------
    $app->get('/hello', '\RESTController\libs\OAuth2Middleware::TokenAuth', function () use ($app) {
        $msg = 'Hello @ '.time();
        $referer = $_SERVER['HTTP_REFERER'];
        $host = $_SERVER['HTTP_HOST'];

        $result = array(
            'msg' => $msg,
            'referer' => $referer,
            'host' => $host
        );

        $app->success($result);
    });


    $app->get('/roundtrip', function () use ($app) {
        $destiny_url = 'http://localhost/restplugin.php/experimental/hello';

        $curl = curl_init($destiny_url);
        //$curl_post_data = array(
        //    'user_id' => 42,
        //    'emailaddress' => 'lorna@example.com',
        //);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        $t_start = microtime();
        $curl_response = curl_exec($curl);
        $t_end = microtime();
        curl_close($curl);

        $result = array(
            'status' => 'success',
            'remote_response' => $curl_response,
            'rtt' => $t_end - $t_start
        );

        $app->success($result);
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
        $destination_service_url = 'http://localhost/restplugin.php/v1/files';
        $curl_post_data = array(
            'ref_id' => 99,
            'title' => 'Sent by ilias rest',
            'uploadfile' => '@'.$file_metadata['file']['realpath'].';filename='.$file_metadata['file']['name'].';type='.$file_metadata['file']['type']
        );

        $curl = curl_init($destination_service_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);

        $t_start = microtime();
        $curl_response = curl_exec($curl);
        $t_end = microtime();

        $result = array(
            'remote_response' => $curl_response,
            'rtt' => $t_end - $t_start
        );

        $app->success($result);
    });


    $app->get('/responsetest', function () use ($app) {
        $result = array(
            'time' => time(),
            'host' => $_SERVER['HTTP_HOST'],
            'referrer' => $_SERVER['HTTP_REFERER'],
            'mynumbers' => array(0.5, 0.3, 0.2, 0.3, 0.5),
            'time' => 0
        );
        $app->success($result);

        // Never reached, just for show
        $app->halt(42, $result);
    });

    // -------------------------------------------------------------------
    $app->get('/getPermaLink/:ref_id', function ($ref_id) use ($app) {
        $result = array();
        $result['permaLink'] = Libs\RESTLib::getPermanentLink($ref_id);
        $app->success($result);
    });

});*/

