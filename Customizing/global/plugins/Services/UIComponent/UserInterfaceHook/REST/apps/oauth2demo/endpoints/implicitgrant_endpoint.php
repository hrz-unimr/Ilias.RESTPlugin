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
    <h4> The client can continue now making further API requests with the obtained bearer token.</h4>

    <!-- Javascript for pulling the access token from the URL fragment -->
    <script>
        function parse(val) {
          var result = "Not found",
              tmp = [];
          location.search
          .substr(1)
            .split("&")
            .forEach(function (item) {
            tmp = item.split("=");
            if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
          });
          return result;
        }

        // show the token parsed from the fragment, and show the next step
        var showToken = function (token_type) {
          document.getElementById(token_type+'_display').innerHTML = parse(token_type)
        }
    </script>
    <a href='../'>Back to the demo</a>
    </body>
</html>
