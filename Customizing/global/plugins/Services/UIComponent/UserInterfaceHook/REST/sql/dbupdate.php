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
  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'setting_name' => array(
      'type'    => 'text',
      'length'  => 1000,
      'fixed'   => false,
      'notnull' => false
    ),
    'setting_value' => array(
      'type'    => 'text',
      'length'  => 1000,
      'fixed'   => false,
      'notnull' => false
    )
  );
  $ilDB->createTable('ui_uihk_rest_config', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_config', array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_config CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #1: Created ui_uihk_rest_config.');
?>
<#2>
<?php
  function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for 'time_low'
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

      // 16 bits for 'time_mid'
      mt_rand( 0, 0xffff ),

      // 16 bits for 'time_hi_and_version',
      // four most significant bits holds version number 4
      mt_rand( 0, 0x0fff ) | 0x4000,

      // 16 bits, 8 bits for 'clk_seq_hi_res',
      // 8 bits for 'clk_seq_low',
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand( 0, 0x3fff ) | 0x8000,

      // 48 bits for 'node'
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
  }
  $soap_username = 'rest_sys_user';
  $soap_password = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 10);

  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'salt'),
    'setting_value' => array('text', gen_uuid())
  ));
  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'soap_username'),
    'setting_value' => array('text', $soap_username)
  ));
  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'soap_password'),
    'setting_value' => array('text', $soap_password)
  ));
  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'access_token_ttl'),
    'setting_value' => array('text', '30')
  ));
  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'refresh_token_ttl'),
    'setting_value' => array('text', '315360000')
  ));
  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'authorization_token_ttl'),
    'setting_value' => array('text', 1)
  ));
  $ilDB->insert('ui_uihk_rest_config', array(
    'setting_name'  => array('text', 'short_token_ttl'),
    'setting_value' => array('text', '1')
  ));

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #2: Filled ui_uihk_rest_config.');
?>
<#3>
<?php
  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'api_key' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'api_secret' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'cert_serial' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'cert_issuer' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'cert_subject' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'redirect_uri' => array(
      'type'    => 'text',
      'length'  => 1024,
      'fixed'   => false,
      'notnull' => false,
      'default' => ''
    ),
    'consent_message' => array(
      'type'    => 'text',
      'length'  => 4000,
      'fixed'   => false,
      'notnull' => false,
      'default' => ''
    ),
    'client_credentials_userid' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true,
      'default' => -1
    ),
    'grant_client_credentials' => array(
      'type'    => 'boolean',
      'default' => false
    ),
    'grant_authorization_code' => array(
      'type'    => 'boolean',
      'default' => false
    ),
    'grant_implicit' => array(
      'type'    => 'boolean',
      'default' => false
    ),
    'grant_resource_owner' => array(
      'type'    => 'boolean',
      'default' => false
    ),
    'refresh_authorization_code' => array(
      'type'    => 'boolean',
      'default' => false
    ),
    'refresh_resource_owner' => array(
      'type'    => 'boolean',
      'default' => false
    ),
    'ips' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'description' => array(
      'type'    => 'text',
      'length'  => 4000,
      'fixed'   => false,
      'notnull' => false,
      'default' => ''
    )
  );
  $ilDB->createTable('ui_uihk_rest_client', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_client', array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_client CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #3: Created ui_uihk_rest_client.');
?>
<#4>
<?php
  $api_key         = 'apollon';
  $api_secret      = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, 10);
  $description     = 'oAuth2 Admin-Panel Client';

  $ilDB->insert('ui_uihk_rest_client', array(
    'api_key'                   => array('text',    $api_key),
    'api_secret'                => array('text',    $api_secret),
    'grant_resource_owner'      => array('boolean', true),
    'description'               => array('text',    $description)
  ));
  // TODO: Store into admin-panel config

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #4: Filled ui_uihk_rest_client.');
?>
<#5>
<?php
  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'api_id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'user_id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    )
  );
  $ilDB->createTable('ui_uihk_rest_user', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_user', array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_user CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #5: Created ui_uihk_rest_user.');
?>
<#6>
<?php
  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'api_id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'pattern' => array(
      'type'    => 'text',
      'length'  => 512,
      'fixed'   => false,
      'notnull' => false
    ),
    'verb' => array(
      'type'    => 'text',
      'length'  => 16,
      'fixed'   => false,
      'notnull' => false
    )
  );
  $ilDB->createTable('ui_uihk_rest_perm', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_perm', array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_perm CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #6: Created ui_uihk_rest_perm.');
?>
<#7>
<?php
  $sql    = 'SELECT id FROM ui_uihk_rest_client WHERE api_key = \'apollon\'';
  $query  = $ilDB->query($sql);
  $row    = $ilDB->fetchAssoc($query);
  $id     = (isset($row['id'])) ? intval($row['id']) : 1;

  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clients'),
    'verb'    => array('text', 'GET')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clients/:id'),
    'verb'    => array('text', 'PUT')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clients/:id'),
    'verb'    => array('text', 'DELETE')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clients/'),
    'verb'    => array('text', 'POST')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/routes'),
    'verb'    => array('text', 'GET')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clientpermissions'),
    'verb'    => array('text', 'GET')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clientpermissions/:id'),
    'verb'    => array('text', 'DELETE')
  ));
  $ilDB->insert('ui_uihk_rest_perm', array(
    'api_id'  => array('integer', $id),
    'pattern' => array('text', '/clientpermissions/'),
    'verb'    => array('text', 'POST')
  ));

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #7: Filled ui_uihk_rest_perm.');
?>
<#8>
<?php
  global $ilLog;

  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'user_id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'api_id' => array(
      'type'    => 'text',
      'length'  => 128,
      'fixed'   => false,
      'notnull' => false
    ),
    'token' => array(
      'type'    => 'text',
      'length'  => 1024,
      'fixed'   => false,
      'notnull' => false
    ),
    'last_refresh'  => array(
      'type'    => 'timestamp'
    ),
    'created' => array(
      'type'    => 'timestamp'
    ),
    'refreshes' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    )
  );
  $ilDB->createTable('ui_uihk_rest_refresh', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_refresh', array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_refresh CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #8: Created ui_uihk_rest_refresh.');
?>
<#9>
<?php
  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'token' => array(
      'type'    => 'text',
      'length'  => 512,
      'fixed'   => false,
      'notnull' => false
    ),
    'expires'  => array(
      'type'    => 'timestamp'
    ),
  );
  $ilDB->createTable('ui_uihk_rest_authcode', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_authcode',  array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_authcode CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #9: Created ui_uihk_rest_authcode.');
?>
<#10>
<?php
  $fields = array(
    'id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'user_id' => array(
      'type'    => 'integer',
      'length'  => 4,
      'notnull' => true
    ),
    'client_challenge' => array(
      'type'    => 'text',
      'length'  => 25,
      'notnull' => true
    ),
    'server_challenge' => array(
      'type'    => 'text',
      'length'  => 25,
      'notnull' => true
    )
  );
  $ilDB->createTable('ui_uihk_rest_challenge', $fields, true);

  $ilDB->addPrimaryKey('ui_uihk_rest_challenge',  array('id'));
  $ilDB->manipulate('ALTER TABLE ui_uihk_rest_challenge CHANGE id id INT NOT NULL AUTO_INCREMENT');

  global $ilLog;
  $ilLog->write('Plugin REST -> DB-Update #10: Created ui_uihk_rest_challenge.');
?>
