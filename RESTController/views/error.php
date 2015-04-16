<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */

 
echo '{
    "msg": "An error occured while handling this route!",
    "data": {
        "message": ' . json_encode ($error->getMessage()) . ',
        "code": ' . json_encode ($error->getCode()) . ',
        "file": ' . json_encode ($error->getFile()) . ',
        "line": ' . json_encode ($error->getLine()) . ',
        "trace": ' . json_encode($error->getTraceAsString()) . ',
        "full": ' . json_encode($error->__tostring()) . '
    }
}';