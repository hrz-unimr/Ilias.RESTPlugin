<?php
// Portions of this code taken from https://github.com/bshaffer/oauth2-demo-php
// You can also find various examples for the different Oaith2-Types.
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>OAuth2: Implicit Grant Endpoint</title>
    </head>
    <body>
    <h3>OAuth2 Token via Imlicit Grant Workflow Retrieved!</h3>
    <pre><code>Bearer Token: <span id="access_token_display"><a onclick="showAccessToken();">(click here to pull from URL fragment)</a></span> </code></pre>
    <h4> The client can continue now making further API requests with the obtained bearer token.</h4>

    <!-- Javascript for pulling the access token from the URL fragment -->
    <script>
        function getAccessToken() {
            var queryString = window.location.hash.substr(1);
            var queries = queryString.split("&");
            var params = {}
            for (var i = 0; i < queries.length; i++) {
                pair = queries[i].split('=');
                params[pair[0]] = pair[1];
            }

            return params.access_token;
        };

        // show the token parsed from the fragment, and show the next step
        var showAccessToken = function (e) {
            document.getElementById('access_token_display').innerHTML = getAccessToken();
            document.getElementById('request_resource').style.display = 'block';
        }
    </script>
    <a href='start.php'>Back to the demo</a>
    </body>
</html>
