<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Rest/classes/class.ilRestInitialisation.php');
$ilInit = new ilRestInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initIliasREST();//initILIAS();

require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Rest/RESTController/app.php');
$app->run();

?>
