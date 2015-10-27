<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 * Constructor requires $app.
 */
class Util extends EndpointBase {
    // Allow to re-use status-strings
    const MSG_UC_DISABLED = 'User-credentials grant-type is disabled for this client.';
    const MSG_CC_DISABLED = 'Client-credentials grant-type is disabled for this client.';
    const MSG_AC_DISABLED = 'Authorization-code grant-type is disabled for this client.';
    const MSG_I_DISABLED = 'Implicit grant-type is disabled for this client.';

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
    public static function checkSession($user_id, $rtoken, $session_id) {
        $rtokenValid = false;
        $sessionValid = false;

        $sqlToken = Libs\RESTLib::safeSQL('
            SELECT * FROM il_request_token
            WHERE user_id = %d
            AND token = %s
            AND session_id = %s',
            $user_id,
            $rtoken,
            $session_id
        );
        $queryToken = self::getDB()->query($sqlToken);
        if (self::getDB()->numRows($queryToken) > 0)
            $rtokenValid = true;

        $sqlSession = Libs\RESTLib::safeSQL('
            SELECT * FROM usr_session
            WHERE user_id = %d
            AND session_id = %s',
            $user_id,
            $session_id
        );
        $querySession = self::getDB()->query($sqlSession);
        if ($row = self::getDB()->fetchAssoc($querySession))
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
    public static function renderWebsite($title, $file, $data) {
        // Build absolute-path (relative to document-root)
        $sub_dir = 'core/auth/views';
        $rel_path = Libs\RESTLib::getPluginDir();
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $scriptName = str_replace('\\', '/', $scriptName);
        $scriptName = ($scriptName == '/' ? '' : $scriptName);
        $abs_path = $scriptName.'/'.$rel_path.'/RESTController/'.$sub_dir;

        // Supply data to slim application
        self::getApp()->render($sub_dir.'/core.php', array(
            'tpl_path' => $abs_path,
            'tpl_title' => $title,
            'tpl_file' => $file,
            'tpl_data' => $data
        ));
    }


    /**
     *
     * NOTE: May throw TokenInvalid!
     */
    public static function getAccessToken() {
        // Fetch token from body GET/POST (json or plain)
        $request = self::getApp()->request();
        $tokenString = $request->params('token');

        // Fetch access_token from GET/POST (json or plain)
        if (is_null($tokenString))
            $tokenString = $request->params('access_token');

        // Fetch token from request header
        if (is_null($tokenString)) {
            // Fetch Authorization-Header
            $authHeader = $request->headers('Authorization');

            // Found Authorization header?
            if ($authHeader != null) {
                $a_auth = explode(' ', $authHeader);
                $tokenString = $a_auth[1];        // With "Bearer"-Prefix
                if ($tokenString == null)
                    $tokenString = $a_auth[0];    // Without "Bearer"-Prefix
            }
        }

        // Decode token (Throws Exception if token is invalid/missing)
        $accessToken = Token\Generic::fromMixed(self::tokenSettings('access'), $tokenString);

        // Store access token
        return $accessToken;
    }


    /**
     * Authentication via the ILIAS Auth mechanisms.
     * This method is used as backend for OAuth2.
     *
     * @param $username - ILIAS user-id
     * @param $password - ILIS user-password
     * @return bool - True if authentication was successfull, false otherwise
     */
    public static function authenticateViaIlias($username, $password) {
        Libs\RESTLib::initAccessHandling();

        $_POST['username'] = $username;
        $_POST['password'] = $password;

        require_once('Services/Authentication/classes/class.ilAuthUtils.php');
        \ilAuthUtils::_initAuth();

        global $ilAuth;
        $ilAuth->start();
        $checked_in = $ilAuth->getAuth();

        $ilAuth->logout();
        session_destroy();
        header_remove('Set-Cookie');

        return $checked_in;
    }
}
