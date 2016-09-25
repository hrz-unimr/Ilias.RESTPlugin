<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */

// Include the RESTController application
require_once('RESTController/RESTController.php');

// Register the RESTController Class-AutoLoader
\RESTController\RESTController::registerAutoloader();

// Ensure all ILIAS includes still work
$directory = strstr($_SERVER['SCRIPT_FILENAME'], 'Customizing', true);
if (is_file('path.conf'))
	$directory = trim(file_get_contents('path.conf'));
chdir($directory);

// Instantate and run the RESTController application
$restCTL = new \RESTController\RESTController($directory);
$restCTL->run();
