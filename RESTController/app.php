<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and S.Schneider <(schaefer|schneider)@hrz.uni-marburg.de>
 * 2014-2015
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$logWriter = new \Slim\LogWriter(fopen(ILIAS_LOG_DIR.'/restplugin.log', 'a'));

$app->config(array(
    'debug' => true,
    'template.path' => ILIAS_REST_DIR . '/RESTController/views',
    'log.writer' => $logWriter
));

$app->view()->setTemplatesDirectory(ILIAS_REST_DIR . "/RESTController/views");

$app->log->setEnabled(true);
$app->log->setLevel(\Slim\Log::DEBUG);


$app->hook('slim.after.router', function () {
    header_remove('Set-Cookie');
});

$env = $app->environment();
$env['client_id'] = CLIENT_ID;

class ResourceNotFoundException extends Exception {}

////////////////////////////////////////////////////////////////////////////////////
// --------------------------[!! Please do not remove !!]---------------------------
// The following code belongs the the core of the ILIAS REST plugin.
require_once('libs/class.ilRESTLib.php');
require_once('libs/class.ilAuthLib.php');
require_once('libs/class.ilTokenLib.php');
require_once('libs/class.ilRESTResponse.php');
require_once('libs/inc.ilAuthMiddleware.php');
require_once('libs/class.ilRESTSoapAdapter.php');
require_once('core/clients/models/class.ilClientsModel.php');
require_once('libs/class.ilRESTRequest.php');

// --------------------------[!! Please do not remove !!]---------------------------
////////////////////////////////////////////////////////////////////////////////////

$app->log->debug("REST call from ".$_SERVER['REMOTE_ADDR']." at ".date("d/m/Y,H:i:s", time()));


/**
 * Load Core
 */
foreach (glob(realpath(dirname(__FILE__))."/core/*/models/*.php") as $filename)
{
    include_once $filename;
    //$app->log->debug("Loading extension [model] $filename");
}

foreach (glob(realpath(dirname(__FILE__))."/core/*/routes/*.php") as $filename)
{
    include_once $filename;
    //$app->log->debug("Loading extension [route] $filename");
}

// Please add your models and routes to the folders:
// extensions/models and extensions/routes respectively

/**
 * Load Extensions
 */
foreach (glob(realpath(dirname(__FILE__))."/extensions/*/models/*.php") as $filename)
{
    include_once $filename;
    //$app->log->debug("Loading extension [model] $filename");
}

foreach (glob(realpath(dirname(__FILE__))."/extensions/*/routes/*.php") as $filename)
{
    include_once $filename;
    //$app->log->debug("Loading extension [route] $filename");
}

?>
