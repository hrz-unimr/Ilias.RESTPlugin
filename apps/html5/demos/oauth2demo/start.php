<?php

    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $protocol = 'http://';
    } else {
        $protocol = 'https://';
    }
    $base_url = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']);
    if ($_SERVER["SERVER_PORT"] != "80") {
        $base_url = $protocol . $_SERVER['SERVER_NAME'] . ":" . $_SERVER["SERVER_PORT"] . dirname($_SERVER['PHP_SELF']);
    }
    $apikey = "apollon"; // apikey alias oauth2 client_id
    $apipass = "oMzVXctRuu"; // only needed for "Clients Credentials Grant"
    $subFolder = "/ilias5";
    $loginUrl = $subFolder. "/restplugin.php/v1/oauth2/auth?client_id=".urlencode($apikey);

    // Prerequisite the demo endpoints are located within the same directory as this script
    $authGrantUrl = $loginUrl."&redirect_uri=".urlencode($base_url."/authcode_endpoint.php")."&response_type=code";
    $implicitGrantUrl = $loginUrl."&redirect_uri=".urlencode($base_url."/implicitgrant_endpoint.php")."&response_type=token";
?>
<html>
    <head></head>
    <body>
        <h2>Demo: ILIAS REST Plugin and OAuth2 </h2>
        <p style="color:red;">Note: it is necessary to adapt the files "start.php" and "authcode_endpoint.php"! There you need to specify a valid rest client_id, password and url to the rest-endpoint.</p>
        <h3>Initiating one of the following OAuth2 Grant Mechanism via a GET Request:</h3>
        <ul>
            <li><a href = "<?php echo $authGrantUrl; ?>">My ILIAS (via OAuth2 - Authorization Code)</a></li>
            <li><a href = "<?php echo $implicitGrantUrl; ?>"> My ILIAS (via OAuth2 - Implicit Grant) </a></li>
        </ul>
        <h3>Initiating one of the following OAuth2 Grant Mechanism via a POST Request:</h3>
        <ul>
            <li>
                <form method="POST" action="<?php echo $subFolder;?>/restplugin.php/v1/oauth2/auth">
                    <input type="hidden" name="client_id" value="<?php echo $apikey; ?>" />
                    <input type="hidden" name="response_type" value="code" />
                    <input type="hidden" name="redirect_uri" value="<?php echo $base_url."/authcode_endpoint.php";?>" />
                    <input type="submit" value="Authorization Code Grant" />
                </form>

            </li>
            <li>
                <form method="POST" action="<?php echo $subFolder;?>/restplugin.php/v1/oauth2/auth">
                    <input type="hidden" name="client_id" value="<?php echo $apikey; ?>" />
                    <input type="hidden" name="response_type" value="token" />
                    <input type="hidden" name="redirect_uri" value="<?php echo $base_url."/implicitgrant_endpoint.php"; ?>" />
                    <input type="submit" value="Implicit Grant" />
                </form>
            </li>
            <li>
                <form method="POST" action="<?php echo $subFolder;?>/restplugin.php/v1/oauth2/token">

                    <input type="hidden" name="grant_type" value="client_credentials" />
                    <input type="hidden" name="scope" value="" />
                    <input type="hidden" name="client_id" value="<?php echo $apikey; ?>" />
                    <input type="hidden" name="client_secret" value="<?php echo $apipass; ?>" />
                    <input type="submit" value="Clients Credentials" />
                </form>
            </li>
            <li>
                <form method="POST" action="<?php echo $subFolder;?>/restplugin.php/v1/oauth2/token">

                    <input type="hidden" name="grant_type" value="password" />
                    <input type="hidden" name="scope" value="" />
                    <input type="hidden" name="client_id" value="<?php echo $apikey; ?>" />
                    username: <input type="text" name="username" />
                    password: <input type="password" name="password" />
                    <input type="submit" value="Resource Owner Password Credentials Grant" />
                </form>
            </li>

        </ul>


    </body>
</html>
