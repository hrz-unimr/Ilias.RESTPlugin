<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\clients\Clients as Clients;


/**
 *
 * Constructor requires $app & $sqlDB.
 */
class OAuth2Misc extends Libs\RESTModel {
    /**
     *
     */
    public function tokenInfo($request) {
        //
        $token = Libs\AuthMiddleware::getToken($this->app);
        if (!Libs\TokenLib::tokenValid($token))
            throw new Exceptions\TokenInvalid("");
        if (Libs\TokenLib::tokenExpired($token))
            throw new Exceptions\TokenExpired("");

        //
        return array(
            'api_key' => $token['api_key'],
            'user' =>  $token['user'],
            'type' =>  $token['type'],
            'expires_in' => Libs\TokenLib::getRemainingTime($token),
            'scope' =>  $token['scope']
        );

    }
    /**
     * Further OAuth2 routines:
     * Allows for exchanging an ilias session to a bearer token.
     * This is used for administration purposes.
     * @param $app
     */
    public function rToken2Bearer($request) {
        $result = array();
        $user_id = '';
        $rtoken = '';
        $session_id = '';
        $api_key = '';

        if (count($request->post()) == 0) {
            $a_data = array();
            $reqdata = $app->request()->getBody(); // json
            $a_data = json_decode($reqdata, true);
            //var_dump($a_data);
            $user_id = $a_data['user_id'];
            $rtoken = $a_data['rtoken'];
            $session_id = $a_data['session_id'];
            $api_key = $a_data['api_key'];
        } else {
            $user_id = $request->getParam('user_id');
            $rtoken = $request->getParam('rtoken');
            $session_id = $request->getParam('session_id');
            $api_key = $request->getParam('api_key');
        }

        $isAuth = Libs\AuthLib::authFromIlias($user_id, $rtoken, $session_id);

        if ($isAuth == false) {
            //$app->response()->status(400);
            $result['status'] = 'error';
            $result['error'] = 'Invalid token.';
            $result['user_id']=$user_id;
            $result['rtoken']=$rtoken;
            $result['session_id']=$session_id;

        }
        else {
            $user = Libs\RESTLib::userIdtoLogin($user_id);
            $access_token = Libs\TokenLib::generateBearerToken($user, $api_key);
            $result['status'] = 'success';
            $result['user'] = $user;
            $result['token'] = $access_token;
        }
    }


    /**
     * Simplifies rendering output by allowing to reuse common code.
     * Core.php which includes many preset JavaScript and CSS libraries will always
     * be used as a base template and $file will be included into its body.
     *
     * @param $title - Sets the pages <title> tag
     * @param $file - This file will be included inside <body></body> tags
     * @param $data - Optional data (may be an array) that is passed to the template
     */
    public function render($title, $file, $data) {
        // Build absolute-path (relative to document-root)
        $sub_dir = 'core/auth/views';
        $rel_path = $this->plugin->getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'REST')->getDirectory();
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $scriptName = str_replace('\\', '/', $scriptName);
        $scriptName = ($scriptName == '/' ? '' : $scriptName);
        $abs_path = $scriptName.'/'.$rel_path.'/RESTController/'.$sub_dir;

        // Supply data to slim application
        $this->app->render($sub_dir.'/core.php', array(
            'tpl_path' => $abs_path,
            'tpl_title' => $title,
            'tpl_file' => $file,
            'tpl_data' => $data
        ));
    }
}
