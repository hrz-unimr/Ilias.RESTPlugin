<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */

 
// Generate standard message
$errStr = '{
    "msg": "An error occured while handling this route!",
    "data": {
        "message": ' . json_encode(isset($error["message"]) ? $error["message"] : "") . ',
        "code": ' . json_encode(isset($error["code"]) ? $error["code"] : "") . ',
        "file": ' . json_encode(isset($error["file"]) ? $error["file"] : "") . ',
        "line": ' . json_encode(isset($error["line"]) ? $error["line"] : "") . ',
        "trace": ' . json_encode(isset($error["trace"]) ? $error["trace"] : "") . '
    }
}';

// Log error to file
$app->log->debug($errStr);

// Display error
echo $errStr;