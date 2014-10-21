<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
if (isset($_GET['client_id'])) {  // workaround see https://github.com/eqsoft/RESTPlugin/issues/1
    $oauth_client_id = $_GET['client_id'];
    unset($_GET['client_id']);
}

include_once './Services/Context/classes/class.ilContext.php';
ilContext::init(ilContext::CONTEXT_UNITTEST);

include_once './Services/Init/classes/class.ilInitialisation.php';
$ilInit = new ilInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initILIAS();

if (isset($oauth_client_id)) { // workaround see https://github.com/eqsoft/RESTPlugin/issues/1
    $_GET['client_id'] = $oauth_client_id;
}
require_once("Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Rest/RESTController/slimnode.php");
$app->run();

?>
