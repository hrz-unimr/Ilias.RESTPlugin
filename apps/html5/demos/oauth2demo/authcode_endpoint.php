<?php
/**
 * "My ILIAS (via OAuth2 - Authorization Code)" (start.php) will use this as 
 * redirect after ...restplugin.php/v1/oauth2/auth has generated a (temporary).
 * Authentification code, which can be used by the client (this file) to generate a
 * token via restplugin.php/v1/oauth2/token (see $postBody for POST body)
 */

// Include settings
require_once('config.ini.php');

// Exchange OAuth 2 authorization code for bearer token
if (isset($_GET['code'])){ 
    if (isset($_GET['make_curl_call'])) {
        // Protocol used for curl call
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $protocol = 'http://';
        } else {
            $protocol = 'https://';
        }

        // Endpoint (url) used for curl call
        $restUrl =  $protocol . 'localhost' .$subFolder. "/restplugin.php/v1/oauth2/token";

        // Redirection URL (but into body) 
        $redirect_uri = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        if ($_SERVER["SERVER_PORT"] != "80") {
            $redirect_uri = $protocol . $_SERVER['SERVER_NAME'] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER['PHP_SELF'];
        }

        // Build the body for curl call
        $postBody = array(
            'grant_type'=> 'authorization_code',
            'code' => $_GET['code'],
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'redirect_uri' => $redirect_uri
        );

        // Construct and execute curl (REST) POST-request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $restUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        curl_close($ch);
        
        // Convert to array
        $decoded = json_decode($curl_response, true);
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>OAuth2: Authorization Code Endpoint</title>
            </head>
            <body>
                <h3>OAuth2 Token via Authorization Code Workflow Retrieved!</h3>
                <pre>Bearer-Token: <?php echo $decoded["access_token"]; ?></pre>
                <h4> The client can continue now making further API requests with the obtained bearer token.</h4>
                <a href='start.php'>Back to the demo</a>
            </body>
        </html>
        <?php
    }
    else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <title>OAuth2: Authorization Code Endpoint</title>
            </head>
            <body>
                <h3>The Server has authenticated your request and generated an authentication code that can be traded for a bearer token.</h3>
                <pre>Authorization Code: <?php echo $_GET['code']; ?></pre>
                <a href='<?php echo $_SERVER['REQUEST_URI']; ?>&make_curl_call=1'>Trade authentication code for bearer token</a></br></br>
                <a href='start.php'>Back to the demo</a>
            </body>
        </html>
        <?php
    }
}
