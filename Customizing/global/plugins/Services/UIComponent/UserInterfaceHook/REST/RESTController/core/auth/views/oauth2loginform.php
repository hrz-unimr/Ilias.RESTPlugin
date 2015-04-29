<?php
$restAPI = dirname($_SERVER['SCRIPT_NAME']);
$restAPI = str_replace('\\', '/', $restAPI);
$restAPI = ($restAPI == '/' ? '' : $restAPI);
?>
<div id="ilAll">
    <div class="ilStartupFrame">
        <div id="ilStartupLogo">
            <img src="<?php echo $tpl_path; ?>/img/ilias.png" alt="Logo" />
        </div>
        <div id="ilStartupContent">
            <div class="ilMessageBox">
                <p style="color:#ff0000;">
                    <?php
                    if (isset($tpl_data['error_msg'])) {
                        echo $tpl_data['error_msg'];
                    }
                    ?>
                </p>
            </div>
            <form id="form_" id="formlogin" action="<?php echo $restAPI; ?>/restplugin.php/v1/oauth2/auth" method="post">
                <div class="ilForm">
                    <div class="ilFormRow">
                        <h3 class="ilFormHeader"><a id="il_form_top">Bei ILIAS anmelden</a></h3>
                    </div>
                    <div class="ilFormRow">
                        <div class="ilFormOption" id="il_prop_cont_username">
                            <label for="username">Benutzername <span class="asterisk">*</span></label>
                        </div>
                        <div class="ilFormValue">
                            <input  type="text" size="20" id="username"  maxlength="200" name="username"  />
                        </div>
                    </div>
                    <div class="ilFormRow">
                        <div class="ilFormOption" id="il_prop_cont_password">
                            <label for="password">Passwort <span class="asterisk">*</span></label>
                        </div>
                        <div class="ilFormValue">
                            <input type="password" size="20" id="password" maxlength="" name="password" autocomplete="off"/><br />
                        </div>
                    </div>
                    <div class="ilFormRow">
                        <div class="ilFormFooter">
                            <span class="asterisk">*</span><span class="small"> Erforderliche Angabe</span>
                            &nbsp;
                        </div>
                        <div class="ilFormFooter ilFormCommands">
                            <input class="submit" type="submit" name="cmd[showLogin]" value="Anmelden" />
                        </div>
                    </div>
                </div>
                <div>
                    <input id="api_key" name ="api_key" type="hidden" value="<?php echo $tpl_data['api_key']; ?>" />
                    <input id="redirect_uri" name ="redirect_uri" type="hidden" value="<?php echo $tpl_data['redirect_uri']; ?>" />
                    <input id="response_type" name ="response_type" type="hidden" value="<?php echo $tpl_data['response_type']; ?>" />
                </div>
            </form>
        </div>
    </div>
    <div class="il_Footer">Powered by ILIAS | <a href="http://localhost/goto.php?target=impr_0" target="_blank">Impressum</a> | <a href="mailto:">Administration kontaktieren</a>
    </div>
</div>