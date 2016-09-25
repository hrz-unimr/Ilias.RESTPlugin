<!DOCTYPE html>
<html lang="en">
  <head>
    <title>OAuth2: Authorization Code Endpoint</title>
  </head>
  <body>
<?php
/**
 * "My ILIAS (via OAuth2 - Authorization Code)" (start.php) will use this as
 * redirect after .../v1/oauth2/auth has generated a (temporary).
 * Authentification code, which can be used by the client (this file) to generate a
 * token via /v1/oauth2/token (see $postBody for POST body)
 */

// Include settings
require_once('../config.ini.php');

// Exchange OAuth 2 authorization code for bearer token
if (isset($_GET['code'])){
  if (isset($_GET['make_curl_call'])) {
    // Protocol used for curl call
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
      $protocol = 'http://';
    } else {
      $protocol = 'https://';
    }

    // Redirection URL (but into body)
    $redirect_uri = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    if ($_SERVER["SERVER_PORT"] != "80") {
      $redirect_uri = $protocol . $_SERVER['SERVER_NAME'] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER['PHP_SELF'];
    }

    // Build the body for curl call
    $post = array(
      'grant_type' => 'authorization_code',
      'code' => $_GET['code'],
      'api_key' => $api_key,
      'api_secret' => $api_secret,
      'redirect_uri' => $redirect_uri
    );

    // Endpoint (url) used for curl call
    $url =  $subFolder. "/v2/oauth2/token";

    //
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result       = curl_exec($ch);
    $header_size  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header       = substr($result, 0, $header_size);
    $body         = substr($result, $header_size);
    curl_close($ch);

    // Convert to array
    $decoded = json_decode($body, true);

    ?>
    <h3>OAuth2 Token via Authorization Code Workflow Retrieved!</h3>
    <pre>Access-Token: <?php echo (isset($decoded["access_token"]))   ? $decoded["access_token"]  : "[ No Data ]"; ?></pre>
    <pre>Refresh-Token: <?php echo (isset($decoded["refresh_token"])) ? $decoded["refresh_token"] : "[ No Data ]"; ?></pre>
    <h4> The client can continue now making further API requests with the obtained bearer token.</h4>
    <?php
  }
  else {
    ?>
    <h3>The Server has authenticated your request and generated an authentication code that can be traded for a bearer token.</h3>
    <pre>Authorization Code: <?php echo $_GET['code']; ?></pre>
    <a href='<?php echo $_SERVER['REQUEST_URI']; ?>&make_curl_call=1'>Trade authentication code for bearer token</a><br><br>
    <?php
  }
}
?>
  <a href='../'>Back to the demo</a>
  </body>
</html>
