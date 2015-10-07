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
  protected function getContactInfo($contact) {
    $loginId  = Libs\RESTLib::getUserIdFromUserName($contact['login']);

    try {
      $userInfo = UserInfo::getUserInfo($loginId);

      return array_merge(
        ($userInfo) ?: array(),
        array(
          contact_id     => $contact['addr_id'],
          contact_email  => $contact['email']
        )
      );
    }
    // getUserInfo failed, use fallback data
    catch (Exceptions\UserInfo $e) {
      return array(
        id            => $loginId,
        firstname     => $contact['firstname'],
        lastname      => $contact['lastname'],
        contact_id    => $contact['addr_id'],
        contact_email => $contact['email']
      );
    }
  }


  /**
   *
   */
  public static function getAllContacts($accessToken) {
    // Extract user name
    $userId       = $accessToken->getUserId();

    // Fetch contacts of user
    require_once("Services/Contact/classes/class.ilAddressbook.php");
    $adressbook = new \ilAddressbook($userId);
    $contacts   = $adressbook->getEntries();

    // Add user-info (filtered) to each contact
    $result = array();
    foreach ($contacts as $contact) {
      $info               = self::getContactInfo($contact);
      $contactId          = $info['contact_id'];
      $result[$contactId] = $info;
    }

    // Return contacts
    return $result;
  }


  /**
   *
   */
  public static function getContacts($accessToken, $contactIds) {
    // Convert to array
    if (!is_array($contactIds))
      $contactIds = array($contactIds);

    // Extract user name
    $userId       = $accessToken->getUserId();

    // Fetch contacts of user
    require_once("Services/Contact/classes/class.ilAddressbook.php");
    $adressbook = new \ilAddressbook($userId);

    // Fetch each contact from list
    $result = array();
    foreach($contactIds as $contactId) {
      $contact            = $adressbook->getEntry($contactId);
      $result[$contactId] = self::getContactInfo($contact);
    }

    return $result;
  }
}
