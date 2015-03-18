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

$ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");

// Run the slim-application
require_once($ilRESTPlugin->getDirectory() . "/RESTController/slimnode.php");
$app->run();

