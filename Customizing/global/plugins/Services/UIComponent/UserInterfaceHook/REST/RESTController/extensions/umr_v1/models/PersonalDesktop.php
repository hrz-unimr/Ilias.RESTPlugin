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
class PersonalDesktop extends Libs\RESTModel {
  /**
   *
   */
  public static function getPersonalDesktop($accessToken) {
    // Extract user name & id
    $userId       = $accessToken->getUserId();

    // Load ILIAS user
    $ilUser = Libs\RESTilias::loadIlUser($userId);
    $items = $ilUser->getDesktopItems();

    $result = array();
    foreach ($items as $item)
      $result[] = intval($item['ref_id']);

    return array(
      'ref_ids' => $result
    );
  }
}
