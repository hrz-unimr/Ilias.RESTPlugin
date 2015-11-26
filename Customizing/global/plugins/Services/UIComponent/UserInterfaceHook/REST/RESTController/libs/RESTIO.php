<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Class: RESTIO
 *  Base class for all 'io models'. IO classes are allowed to
 *  parse input parameters and send responses via SLIM, but
 *  should contain as little as possible actual "program-logic"
 *  unrelated to non-io tasks.
 *  It should make I/O logic reusable while 'non-io models'
 *  make program-logic reuseable without parsing  input or
 *  sending responses.
 */
class RESTIO {
}
