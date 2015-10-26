<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\core\clients\RESTClient as RESTClient;
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


    // Store token and fetch it only once per execution
    protected $accessToken;

    // Store requesting client model and fetch it only once per execution
    protected $client;

    /**
     * Checks if provided OAuth2 - client (aka api_key) does exist.
     *
     * @param  api_key
     * @return bool
     */
    public static function checkClient($api_key) {
        // Fetch client with given api-key (checks existance)
        $sql = Libs\RESTLib::safeSQL('SELECT id FROM ui_uihk_rest_keys WHERE api_key = %s', $api_key);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0)
            return true;
        return false;
    }


    /**
     * Checks if provided OAuth2 client credentials are valid.
     * Compare with http://tools.ietf.org/html/rfc6749#section-4.4 (client credentials grant type).
     *
     * @param int api_key
     * @param string api_secret
     * @return bool
     */
    public static function checkClientCredentials($api_key, $api_secret) {
        // Fetch client with given api-key (checks existance)
        $sql = Libs\RESTLib::safeSQL('SELECT id FROM ui_uihk_rest_keys WHERE api_key = %s AND api_secret = %s', $api_key, $api_secret);
        $query = self::$sqlDB->query($sql);
        if (self::$sqlDB->numRows($query) > 0)
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
    public static function checkScope($route, $operation, $api_key) {
        if (!$this->client)
            $this->client = new RESTClient($api_key);

        if ($this->client->hasAPIKey($api_key) == true)
            return $this->client->checkScope($route, $operation);

        return false;
    }

    /**
     * Checks if the requesting client is allowed to make this request by IP address.
     * @param $api_key
     * @return bool
     */
    public static function checkIPAccess($api_key) {
        if (!$this->client)
            $this->client = new RESTClient($api_key);

        if ($this->client->hasAPIKey($api_key) == true) {
            $request_ip=$_SERVER['REMOTE_ADDR'];

            self::$app->log->debug('Util: Checking IP address for access: '.$request_ip);
            return $this->client->checkIPAccess($request_ip);
        }
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
        $queryToken = self::$sqlDB->query($sqlToken);
        if (self::$sqlDB->numRows($queryToken) > 0)
            $rtokenValid = true;

        $sqlSession = Libs\RESTLib::safeSQL('
            SELECT * FROM usr_session
            WHERE user_id = %d
            AND session_id = %s',
            $user_id,
            $session_id
        );
        $querySession = self::$sqlDB->query($sqlSession);
        if ($row = self::$sqlDB->fetchAssoc($querySession))
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
        $rel_path = self::$plugin->getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'REST')->getDirectory();
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $scriptName = str_replace('\\', '/', $scriptName);
        $scriptName = ($scriptName == '/' ? '' : $scriptName);
        $abs_path = $scriptName.'/'.$rel_path.'/RESTController/'.$sub_dir;

        // Supply data to slim application
        self::$app->render($sub_dir.'/core.php', array(
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
    public static function getAccessToken($force = false) {
        // Return stored acces token
        if (!$this->accessToken || $force) {
            // Fetch token from body GET/POST (json or plain)
            $request = self::$app->request();
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
            $this->accessToken = $accessToken;
        }

        // return token
        return $this->accessToken;
    }
}
