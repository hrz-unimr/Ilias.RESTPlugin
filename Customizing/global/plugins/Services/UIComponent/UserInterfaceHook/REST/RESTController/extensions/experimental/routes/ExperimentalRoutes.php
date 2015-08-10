<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\experimental;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\core\clients as Clients;


$app->group('/dev', function () use ($app) {
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


    /**
     * Refresh-Token Part 1: extended token-endpoint: hier kann durch ein gültiges refresh-token ein bearer-token erzeugt werden. der code hier geht in jedem fall in den oauth2 token endpunkt ein.
     * Status: DONE
    */
    $app->get('/reftoken', function () use ($app) {
        $request = $app->request();
        $refresh_token = $request->params('refresh_token');

        Libs\RESTLib::initAccessHandling();

        global $ilLog;
        $ilLog->write('Hello from REST Plugin - Experimental');
        $app->response()->header('Content-Type', 'application/json');

        $model = new Auth\TokenEndpoint();
        $refreshToken = Token\Refresh::fromMixed($model->tokenSettings(), $refresh_token);
        $bearer_token = $model->refresh2Access($refreshToken);

        $result = array('token' => $bearer_token->getEntry('access_token'));
        $app->success($result);
    });


    /**
     * Refresh-Token Part 2.1: new refresh end-point ; erzeugt ein NEUES refresh-token für ein valides bearer token. der
     * zugang muss daher geschützt sein. teile des codes gehen entweder gemaess spec im oauth2 mechanismus ein oder die beantragung
     * von REFRESH tokens bleibt eine eigene route und der zugriff wird über api-key geregelt.
     * Status: DONE
     */
    $app->get('/refresh', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth', function () use ($app) {
        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $user = $accessToken->getUserName();
        $uid = $accessToken->getUserId();

        global $ilLog;
        $ilLog->write('Requesting new refresh token for user '.$uid);
        //RESTLib::initAccessHandling();

        // Create new refresh token
        $model = new Auth\RefreshEndpoint();
        $refreshToken = $model->getToken($accessToken);

        $result = array(
            'refresh-token' => $refreshToken->getTokenString(),
            'maxint' => PHP_INT_MAX,
            'beareruser' => $accessToken->getUserName(),
            'api-key' => $accessToken->getApiKey(),
            'ilias client' => $app->environment()['client_id']
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
        /*$curl_post_data = array(
            'user_id' => 42,
            'emailaddress' => 'lorna@example.com',
        );*/

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
    $app->get('/getLink/:ref_id', function ($ref_id) use ($app) {
        /*$result = array(
            'time' => time(),
            'host' => $_SERVER['HTTP_HOST'],
            'referrer' => $_SERVER['HTTP_REFERER'],
            'mynumbers' => array(0.5, 0.3, 0.2, 0.3, 0.5),
            'time' => 0
        );*/
        $result = array();
        //require_once("./Services/Link/classes/class.ilLink.php");
        //$destination = \ilLink::_getStaticLink($ref_id);
        //$result['link'] = $destination;

        //ilRESTLib::getTypeOfObject
        $obj_id = Libs\RESTLib::getObjIdFromRef($ref_id);
        $type = Libs\RESTLib::getTypeOfObject($obj_id);
        $result['type'] = $type;
        $result['baseURL'] = Libs\RESTLib::getBaseUrl();
        $app->success($result);
    });

});
