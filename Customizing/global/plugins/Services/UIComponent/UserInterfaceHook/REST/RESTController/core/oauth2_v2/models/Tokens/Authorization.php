<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\core\oauth2_v2\Tokens;


/**
 * Class: Authorization (-Token)
 *  Represents an actual Authorization-Token. (Authorization-Code flow)
 */
class Authorization extends Base {
  // Will be used to validate type of token
  protected static $class = 'authorization';
}
