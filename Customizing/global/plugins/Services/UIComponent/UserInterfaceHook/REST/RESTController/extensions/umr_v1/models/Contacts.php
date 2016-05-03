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
class Contacts extends Libs\RESTModel {
  // Allow to re-use status-messages and status-codes
  const MSG_NO_CONTACT_ID  = 'Contact with contactId %s does not exist.';
  const MSG_ALL_FAILED      = 'All requests failed, see data-entry for more information.';
  const ID_NO_CONTACT_ID   = 'RESTController\\extensions\\umr_v1\\Contacts::ID_NO_CONTACT_ID';
  const ID_ALL_FAILED       = 'RESTController\\extensions\\umr_v1\\Contacts::ID_ALL_FAILED';


  /**
   *
   */
  protected function getContactInfo($contact) {
    $loginId  = Libs\RESTilias::getUserId($contact['login']);

    try {
      $userInfo = UserInfo::getUserInfo($loginId);

      return array_merge(
        ($userInfo) ?: array(),
        array(
          contact_id     => intval($contact['addr_id']),
          contact_email  => $contact['email']
        )
      );
    }
    // getUserInfo failed, use fallback data
    catch (Exceptions\UserInfo $e) {
      return array(
        id            => intval($loginId),
        firstname     => $contact['firstname'],
        lastname      => $contact['lastname'],
        contact_id    => intval($contact['addr_id']),
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
    require_once('Services/Contact/classes/class.ilAddressbook.php');
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
    require_once('Services/Contact/classes/class.ilAddressbook.php');
    $adressbook = new \ilAddressbook($userId);

    // Fetch each contact from list
    $result     = array();
    $noSuccess  = true;
    foreach($contactIds as $contactId) {
      $contact            = $adressbook->getEntry($contactId);

      // Contact was found
      if ($contact && $contact['login']) {
        $result[$contactId] = self::getContactInfo($contact);
        $noSuccess          = false;
      }
      // No contact with given id
      else {
        $result[$contactId]               = Libs\RESTResponse::responseObject(sprintf(self::MSG_NO_CONTACT_ID, $contactId), self::ID_NO_CONTACT_ID);
        $result[$contactId]['contact_id'] = $contactId;
      }
    }

    // If every request failed, throw instead
    if ($noSuccess && count($contactIds) > 0)
      throw new Exceptions\Contacts(self::MSG_ALL_FAILED, self::ID_ALL_FAILED, $result);

    return $result;
  }
}
