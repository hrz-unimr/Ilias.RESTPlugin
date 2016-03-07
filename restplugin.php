<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */


// Include the RESTController application
$appDirectory = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST/RESTController/';
require_once($appDirectory . 'app.php');

// Register the RESTController Class-AutoLoader
\RESTController\RESTController::registerAutoloader();

// Instantate and run the RESTController application
$restController = new \RESTController\RESTController($appDirectory);
$restController->run();
