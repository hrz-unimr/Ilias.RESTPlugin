<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 
 
// Include SLIM-Framework
require_once('Slim/Slim.php');


// Create bew slim instance (uses autoloader to load other includes)
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

// Use Custom Router
$app->container->singleton('router', function ($c) {
    require_once('libs\RESTRouter.php');
    return new RESTRouter();
});

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

$app->error(function (\Exception $e) use ($app) {
    $app->render('views/error.php');
});
$app->notFound(function () use ($app) {
    $app->render('views/404.php');
});


// Global information that should be available to all routes/models
$env = $app->environment();
$env['client_id'] = CLIENT_ID;


// --------------------------[!! Please do not remove !!]---------------------------
require_once('libs/RESTLib.php');
require_once('libs/RESTResponse.php');
require_once('libs/RESTRequest.php');
require_once('libs/RESTSoapAdapter.php');
require_once('libs/AuthLib.php');
require_once('libs/TokenLib.php');
require_once('libs/AuthMiddleware.php');
// --------------------------[!! Please do not remove !!]---------------------------


// Log some debug usage information
$app->log->debug("REST call from ".$_SERVER['REMOTE_ADDR']." at ".date("d/m/Y,H:i:s", time()));


// Load core models & routes
foreach (glob(realpath(__DIR__)."/core/*/models/*.php") as $filename) 
    include_once($filename);
foreach (glob(realpath(__DIR__)."/core/*/routes/*.php") as $filename) 
    include_once($filename);

// Load extension models & routes
foreach (glob(realpath(__DIR__)."/extensions/*/models/*.php") as $filename) 
    include_once($filename);
foreach (glob(realpath(__DIR__)."/extensions/*/routes/*.php") as $filename) 
    include_once($filename);
