<?php
// This is a little script to work directly with the application logic of ilias.
// This tool might be useful to speed up (rest api) development.
include_once('Services/Context/classes/class.ilContext.php');


ilContext::init(ilContext::CONTEXT_UNITTEST);

include_once('Services/Init/classes/class.ilInitialisation.php');
$ilInit = new ilInitialisation();
$GLOBALS['ilInit'] = $ilInit;
$ilInit->initILIAS();


require_once('Services/User/classes/class.ilObjUser.php');

setUserContext("root");
// ------------------------------------------------------------------------
echo "<h3>[welcome to 'direct']</h3>";
// ------------------------------------------------------------------------
$id = $ilUser->id;
$usrObj = ilObjectFactory::getInstanceByObjId($id);
$usr_data = array();
$usr_data['firstname'] = $usrObj->firstname;
$usr_data['lastname'] = $usrObj->lastname;
$usr_data['login'] = $usrObj->login;
var_dump($usr_data);
// ------------------------------------------------------------------------
// User Ops
require_once('Services/Utilities/classes/class.ilUtil.php');
//$list = ilUtil::_getObjectsByOperations("usr","visible",$id); // returns ref_ids
//$list = ilUtil::_getObjectsByOperations("crs","visible,read",$id);
$dummy = "";
//$list = ilObjUser::_getAllUserLogins($dummy);
//$list = ilObjUser::_getAllUserData(array('firstname','login','email'),1);
//var_dump($list);

function setUserContext($login) {
    global $ilias, $ilInit;
    $userId = ilObjUser::_lookupId($login);
    $ilUser = new ilObjUser($userId);
    $ilias->account = $ilUser;
    initGlobal("ilUser", $ilUser);
}

function initGlobal($a_name, $a_class, $a_source_file = null) {
    if($a_source_file)
    {
        include_once($a_source_file);
        $GLOBALS[$a_name] = new $a_class;
    }
    else
    {
        $GLOBALS[$a_name] = $a_class;
    }
}
