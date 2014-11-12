<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and S.Schneider <(schaefer|schneider)@hrz.uni-marburg.de>
 * 2014
 */
require 'Slim/Slim.php';

define('REST_PLUGIN_DIR', dirname($_SERVER['SCRIPT_NAME']).'/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Rest');

// register Slim auto-loader
\Slim\Slim::registerAutoloader();

// initialize app
$app = new \Slim\Slim();

$logWriter = new \Slim\LogWriter(fopen(ILIAS_LOG_DIR.'/restplugin.log', 'a'));

$app->config(array(
    'debug' => true,
    'template.path' => REST_PLUGIN_DIR.'/RESTController/views',
    'log.writer' => $logWriter
));

$app->view()->setTemplatesDirectory("./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Rest/RESTController/views");

$app->log->setEnabled(true);
$app->log->setLevel(\Slim\Log::DEBUG);


$app->hook('slim.after.router', function () {
    header_remove('Set-Cookie');
});


class ResourceNotFoundException extends Exception {}

////////////////////////////////////////////////////////////////////////////////////
// --------------------------[!! Please do not remove !!]---------------------------
// The following code belongs the the core of the ILIAS REST plugin.
require_once('libs/class.ilRestLib.php');
require_once('libs/class.ilAuthLib.php');
require_once('libs/class.ilTokenLib.php');
require_once('libs/class.RestResponse.php');
require_once('libs/inc.ilAuthMiddleware.php');
require_once('libs/class.ilRestSoapAdapter.php');
require_once('core/clients/models/class.ilClientsModel.php');

/**
 * OAuth2 authorization and authentication mechanism
 */
include_once('core/auth/routes/inc.ilOAuth2Routes.php');
/**
 * ILIAS REST clients administration component.
 */
include_once('core/clients/routes/inc.ilClientsRoutes.php');

// --------------------------[!! Please do not remove !!]---------------------------
////////////////////////////////////////////////////////////////////////////////////

// Please add your models and routes to the folders:
// extensions/models and extensions/routes respectively

/**
 * Load Extensions
 */

foreach (glob(realpath(dirname(__FILE__))."/extensions/*/models/*.php") as $filename)
{
    include_once $filename;
    $app->log->debug("Loading extension [model] $filename");
}

foreach (glob(realpath(dirname(__FILE__))."/extensions/*/routes/*.php") as $filename)
{
    include_once $filename;
    $app->log->debug("Loading extension [route] $filename");
}

?>
