<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 *
 */
class Contacts {
  /**
   *
   */
  public static function getContacts($accessToken) {
    // Extract user name
    $userId       = $accessToken->getUserId();

    // Fetch contacts of user
    require_once("Services/Contact/classes/class.ilAddressbook.php");
    $adressbook = new \ilAddressbook($userId);
    $contacts = $adressbook->getEntries();

    // Add user-info (filtered) to each contact
    $result = array();
    foreach ($contacts as $contact) {
      $loginId  = Libs\RESTLib::getUserIdFromUserName($contact['login']);
      $userInfo = UserInfo::getUserInfo($loginId);

      $result[] = array_merge(
        ($userInfo) ?: array(),
        array(contactId => $contact['addr_id'])
      );
    }

    // Return contacts
    return $result;
  }
}
