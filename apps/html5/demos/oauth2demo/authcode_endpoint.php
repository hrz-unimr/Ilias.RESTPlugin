<?php
   // print_r($_REQUEST);
    if (isset($_GET['code'])){ // Exchange OAuth 2 authorization code for bearer token

        $api_key = "apollon";
        $api_secret = "LO3ScgUvN7"; // Top Secret
        $restUrl = "http://localhost/ilias5/restplugin.php/v1/oauth2/token";

        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $protocol = 'http://';
        } else {
            $protocol = 'https://';
        }
        $redirect_uri = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        if ($_SERVER["SERVER_PORT"] != "80") {
            $redirect_uri = $protocol . $_SERVER['SERVER_NAME'] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER['PHP_SELF'];
        }

        $postBody = array('grant_type'=> 'authorization_code',
                          'code' => $_GET['code'],
                          'api_key' => $api_key,
                          'api_secret' => $api_secret,
                          'redirect_uri' => $redirect_uri
                            );
        //var_dump($postBody);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $restUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($curl_response,true);


        echo "<h3>You are logged in now. Behind the scene, the client exchanged the OAuth2 authentication code for a bearer token:</h3>";
        var_dump($decoded);
        echo "<h4> The client can continue now making further API requests with the obtained bearer token.</h4>";
        echo "<a href='start.php'>Back to the demo</a>";
    }
?>
