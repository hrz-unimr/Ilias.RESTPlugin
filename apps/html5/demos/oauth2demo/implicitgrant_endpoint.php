<!-- Portions of this code taken from https://github.com/bshaffer/oauth2-demo-php -->
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>OAuth2: Implicit Grant Endpoint</title>
    </head>
    <body>

    <h3>OAuth2 Token via Imlicit Grant Workflow Retrieved!</h3>

    <pre><code>Access Token: <span id="access_token_display"><a onclick="showAccessToken();">(click here to pull from URL fragment)</a></span> </code></pre>

    <div id="request_resource" style="none">
        <p>This token can now be used multiple times to make API requests for this user.</p>

    </div>

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
