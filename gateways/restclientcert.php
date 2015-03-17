<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

// workaround see https://github.com/eqsoft/RESTPlugin/issues/1
if (isset($_GET['client_id'])) {
    $oauth_client_id = $_GET['client_id'];
    unset($_GET['client_id']);
}

// Tell ILIAS which context to load
include_once './Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_UNITTEST);

// Initialize ILIAS
include_once './Services/Init/classes/class.ilInitialisation.php';
$ilInit = new ilInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initILIAS();

// workaround see https://github.com/eqsoft/RESTPlugin/issues/1
if (isset($oauth_client_id)) { 
    $_GET['client_id'] = $oauth_client_id;
}

// This allows us to parse the ilias.ini.php, such that we may find the location
// of the RESTPlugin
require_once("./Services/Init/classes/class.ilIniFile.php");
$ilIliasIniFile = new ilIniFile("./ilias.ini.php");				
$ilIliasIniFile->read();
define("ILIAS_REST_DIR", $ilIliasIniFile->readVariable("rest", "path"));
define("ILIAS_REST_URL", $ilIliasIniFile->readVariable("server", "http_path") . "/" . ILIAS_REST_DIR);

// Run the slim-application
require_once(ILIAS_REST_DIR . "/RESTController/slimnode.php");
$app->run();

