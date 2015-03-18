<#1>
<?php
global $ilLog;
$ilLog->write('Plugin REST -> DB_Update');

$fields = array(
    'id' => array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => true
    ),
    'setting_name' => array(
        'type' => 'text',
        'length' => 1000,
        'fixed' => false,
        'notnull' => false
    ),
    'setting_value' => array(
        'type' => 'text',
        'length' => 1000,
        'fixed' => false,
        'notnull' => false
    )
);
$dropExistingTable = true;
$ilDB->createTable("ui_uihk_rest_config", $fields, $dropExistingTable);
$ilDB->addPrimaryKey("ui_uihk_rest_config", array("id"));

$ilLog->write('Plugin REST -> DB_Update to #1');
?>

<#2>
<?php
    global $ilLog;
    
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'api_key' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => false
        ),
        'api_secret' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => false
        ),
        'oauth2_redirection_uri' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false,
            'notnull' => false,
            'default' => ""
        ),
        'oauth2_consent_message' => array(
            'type' => 'text',
            'length' => 4000,
            'fixed' => false,
            'notnull' => false,
            'default' => ""
        ),
        'permissions' => array(
            'type' => 'text',
            'length' => 4000,
            'fixed' => false,
            'notnull' => false
        ),
        'oauth2_gt_client_active' => array( // grant type
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'oauth2_gt_authcode_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'oauth2_gt_implicit_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'oauth2_gt_resourceowner_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'oauth2_user_restriction_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'oauth2_gt_client_user' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 6
        ),
        'oauth2_consent_message_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'oauth2_authcode_refresh_active' => array( // oauth2_granttype_authorization_code_refresh_active
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'oauth2_resource_refresh_active' => array( // oauth2_granttype_resourceowner_refresh_active
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
    );
    $dropExistingTable = true;
    $ilDB->createTable("ui_uihk_rest_keys", $fields, $dropExistingTable);
    $ilDB->addPrimaryKey("ui_uihk_rest_keys", array("id"));

    $ilLog->write('Plugin REST -> DB_Update to #2');
?>

<#3>
<?php
    global $ilLog;
    
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'api_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        )
    );
    $dropExistingTable = true;
    $ilDB->createTable("ui_uihk_rest_keymap", $fields, $dropExistingTable);
    $ilDB->addPrimaryKey("ui_uihk_rest_keymap", array("id"));
    
    $ilLog->write('Plugin REST -> DB_Update to #3');
?>

<#4>
<?php
    global $ilLog;
    
    $ilDB->manipulate("ALTER TABLE `ui_uihk_rest_config` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT");
    $ilDB->manipulate("ALTER TABLE `ui_uihk_rest_keys` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT");
    $ilDB->manipulate("ALTER TABLE `ui_uihk_rest_keymap` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT");
    $ilDB->query("ALTER TABLE `ui_uihk_rest_keys` CHANGE `permissions` `permissions` VARCHAR( 60000 )");
    
    $ilLog->write('Plugin REST -> DB_Update to #4');
?>

<#5>
<?php
    global $ilLog;
    $ilLog->write('Plugin REST -> Include Primary REST Client');
    
    $api_key = "apollon";
    $api_secret = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,10);
    $redirection_uri = "";
    $oauth_consent_message = "";
    $permissions = '[{"pattern":"/clients","verb":"GET"},{"pattern":"/clients/:id","verb":"PUT"},{"pattern":"/clients/:id","verb":"DELETE"},{"pattern":"/clients/","verb":"POST"},{"pattern":"/routes","verb":"GET"}]';
    $a_columns = array("api_key" => array("text", $api_key),
        "api_secret" => array("text", $api_secret),
        "oauth2_redirection_uri" => array("text", $redirection_uri),
        "oauth2_consent_message" => array("text", $oauth_consent_message),
        "permissions" => array("text", $permissions));

    $ilDB->insert("ui_uihk_rest_keys", $a_columns);
    
    $ilLog->write('Plugin REST -> DB_Update to #5');
?>

<#6>
<?php
    global $ilLog;
    
    function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    $uuid = gen_uuid();
    $a_columns = array("setting_name" => array("text", "uuid"), "setting_value" => array("text",$uuid));
    $ilDB->insert("ui_uihk_rest_config", $a_columns);
    
    $ilLog->write('Plugin REST -> DB_Update to #6');
?>

<#7>
<?php
    global $ilLog;
    
    $rest_user = "rest_sys_user";
    $a_columns = array("setting_name" => array("text", "rest_system_user"), "setting_value" => array("text",$rest_user));
    $ilDB->insert("ui_uihk_rest_config", $a_columns);
    $rest_pass = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,10);
    $a_columns = array("setting_name" => array("text", "rest_user_pass"), "setting_value" => array("text",$rest_pass));
    $ilDB->insert("ui_uihk_rest_config", $a_columns);
    
    //INSERT INTO 'usr_data' VALUES (6,'root','dfa8327f5bfa4c672a04f9b38e348a70','root','user',NULL,'m','ilias@yourserver.com',NULL,NULL,NULL,NULL,NULL,NULL,'2005-07-20 15:11:40','2003-09-30 19:50:01',NULL,'',NULL,NULL,NULL,NULL,NULL,7,1,0,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'default',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,1217068076,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0);
    //INSERT INTO 'rbac_ua' VALUES (6,2);
    //$md5_pass = md5($rest_pass);
    //$ilDB->query("INSERT INTO usr_data VALUES (5,'$rest_user','$md5_pass','$rest_user','user',NULL,'m','ilias@yourserver.com',NULL,NULL,NULL,NULL,NULL,NULL,'2005-07-20 15:11:40','2003-09-30 19:50:01',NULL,'',NULL,NULL,NULL,NULL,NULL,7,1,0,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'default',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,1217068076,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0)");
    //$ilDB->query("INSERT INTO rbac_ua VALUES (5,2)");
    
    $ilLog->write('Plugin REST -> DB_Update to #7');
?>

<#8>
<?php
    global $ilLog;
    $ilLog->write('Plugin REST -> DB_Update: ui_uihk_rest_oauth2');

    // setup of table ui_uihk_rest_oauth2
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        /*'client_id' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => false
        ),*/
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'api_key' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false,
            'notnull' => false
        ),
        'refresh_token' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false,
            'notnull' => false
        ),
        'num_refresh_left' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'last_refresh_timestamp' => array('type' => 'timestamp'), // -> will be datetime in mysql!
        'init_timestamp' => array('type' => 'timestamp'),        // -> will be datetime in mysql!
        'num_resets' => array('type' => 'integer', 'length' => 4, 'notnull' => true)
    );
    $dropExistingTable = true;
    $ilDB->createTable("ui_uihk_rest_oauth2", $fields, $dropExistingTable);
    $ilDB->addPrimaryKey("ui_uihk_rest_oauth2", array("id"));
    $ilDB->manipulate("ALTER TABLE `ui_uihk_rest_oauth2` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT");

    $ilLog->write('Plugin REST -> DB_Update to #8');
?>

<#9>
<?php
    global $ilLog;
    
    global $ilPluginAdmin;
    $ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");
    copy($ilRESTPlugin->getDirectory() . "/gateways/restplugin.php", "./restplugin.php");

    $ilLog->write('Plugin REST -> DB_Update to #9');
?>
