<!DOCTYPE html>
<html lang="en">
<head>
    <title>OAuth2: Review permissions</title>
</head>
<body>
    <form id="consentform" accept-charset="UTF-8" action="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/restplugin.php/v1/oauth2/auth" method="post">
    <h3 class="permission-title">Review permissions</h3>
    <div class="oauth-permissions">
        <ul>
            <li>Permission to access my course memberships</li>
            <li>Permission to read and modify my personal settings</li>
        </ul>
    </div>
    <p>
        <input name="authenticity_token" type="hidden" value="<?php echo $this->data['authenticity_token']; ?>" /></div>
        <input id="api_key" name="api_key" type="hidden" value="<?php echo $this->data['api_key']; ?>" />
        <input id="redirect_uri" name="redirect_uri" type="hidden" value="<?php echo $this->data['redirect_uri']; ?>" />
        <input id="scope" name="scope" type="hidden" value="" />
        <input id="response_type" name="response_type" type="hidden" value="<?php echo $this->data['response_type']; ?>" />
        <button type="submit" name="authorize" value="1" tabindex="1" class="button primary">Authorize application</button>
    </p>
    </form>
</body>
</html>