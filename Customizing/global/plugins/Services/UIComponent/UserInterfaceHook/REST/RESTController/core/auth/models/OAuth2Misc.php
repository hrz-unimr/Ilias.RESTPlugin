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


/**
 *
 * Constructor requires $app.
 */
class OAuth2Misc extends Libs\RESTModel {
    // Allow to re-use status-strings
    const MSG_RTOKEN_AUTH_FAILED = 'Failed to authenticate.';


    /**
     *
     */
    public function tokenInfo($request) {
        // Check token
        $token = Libs\AuthMiddleware::getToken($this->app);
        if (Libs\TokenLib::tokenExpired($token))
            throw new Exceptions\TokenInvalid(Libs\TokenLib::MSG_EXPIRED);

        // Generate info for (valid) token
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
    public function rToken2Bearer($api_key, $user_id, $rtoken, $session_id) {
        // Check login-data
        if (!Libs\AuthLib::authFromIlias($user_id, $rtoken, $session_id))
            throw new Exceptions\TokenInvalid(MSG_RTOKEN_AUTH_FAILED);

        // Generate token for user (via given api-key)
        $user = Libs\RESTLib::userIdtoLogin($user_id);
        $access_token = Libs\TokenLib::generateBearerToken($user, $api_key);
        return array(
            'user' => $user,
            'token' => $access_token
        );
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
