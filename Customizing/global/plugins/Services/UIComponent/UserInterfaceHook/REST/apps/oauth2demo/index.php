<!DOCTYPE html>
<html lang="en">
  <head>
    <title>ILIAS REST Plugin - OAuth2 Demo</title>
  </head>
  <body>
    <?php
    if (file_exists('config.ini.php')) {
      // Include settings
      require_once('config.ini.php');

      // Generate GET/POST URLs
      if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $protocol = 'http://';
      } else {
        $protocol = 'https://';
      }
      $base_url = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']);
      if ($_SERVER["SERVER_PORT"] != "80") {
        $base_url = $protocol . $_SERVER['SERVER_NAME'] . ":" . $_SERVER["SERVER_PORT"] . dirname($_SERVER['PHP_SELF']);
      }
      $loginUrl = $subFolder. "/v2/oauth2/authorize?api_key=".urlencode($api_key);

      // This will be the redirect targets for generating bearer tokens via GET (POST contains this info in the header)
      $authGrantUrl = $loginUrl."&redirect_uri=".urlencode($base_url."/endpoints/authcode_endpoint.php")."&response_type=code";
      $implicitGrantUrl = $loginUrl."&redirect_uri=".urlencode($base_url."/endpoints/implicitgrant_endpoint.php")."&response_type=token";
      ?>
      <h3>Initiating one of the following OAuth2 Grant Mechanism via a GET Request:</h3>
      <ul>
        <li><a href="<?php echo $authGrantUrl; ?>">My ILIAS (via OAuth2 - Authorization Code)</a></li>
        <li><a href="<?php echo $implicitGrantUrl; ?>"> My ILIAS (via OAuth2 - Implicit Grant) </a></li>
      </ul>
      <h3>Initiating one of the following OAuth2 Grant Mechanism via a POST Request:</h3>
      <ul>
        <li>
          <form method="POST" action="<?php echo $subFolder;?>/v2/oauth2/authorize">
            <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
            <input type="hidden" name="response_type" value="code" />
            <input type="hidden" name="redirect_uri" value="<?php echo $base_url."/endpoints/authcode_endpoint.php";?>" />
            <input type="submit" value="Authorization Code Grant" />
          </form>
        </li>
        <li>
          <form method="POST" action="<?php echo $subFolder;?>/v2/oauth2/authorize">
            <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
            <input type="hidden" name="response_type" value="token" />
            <input type="hidden" name="redirect_uri" value="<?php echo $base_url."/endpoints/implicitgrant_endpoint.php"; ?>" />
            <input type="submit" value="Implicit Grant" />
          </form>
        </li>
        <li>
          <form method="POST" action="<?php echo $subFolder;?>/v2/oauth2/token">
            <input type="hidden" name="grant_type" value="client_credentials" />
            <input type="hidden" name="scope" value="" />
            <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
            <input type="hidden" name="api_secret" value="<?php echo $api_secret; ?>" />
            <input type="submit" value="Clients Credentials" />
          </form>
        </li>
        <li>
          <form method="POST" action="<?php echo $subFolder;?>/v2/oauth2/token">
            <div>
              <input type="hidden" name="grant_type" value="password" />
              <input type="hidden" name="scope" value="" />
              <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
              <label>Username:</label> <input type="text" name="username" /><br>
              <label>Password:</label> <input type="password" name="password" /><br>
              <input type="submit" value="Resource Owner Password Credentials Grant" />
            </div>
          </form>
        </li>
      </ul>
      <?php
    } else {
      ?>
      <p style="color:red;">Note: it is necessary to adapt the file "config.ini.php"! There you need to specify a valid REST API-Key and API-Secret.</p>
      <?php
    }
    ?>
  </body>
</html>
