<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and S.Schneider <(schaefer|schneider)@hrz.uni-marburg.de>
 * 2014-2015
 */
require 'Slim/Slim.php';

// Create bew slim instance (uses autoloader to load other includes)
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

// Enable debugging (to own file or ilias if not possible)
$app->config('debug', true);
if (is_writable(ILIAS_LOG_DIR.'/restplugin.log')) {
    $logWriter = new \Slim\LogWriter(fopen(ILIAS_LOG_DIR.'/restplugin.log', 'a'));
    $app->config('log.writer', $logWriter);
}
else {
    global $ilLog;
    $ilLog->write('Plugin REST -> Warning: Log file <' . ILIAS_LOG_DIR . '/restplugin.log> is not writeable!');
    $app->config('log.writer', $ilLog);
}
$app->log->setEnabled(true);
$app->log->setLevel(\Slim\Log::DEBUG);

// Set template for current view and new views
$path = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")->getDirectory()."/RESTController/";
$app->config('templates.path', $path);
$app->view()->setTemplatesDirectory($path);

// REST doesn't use cookies
$app->hook('slim.after.router', function () {
    header_remove('Set-Cookie');
});

// Global information that should be available to all routes/models
$env = $app->environment();
$env['client_id'] = CLIENT_ID;

// --------------------------[!! Please do not remove !!]---------------------------
// The following code belongs the the core of the ILIAS REST plugin.
require_once('libs/inc.ilAuthMiddleware.php');
require_once('libs/class.ilRESTLib.php');
require_once('libs/class.ilAuthLib.php');
require_once('libs/class.ilTokenLib.php');
require_once('libs/class.ilRESTResponse.php');
require_once('libs/class.ilRESTRequest.php');
require_once('libs/class.ilRESTSoapAdapter.php');
// --------------------------[!! Please do not remove !!]---------------------------

// Log some debug usage information
$app->log->debug("REST call from ".$_SERVER['REMOTE_ADDR']." at ".date("d/m/Y,H:i:s", time()));

// Load core models & routes
foreach (glob(realpath(__DIR__)."/core/*/models/*.php") as $filename) {
    include_once $filename;
}
foreach (glob(realpath(__DIR__)."/core/*/routes/*.php") as $filename) {
    include_once $filename;
}

// Load extension models & routes
foreach (glob(realpath(__DIR__)."/extensions/*/models/*.php") as $filename) {
    include_once $filename;
}
foreach (glob(realpath(__DIR__)."/extensions/*/routes/*.php") as $filename) {
    include_once $filename;
}
