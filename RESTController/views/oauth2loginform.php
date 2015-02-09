<?php
// original ilias login form
 //var_dump($_REQUEST);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title> ILIAS-Anmeldeseite</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta http-equiv="content-language" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-startup-image" href="/templates/default/images/logo/ilias_logo_startup_320x460.png" /><!-- Startup image -->
    <link rel="apple-touch-icon-precomposed" href="/templates/default/images/logo/ilias_logo_57x57-precomposed.png" /><!-- iphone -->
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="/templates/default/images/logo/ilias_logo_72x72-precomposed.png" /><!-- ipad -->
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="/templates/default/images/logo/ilias_logo_114x114-precomposed.png" /><!-- iphone retina -->
    <link rel="stylesheet" type="text/css" href="/templates/default/delos.css?vers=4-4-0-RC1-2014-01-04" />

</head>
<body class="std"  >
<div id="drag_zmove"></div>
<div id="ilAll">
<div class="ilStartupFrame">
<div id="il_startup_logo">
    <img src="/templates/default/images/HeaderIcon.png" alt="Logo" />
</div>
<div id="il_startup_content">
<div class="ilMessageBox">
    <p style="color:#ff0000;">
    <?php
        if (isset($this->data['error_msg'])) {
            echo $this->data['error_msg'];
        }
    ?>
    </p>
</div>
<div>
</div>
<div class="ilStartupSection">

    <p>
    <form id="form_" name="formlogin" action="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/restplugin.php/v1/oauth2/auth" method="post">
        <div class="ilForm">
            <div class="ilFormRow">
                <div class="ilFormHeader">
                    <h3 class="ilFormHeader"><a name="il_form_top"></a>Bei ILIAS anmelden</h3><div class="ilFormInfo"></div>
                </div>
                <div class="ilFormHeader ilFormCommands">
                </div>
            </div>
            <div class="ilFormRow">
                <div class="ilFormOption" id="il_prop_cont_username">
                    <label for="username">Benutzername <span class="asterisk">*</span></label>
                </div>
                <div class="ilFormValue">
                    <div style="">
                        <input  type="text" size="20" id="username"  maxlength="200" name="username"  />
                    </div>
                </div>
            </div> <!-- end of ilFormRow -->
            <!-- <script type="text/javascript">
            $(function() {
                il.Form.initItem('', );
            });
            </script> -->
            <div class="ilFormRow">
                <div class="ilFormOption" id="il_prop_cont_password">
                    <label for="password">Passwort <span class="asterisk">*</span></label>
                </div>
                <div class="ilFormValue">
                    <input type="password" size="20" id="password" maxlength="" name="password"  autocomplete="off"/><br />
                </div>
            </div> <!-- end of ilFormRow -->
            <!-- <script type="text/javascript">
            $(function() {
                il.Form.initItem('', );
            });
            </script> -->

            <div class="ilFormRow">
                <div class="ilFormFooter">
                    <span class="asterisk">*</span><span class="small"> Erforderliche Angabe</span>
                    &nbsp;</div>
                <div class="ilFormFooter ilFormCommands">
                    <input class="submit" type="submit" name="cmd[showLogin]" value="Anmelden" />
                </div>
            </div> <!-- end of ilFormRow -->
        </div>
        <input id="api_key" name ="api_key" type="hidden" value="<?php echo $this->data['api_key']; ?>" />
        <input id="redirect_uri" name ="redirect_uri" type="hidden" value="<?php echo $this->data['redirect_uri']; ?>" />
        <input id="response_type" name ="response_type" type="hidden" value="<?php echo $this->data['response_type']; ?>" />
    </form>
    </p>

    <p class="ilStartupSection">
    </p>
</div>
<script language="JavaScript">
    <!--
    if (document.formlogin.username && document.formlogin.password)
    {
        if(document.formlogin.username.value!="") document.formlogin.password.focus();
        else document.formlogin.username.focus();
    }
    //-->
</script>


</div>
</div>
<div class="il_Footer">
    powered by ILIAS  |
    <a href="http://localhost/goto.php?target=impr_0" target="_blank">Impressum</a>
    |
    <a href="mailto:">Administration kontaktieren</a>
</div>
</div>
</body>
</html>
