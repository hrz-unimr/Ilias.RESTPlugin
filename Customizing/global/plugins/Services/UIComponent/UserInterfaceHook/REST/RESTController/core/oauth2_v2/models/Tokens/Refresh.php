<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2\Tokens;


/**
 * Class: Refresh (-Token)
 *  Represents an actual Refresh-Token.
 */
class Refresh extends Base {
  // Will be used to validate type of token (and size of random-string in token)
  protected static $class   = 'refresh';
  protected static $entropy = 30;
}
