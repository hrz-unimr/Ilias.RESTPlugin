<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
?>


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
    $ilDB->createTable("ui_uihk_rest_config", $fields, true);
    $ilDB->addPrimaryKey("ui_uihk_rest_config", array("id"));

    $ilLog->write('Plugin REST -> Database updated to #1');
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
            'length' => 128,
            'fixed' => false,
            'notnull' => false
        ),
        'api_secret' => array(
            'type' => 'text',
            'length' => 128,
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
        'oauth2_gt_client_active' => array(
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
        'oauth2_authcode_refresh_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 1
        ),
        'oauth2_resource_refresh_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'ip_restriction_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        ),
        'description' => array(
            'type' => 'text',
            'length' => 1000,
            'fixed' => false,
            'notnull' => false,
            'default' => ""
        ),
    );
    $ilDB->createTable("ui_uihk_rest_keys", $fields, true);
    $ilDB->addPrimaryKey("ui_uihk_rest_keys", array("id"));

    $ilLog->write('Plugin REST -> Database updated to #2');
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
    $ilDB->createTable("ui_uihk_rest_key2user", $fields, true);
    $ilDB->addPrimaryKey("ui_uihk_rest_key2user", array("id"));

    $ilLog->write('Plugin REST -> Database updated to #3');
?>


<#4>
<?php
    global $ilLog;

    $ilDB->manipulate('ALTER TABLE ui_uihk_rest_config CHANGE id id INT NOT NULL AUTO_INCREMENT');
    $ilDB->manipulate('ALTER TABLE ui_uihk_rest_keys CHANGE id id INT NOT NULL AUTO_INCREMENT');
    $ilDB->manipulate('ALTER TABLE ui_uihk_rest_key2user CHANGE id id INT NOT NULL AUTO_INCREMENT');

    $ilLog->write('Plugin REST -> Database updated to #4');
?>


<#5>
<?php
    global $ilLog;
    $ilLog->write('Plugin REST ->Include Primary REST Client');

    $api_key = "apollon";
    $api_secret = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,10);
    $redirection_uri = "";
    $oauth_consent_message = "";

    $fields = array(
        "api_key" => array("text", $api_key),
        "api_secret" => array("text", $api_secret),
        "oauth2_redirection_uri" => array("text", $redirection_uri),
        "oauth2_consent_message" => array("text", $oauth_consent_message)
    );
    $ilDB->insert("ui_uihk_rest_keys", $fields);

    $ilLog->write('Plugin REST -> Database updated to #5');
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
    $salt = gen_uuid();
    $fields = array(
        "setting_name" => array("text", "token_salt"),
        "setting_value" => array("text", $salt)
    );
    $ilDB->insert("ui_uihk_rest_config", $fields);

    $ilLog->write('Plugin REST -> Database updated to #6');
?>


<#7>
<?php
    global $ilLog;

    $rest_soap_user = "rest_sys_user";
    $fields = array(
        "setting_name" => array("text", "rest_soap_user"),
        "setting_value" => array("text", $rest_soap_user)
    );
    $ilDB->insert("ui_uihk_rest_config", $fields);

    $rest_soap_pass = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,10);
    $fields = array(
        "setting_name" => array("text", "rest_soap_pass"),
        "setting_value" => array("text", $rest_soap_pass)
    );
    $ilDB->insert("ui_uihk_rest_config", $fields);

    $ilLog->write('Plugin REST -> Database updated to #7');
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
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'api_id' => array(
            'type' => 'text',
            'length' => 128,
            'fixed' => false,
            'notnull' => false
        ),
        'refresh_token' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false,
            'notnull' => false
        ),
        'last_refresh_timestamp' => array('type' => 'timestamp'),
        'init_timestamp' => array('type' => 'timestamp'),
        'num_resets' => array('type' => 'integer', 'length' => 4, 'notnull' => true)
    );
    $ilDB->createTable("ui_uihk_rest_oauth2", $fields, true);
    $ilDB->addPrimaryKey("ui_uihk_rest_oauth2", array("id"));
    $ilDB->manipulate('ALTER TABLE ui_uihk_rest_oauth2 CHANGE id id INT NOT NULL AUTO_INCREMENT');

    $ilLog->write('Plugin REST -> Database updated to #8');
?>


<#9>
<?php
    global $ilLog;
    global $ilDB;

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
        'pattern' => array(
            'type' => 'text',
            'length' => 512,
            'fixed' => false,
            'notnull' => false
        ),
        'verb' => array(
            'type' => 'text',
            'length' => 16,
            'fixed' => false,
            'notnull' => false
        )
    );

    $ilDB->createTable("ui_uihk_rest_perm", $fields, true);
    $ilDB->addPrimaryKey("ui_uihk_rest_perm", array("id"));
    $ilDB->manipulate('ALTER TABLE ui_uihk_rest_perm CHANGE id id INT NOT NULL AUTO_INCREMENT');

    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clients'),
        "verb" => array("text", 'GET')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clients/:id'),
        "verb" => array("text", 'PUT')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clients/:id'),
        "verb" => array("text", 'DELETE')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clients/'),
        "verb" => array("text", 'POST')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/routes'),
        "verb" => array("text", 'GET')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clientpermissions'),
        "verb" => array("text", 'GET')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clientpermissions/:id'),
        "verb" => array("text", 'DELETE')
    ));
    $ilDB->insert("ui_uihk_rest_perm", array(
        "api_id" => array("integer", 1),
        "pattern" => array("text", '/clientpermissions/'),
        "verb" => array("text", 'POST')
    ));

    $ilLog->write('Plugin REST -> Database updated to #9');
?>


<#10>
<?php
    global $ilLog;

    $fields = array(
        "setting_name" => array("text", "token_ttl"),
        "setting_value" => array("text", 30)
    );
    $ilDB->insert("ui_uihk_rest_config", $fields);

    $ilLog->write('Plugin REST -> Database updated to #10');
?>


<#11>
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
    'ip' => array(
        'type' => 'text',
        'length' => 45,
        'notnull' => true
    )
);
$ilDB->createTable("ui_uihk_rest_key2ip", $fields, true);
$ilDB->addPrimaryKey("ui_uihk_rest_key2ip", array("id"));
$ilDB->manipulate('ALTER TABLE ui_uihk_rest_key2ip CHANGE id id INT NOT NULL AUTO_INCREMENT');

$ilLog->write('Plugin REST -> Database updated to #11');
?>


<#12>
<?php
// If we'd like to log debug-information
global $ilLog;

// Create a new table for challenge-response authentification
$fields = array(
  'user_id' => array(
    'type' => 'integer',
    'length' => 4,
    'notnull' => true
  ),
  'client_challenge' => array(
    'type' => 'text',
    'length' => 25,
    'notnull' => true
  ),
  'server_challenge' => array(
    'type' => 'text',
    'length' => 25,
    'notnull' => true
  )
);
$ilDB->createTable('ui_uihk_rest_challenge',    $fields, true);
$ilDB->addPrimaryKey('ui_uihk_rest_challenge',  array('user_id'));
?>


<#13>
<?php
// Load extension-databases
foreach (glob(realpath(__DIR__).'/extensions/*/sql/dbupdate.php') as $filename)
    include_once($filename);
?>
