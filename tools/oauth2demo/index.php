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

      $self = dirname($_SERVER['PHP_SELF']);

      $apiDir   = $ilias_url . "/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST";
      $authUrl  = $apiDir . "/api.php/v2/oauth2/authorize";
      $tokenUrl = $apiDir . "/api.php/v2/oauth2/token";

      $authGrantRedirect      = $self . "/endpoints/authcode_endpoint.php";
      $implicitGrantRedirect  = $self . "/endpoints/implicitgrant_endpoint.php";

      $loginUrl         = $authUrl . "?api_key=" . urlencode($api_key);
      $authGrantUrl     = $loginUrl . "&response_type=code&redirect_uri="  . urlencode($authGrantRedirect);
      $implicitGrantUrl = $loginUrl . "&response_type=token&redirect_uri=" . urlencode($implicitGrantRedirect);
      ?>
      <h3>Initiating one of the following OAuth2 Grant Mechanism via a GET Request:</h3>
      <ul>
        <li><a href="<?php echo $authGrantUrl; ?>">My ILIAS (via OAuth2 - Authorization Code)</a></li>
        <li><a href="<?php echo $implicitGrantUrl; ?>"> My ILIAS (via OAuth2 - Implicit Grant) </a></li>
      </ul>
      <h3>Initiating one of the following OAuth2 Grant Mechanism via a POST Request:</h3>
      <ul>
        <li>
          <form method="POST" action="<?php echo $authUrl; ?>">
            <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
            <input type="hidden" name="response_type" value="code" />
            <input type="hidden" name="redirect_uri" value="<?php echo $authGrantRedirect; ?>" />
            <input type="submit" value="Authorization Code Grant" />
          </form>
        </li>
        <li>
          <form method="POST" action="<?php echo $authUrl; ?>">
            <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
            <input type="hidden" name="response_type" value="token" />
            <input type="hidden" name="redirect_uri" value="<?php echo $implicitGrantRedirect; ?>" />
            <input type="submit" value="Implicit Grant" />
          </form>
        </li>
        <li>
          <form method="POST" action="<?php echo $tokenUrl; ?>">
            <input type="hidden" name="grant_type" value="client_credentials" />
            <input type="hidden" name="scope" value="" />
            <input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
            <input type="hidden" name="api_secret" value="<?php echo $api_secret; ?>" />
            <input type="submit" value="Clients Credentials" />
          </form>
        </li>
        <li>
          <form method="POST" action="<?php echo $tokenUrl;?>">
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
