<!DOCTYPE html>
<html lang="en">
    <?php
    // Portions of this code taken from https://github.com/bshaffer/oauth2-demo-php
    // You can also find various examples for the different Oaith2-Types.
    ?>
    <head>
        <title>OAuth2: Implicit Grant Endpoint</title>
    </head>
    <body>
    <h3>OAuth2 Token via Imlicit Grant Workflow Retrieved!</h3>
    <pre><code>Access-Token: <span id="access_token_display"><a onclick="showToken('access_token');">(click here to pull from URL fragment)</a></span> </code></pre>
    <!--
    <pre><code>Refresh-Token: <span id="refresh_token_display"><a onclick="showToken('refresh_token');">(click here to pull from URL fragment)</a></span> </code></pre>
    -->
    <h4> The client can continue now making further API requests with the obtained bearer token.</h4>

    <!-- Javascript for pulling the access token from the URL fragment -->
    <script>
        function getToken(token_type) {
            var queryString = window.location.hash.substr(1);
            var queries = queryString.split("&");
            var params = {}
            for (var i = 0; i < queries.length; i++) {
                pair = queries[i].split('=');
                params[pair[0]] = pair[1];
            }

            return params[token_type];
        };

        // show the token parsed from the fragment, and show the next step
        var showToken = function (token_type) {
            var token = getToken(token_type);
            if (token)
                document.getElementById(token_type+'_display').innerHTML = getToken(token_type);
        }
    </script>
    <a href='../'>Back to the demo</a>
    </body>
</html>
