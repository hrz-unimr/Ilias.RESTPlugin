<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs            as Libs;
use \RESTController\libs\Exceptions as LibExceptions;


/**
 * Class: System
 *
 */
class System extends Libs\RESTModel {
  /**
   *
   */
  static public function LoginStats($userId) {
    // Import user-object class
    include_once('Services/User/classes/class.ilObjUser.php');

    // Fetch user-object
    $userObj = new \ilObjUser($userId);

    // Fill result-object
    $userData = array();
    $userData['ext_account']              = $userObj->getExternalAccount();
    $userData['create_date']              = $userObj->getCreateDate();
    $userData['last_update']              = $userObj->getLastUpdate();
    $userData['approve_date']             = $userObj->getApproveDate();
    $userData['agree_date']               = $userObj->getAgreeDate();
    $userData['last_login']               = $userObj->getLastLogin();
    $userData['active']                   = $userObj->getActive();
    if (!$userObj->getActive())
      $userData['inactivation_date']      = $userObj->getInactivationDate();
    if (!$userObj->getTimeLimitUnlimited()) {
      $userData['time_limit_from']          = $userObj->getTimeLimitFrom();
      $userData['time_limit_until']         = $userObj->getTimeLimitUntil();
    }

    // Return result object
    return self::ParseUserData($userData);
  }


  /**
   *
   */
  protected static function ParseUserData($userData) {
    // Parse output to be a bit better
    $parsedUserData = array();
    foreach ($userData as $key => $value) {
      switch ($key) {
        // Convert time-dates (to ISO 8601)
        case 'create_date':
        case 'last_update':
        case 'approve_date':
        case 'agree_date':
        case 'last_login':
        case 'inactivation_date':
          if (isset($value)) {
            $realDate = new \ilDateTime($value, IL_CAL_DATETIME);
            $parsedUserData[$key] = $realDate->get(IL_CAL_ISO_8601);
          }
          else
            $parsedUserData[$key] = false;
        break;
        case 'time_limit_from':
        case 'time_limit_until':
          if (isset($value)) {
            $parsedUserData[$key] = $value;
            $realDate = new \ilDateTime($value, IL_CAL_UNIX);
            $parsedUserData[$key] = $realDate->get(IL_CAL_ISO_8601);
          }
          else
            $parsedUserData[$key] = false;
        break;
        // Convert to real boolean
        case 'active':
          $parsedUserData[$key] = (intval($value) === 1);
        break;
        // Convert empty values to false
        case 'ext_account':
          if (!isset($value) || strlen($value) == 0)
            $parsedUserData[$key] = false;
          else
            $parsedUserData[$key] = $value;
        // Only keep non-null values by default
        default:
          if (isset($value))
            $parsedUserData[$key] = $value;
        break;
      }
    }

    // Return parsed output
    return $parsedUserData;
  }
}
