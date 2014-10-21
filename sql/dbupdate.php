<#1>
<?php
global $ilLog;
$ilLog->write(__METHOD__.': l');
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
$ilDB->createTable("rest_config", $fields, $dropExistingTable);
$ilDB->addPrimaryKey("rest_config", array("id"));
?>

<#2>
<?php
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'client_id' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => false
        ),
        'client_secret' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => false
        ),
        'redirection_uri' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false,
            'notnull' => false
        ),
        'oauth_consent_message' => array(
            'type' => 'text',
            'length' => 4000,
            'fixed' => false,
            'notnull' => false
        ),
        'permissions' => array(
            'type' => 'text',
            'length' => 4000,
            'fixed' => false,
            'notnull' => false
        )
    );
    $dropExistingTable = true;
    $ilDB->createTable("rest_apikeys", $fields, $dropExistingTable);
    $ilDB->addPrimaryKey("rest_apikeys", array("id"));

?>

<#3>
<?php
    $ilDB->manipulate("ALTER TABLE `rest_config` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT");
    $ilDB->manipulate("ALTER TABLE `rest_apikeys` CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT");
    $ilDB->query("ALTER TABLE `rest_apikeys` CHANGE `permissions` `permissions` VARCHAR( 60000 )");
?>

<#4>
<?php
    global $ilLog;
    $ilLog->write('Plugin REST -> Include Primary Rest Client');
    $client_id = "apollon";
    $client_secret = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,10);
    $redirection_uri = "";
    $oauth_consent_message = "";
    $permissions = '[{"pattern":"/clients","verb":"GET"},{"pattern":"/clients/:id","verb":"PUT"},{"pattern":"/clients/:id","verb":"DELETE"},{"pattern":"/clients/","verb":"POST"},{"pattern":"/routes","verb":"GET"}]';
    $a_columns = array("client_id" => array("text", $client_id),
        "client_secret" => array("text", $client_secret),
        "redirection_uri" => array("text", $redirection_uri),
        "oauth_consent_message" => array("text", $oauth_consent_message),
        "permissions" => array("text", $permissions));

    $ilDB->insert("rest_apikeys", $a_columns);
?>
<#5>
<?php
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
    $ilDB->insert("rest_config", $a_columns);
?>
<#6>
<?php
    $rest_user = "rest_sys_user";
    $a_columns = array("setting_name" => array("text", "rest_system_user"), "setting_value" => array("text",$rest_user));
    $ilDB->insert("rest_config", $a_columns);
    $rest_pass = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,10);
    $a_columns = array("setting_name" => array("text", "rest_user_pass"), "setting_value" => array("text",$rest_pass));
    $ilDB->insert("rest_config", $a_columns);
    //INSERT INTO `usr_data` VALUES (6,'root','dfa8327f5bfa4c672a04f9b38e348a70','root','user',NULL,'m','ilias@yourserver.com',NULL,NULL,NULL,NULL,NULL,NULL,'2005-07-20 15:11:40','2003-09-30 19:50:01',NULL,'',NULL,NULL,NULL,NULL,NULL,7,1,0,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'default',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,1217068076,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0);
    //INSERT INTO `rbac_ua` VALUES (6,2);
    //$md5_pass = md5($rest_pass);
    //$ilDB->query("INSERT INTO usr_data VALUES (5,'$rest_user','$md5_pass','$rest_user','user',NULL,'m','ilias@yourserver.com',NULL,NULL,NULL,NULL,NULL,NULL,'2005-07-20 15:11:40','2003-09-30 19:50:01',NULL,'',NULL,NULL,NULL,NULL,NULL,7,1,0,0,0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'default',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,1217068076,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0)");
    //$ilDB->query("INSERT INTO rbac_ua VALUES (5,2)");
   
?>