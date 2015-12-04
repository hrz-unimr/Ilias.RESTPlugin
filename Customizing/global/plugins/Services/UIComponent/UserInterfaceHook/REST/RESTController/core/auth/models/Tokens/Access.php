<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\auth\Tokens;


/**
 * Class: Access (-Token)
 *  Represents an actual Access-Token.
 */
class Access extends Base {
  // Will be used to validate type of token
  protected static $class = 'access';
}
