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
class Util extends Libs\RESTModel {
    // Allow to re-use status-strings
    const MSG_UC_DISABLED = 'User-credentials grant-type is disabled for this client.';
    const MSG_CC_DISABLED = 'Client-credentials grant-type is disabled for this client.';
    const MSG_AC_DISABLED = 'Authorization-code grant-type is disabled for this client.';
    const MSG_I_DISABLED = 'Implicit grant-type is disabled for this client.';


    /**
     * Checks if provided OAuth2 - client (aka api_key) does exist.
     *
     * @param  api_key
     * @return bool
     */
    static public function checkClient($api_key) {
        // Fetch client with given api-key (checks existance)
        $query = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s"', $api_key);
        $set = $this->sqlDB->query($query);
        if ($this->sqlDB->numRows($set) > 0)
            return true;
        return false;
    }


    /*
     * Checks if provided OAuth2 client credentials are valid.
     * Compare with http://tools.ietf.org/html/rfc6749#section-4.4 (client credentials grant type).
     *
     * @param int api_key
     * @param string api_secret
     * @return bool
     */
    static public function checkClientCredentials($api_key, $api_secret) {
        // Fetch client with given api-key (checks existance)
        $query = sprintf('SELECT id FROM ui_uihk_rest_keys WHERE api_key = "%s" AND api_secret = "%s"', $api_key, $api_secret);
        $set = $this->sqlDB->query($query);
        if ($this->sqlDB->numRows($set) > 0)
            return true;
        return false;
    }


    /**
     * Checks if a rest client is allowed to enter a route (aka REST endpoint).
     *
     * @param route
     * @param operation
     * @param api_key
     * @return bool
     */
    static public function checkScope($route, $operation, $api_key) {
        $operation = strtoupper($operation);
        $query = sprintf('
            SELECT pattern, verb
            FROM ui_uihk_rest_perm
            JOIN ui_uihk_rest_keys
            ON ui_uihk_rest_keys.api_key = "%s"
            AND ui_uihk_rest_keys.id = ui_uihk_rest_perm.api_id
            AND ui_uihk_rest_perm.pattern = "%s"
            AND ui_uihk_rest_perm.verb = "%s"',
            $api_key,
            $route,
            $operation
        );
        $set = $this->sqlDB->query($query);
        if ($this->sqlDB->fetchAssoc($set))
            return true;
        return false;
    }

    /**
     * Checks if an ILIAS session is valid and belongs to a particular user.
     * And furthermore if rToken is valid.
     *
     * @see Services/UICore/classes/class.ilCtrl.php
     * @see Services/Authentication/classes/ilSessionControl.php
     *
     * @param $user_id
     * @param $rtoken
     * @param $session_id
     * @return bool
     */
    static public function checkSessionData($user_id, $rtoken, $session_id) {
        $rtokenValid = false;
        $sessionValid = false;

        $query = sprintf('
            SELECT * FROM il_request_token
            WHERE user_id = %d
            AND token = "%s"
            AND session_id = "%s"',
            $user_id,
            $rtoken,
            $session_id
        );
        $set = $this->sqlDB->query($query);
        if ($this->sqlDB->numRows($set) > 0)
            $rtokenValid = true;

        $query = sprintf('
            SELECT * FROM usr_session
            WHERE user_id = %d
            AND session_id = "%s"',
            $user_id,
            $session_id
        );
        $set = $this->sqlDB->query($query);
        if ($row = $this->sqlDB->fetchAssoc($set))
            if ($row['expires'] > time())
                $sessionValid = true;

        return $rtokenValid && $sessionValid;
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
    public function renderWebsite($title, $file, $data) {
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
