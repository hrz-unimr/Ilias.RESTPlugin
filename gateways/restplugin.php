<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST/classes/class.ilRESTInitialisation.php');
$ilInit = new ilRESTInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initIliasREST();//initILIAS();

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST/RESTController/app.php');
$app->run();

?>
