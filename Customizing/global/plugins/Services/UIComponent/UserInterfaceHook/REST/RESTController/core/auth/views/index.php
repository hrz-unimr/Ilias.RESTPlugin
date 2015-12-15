<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */


// Use given parameters or default input
$username = (isset($parameters['username'])) ? $parameters['username'] : 'Benutzter';
$password = (isset($parameters['password'])) ? $parameters['password'] : 'Passwort';
?>

<!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8">
  <title>ILIAS oAuth2 Anmeldung - <?php echo $client; ?></title>

  <link rel="stylesheet" href="<?php echo $viewURL; ?>css/jquery-ui.min.css">
  <link rel="stylesheet" href="<?php echo $viewURL; ?>css/style.css">
</head>

<body>
  <div class="login-card">
    <img class="logo" src="<?php echo $viewURL; ?>img/logo.png">

    <h1>Anmeldung</h1>
    <h2>Anwendungs-Zugriff</h2><br>

    <form>
      <input type="text"     name="user"  placeholder="<?php echo $username; ?>">
      <input type="password" name="pass"  placeholder="<?php echo $password; ?>">
      <input type="submit"   name="login" value="Anmelden" class="login login-submit">
    </form>
  </div>

  <script src="<?php echo $viewURL; ?>js/jquery.min.js"></script>
  <script src="<?php echo $viewURL; ?>js/jquery-ui.min.js"></script>
</body>
</html>


<!--
// Show login page if no resource-owner credentials are given or an exception has happened
if (!isset($parameters['username']) || !isset($parameters['password']) || isset($parameters['exception'])) {
  // Is there an exception to display?
  $exception = $parameters['exception'];
  if (isset($exception)) {
    ?>
    <div><?php echo $exception->getRESTMessage(); ?></div>
    <?php
  }

  // Use given parameters or default input
  $username = (isset($parameters['username'])) ? $parameters['username'] : 'Benutzter';
  $password = (isset($parameters['password'])) ? $parameters['password'] : 'Passwort';

  // Show actual login-form
  ?>
  <form action="<?php echo $endpoint; ?>" method="POST">
    <input type="hidden" name="response_type" value="<?php echo $parameters['response_type']; ?>" />
    <input type="hidden" name="redirect_uri"  value="<?php echo $parameters['redirect_uri']; ?>" />
    <input type="hidden" name="api_secret"    value="<?php echo $parameters['api_secret']; ?>" />
    <input type="hidden" name="api_key"       value="<?php echo $parameters['api_key']; ?>" />
    <input type="hidden" name="scope"         value="<?php echo $parameters['scope']; ?>" />
    <input type="hidden" name="state"         value="<?php echo $parameters['state']; ?>" />

    <label for="username">Benutzername</label>
    <input type="text"     id="username" name="username" value="<?php echo $username; ?>" />

    <label for="password">Passwort</label>
    <input type="password" id="password" name="password" value="<?php echo $password; ?>" />

    <input type="submit" name="submit" value="Anmelden" />
  </form>
  <?php
}

// Show page to allow or deny the client access to ones resources
else {
  ?>
  <form action="<?php echo $endpoint; ?>" method="POST">
    <input type="hidden" name="response_type" value="<?php echo $parameters['response_type']; ?>" />
    <input type="hidden" name="redirect_uri"  value="<?php echo $parameters['redirect_uri']; ?>" />
    <input type="hidden" name="api_secret"    value="<?php echo $parameters['api_secret']; ?>" />
    <input type="hidden" name="api_key"       value="<?php echo $parameters['api_key']; ?>" />
    <input type="hidden" name="scope"         value="<?php echo $parameters['scope']; ?>" />
    <input type="hidden" name="state"         value="<?php echo $parameters['state']; ?>" />

    <input type="hidden" name="username"      value="<?php echo $parameters['username']; ?>" />
    <input type="hidden" name="password"      value="<?php echo $parameters['password']; ?>" />

    <div>Client: <?php echo $parameters['api_key'];         ?></div>
    <div>Scope:  <?php echo $parameters['scope'];           ?></div>
    <div>Note:   <?php echo $parameters['consent_message']; ?></div>

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
-->
