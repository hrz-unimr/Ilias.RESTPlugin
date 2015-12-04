<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\database;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * Class: RESTauthentification (Database-Table)
 *  Abstraction for 'ui_uihk_rest_authcode' database table.
 *  See RESTDatabase class for additional information.
 */
class RESTauthentification extends Libs\RESTDatabase {
  // This three variables contain information about the table layout
  protected static $primaryKey  = 'id';
  protected static $tableName   = 'ui_uihk_rest_authcode';
  protected static $tableKeys   = array(
    'id'    => 'integer',
    'token' => 'text'
  );
}
