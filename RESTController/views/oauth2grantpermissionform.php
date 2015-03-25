<!DOCTYPE html>
<html lang="en">
<head>
    <title>OAuth2: Review permissions</title>
</head>
<body>
    <form id="consentform" accept-charset="UTF-8" action="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/restplugin.php/v1/oauth2/auth" method="post">
    <h3 class="permission-title">The current application requests access to ILIAS on your behalf.</h3>
        <h4>If you agree with this, you need to press the button "Authorize application" on the bottom of the page.</h4>
    <?php if (isset($this->data['oauth2_consent_message'])) {
      ?>
        In the following, the application states the scope of actions it will perform and/or describes purpose:
        <div class="oauth-consent-message">
            <?php echo $this->data['oauth2_consent_message']; ?>
        </div>
        <?php
    }
    ?>

    <div>
        <input name="authenticity_token" type="hidden" value="<?php echo $this->data['authenticity_token']; ?>" />
        <input id="api_key" name="api_key" type="hidden" value="<?php echo $this->data['api_key']; ?>" />
        <input id="redirect_uri" name="redirect_uri" type="hidden" value="<?php echo $this->data['redirect_uri']; ?>" />
        <input id="scope" name="scope" type="hidden" value="" />
        <input id="response_type" name="response_type" type="hidden" value="<?php echo $this->data['response_type']; ?>" />
        <button type="submit" name="authorize" value="1" tabindex="1" class="button primary">Authorize application</button>
    </div>
    </form>
</body>
</html>