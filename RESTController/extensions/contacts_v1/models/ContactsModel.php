<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\contacts_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTLib, \RESTController\libs\AuthLib, \RESTController\libs\TokenLib;
use \RESTController\libs\RESTRequest, \RESTController\libs\RESTResponse;

use \ilAddressbook;


require_once("./Services/Database/classes/class.ilAuthContainerMDB2.php");
require_once("./Services/User/classes/class.ilObjUser.php");
require_once("Services/Contact/classes/class.ilAddressbook.php");


class ContactsModel
{

    /**
     * Retrieves contacts for a given user listed under "My Contacts".
     * @param $user_id
     */
    function getMyContacts($user_id)
    {
        RESTLib::loadIlUser();
        global    $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        RESTLib::initAccessHandling();

        $abook = new ilAddressbook($ilUser->getId());
        $entries = $abook->getEntries();

        return $entries;
    }

}