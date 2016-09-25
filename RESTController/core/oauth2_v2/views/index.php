<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */


// Needs to be called with parameters...
if (!isset($parameters) || !isset($parameters['api_key']) || !isset($parameters['response_type'])
|| !isset($viewURL)
|| !isset($client)
|| !isset($endpoint))
  die;

// Use given parameters or default input
$username       = (isset($parameters['username']))        ? $parameters['username']         : 'Benutzter';
$password       = (isset($parameters['password']))        ? $parameters['password']         : 'Passwort';
$responseType   = (isset($parameters['response_type']))   ? $parameters['response_type']    : '';
$redirectUri    = (isset($parameters['redirect_uri']))    ? $parameters['redirect_uri']     : '';
$apiSecret      = (isset($parameters['api_secret']))      ? $parameters['api_secret']       : '';
$apiKey         = (isset($parameters['api_key']))         ? $parameters['api_key']          : '';
$scope          = (isset($parameters['scope']))           ? $parameters['scope']            : '';
$state          = (isset($parameters['state']))           ? $parameters['state']            : '';
$consentMessage = (isset($parameters['consent_message'])) ? $parameters['consent_message']  : '';


?><!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8">
  <title>ILIAS oAuth2 Anmeldung - <?php echo $client; ?></title>

  <link rel="stylesheet" href="<?php echo $viewURL; ?>css/jquery-ui.min.css">
  <link rel="stylesheet" href="<?php echo $viewURL; ?>css/style.css">
</head>

<body>
  <div class="card">
    <img class="logo" src="<?php echo $viewURL; ?>img/logo.png">

    <h1>Anmeldung</h1>
    <h2>Anwendungs-Zugriff</h2><br>

    <?php
    if (!isset($parameters['username']) || !isset($parameters['password']) || isset($parameters['exception'])) {
      if (isset($parameters['exception'])) {
        ?>
        <div class='help'><?php echo $parameters['exception']; ?></div>
        <?php
      }
      ?>
      <form action="<?php echo $endpoint; ?>" method="POST">
        <input type="hidden"   name="response_type" value="<?php echo $responseType; ?>" />
        <input type="hidden"   name="redirect_uri"  value="<?php echo $redirectUri; ?>" />
        <input type="hidden"   name="api_secret"    value="<?php echo $apiSecret; ?>" />
        <input type="hidden"   name="api_key"       value="<?php echo $apiKey; ?>" />
        <input type="hidden"   name="scope"         value="<?php echo $scope; ?>" />
        <input type="hidden"   name="state"         value="<?php echo $state; ?>" />

        <input type="text"     name="username"      placeholder="<?php echo $username; ?>">
        <input type="password" name="password"      placeholder="<?php echo $password; ?>">
        <input type="submit"   name="login"         value="Anmelden">
      </form>
      <?php
    }
    else {
      ?>
      <form action="<?php echo $endpoint; ?>" method="POST">
        <input type="hidden"  name="response_type" value="<?php echo $responseType; ?>" />
        <input type="hidden"  name="redirect_uri"  value="<?php echo $redirectUri; ?>" />
        <input type="hidden"  name="api_secret"    value="<?php echo $apiSecret; ?>" />
        <input type="hidden"  name="api_key"       value="<?php echo $apiKey; ?>" />
        <input type="hidden"  name="scope"         value="<?php echo $scope; ?>" />
        <input type="hidden"  name="state"         value="<?php echo $state; ?>" />

        <input type="hidden"  name="username"      value="<?php echo $username; ?>" />
        <input type="hidden"  name="password"      value="<?php echo $password; ?>" />

        <ul class="list">
          <li><label>Client:</label><?php echo $apiKey;           ?></li>
          <?php if (strlen($scope) > 0) { ?>
            <li><label>Scope:</label> <?php echo $scope;          ?></li>
          <?php } ?>
          <?php if (strlen($consentMessage) > 0) { ?>
            <li><label>Note:</label>  <?php echo $consentMessage; ?></li>
          <?php } ?>
        </ul>

        <select name="answer">
          <option value="" selected="selected">Auswählen</option>
          <option value="allow">Zulassen</option>
          <option value="deny">Verweigern</option>
        </select>

        <input type="submit" name="submit" value="Bestätigen" />
      </form>
      <?php
    }
    ?>
  </div>

  <script src="<?php echo $viewURL; ?>js/jquery.min.js"></script>
  <script src="<?php echo $viewURL; ?>js/jquery-ui.min.js"></script>
</body>
</html>
