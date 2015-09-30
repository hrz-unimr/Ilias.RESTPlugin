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
class MyCoursesAndGroups {
  /**
   *
   */
  public static function get($accessToken) {
    // Extract user name
    $userId       = $accessToken->getUserId();

    // Load ILIAS user
    $ilUser = Libs\RESTLib::loadIlUser($userId);

    // Fetch groups of user
    $grps = \ilUtil::_getObjectsByOperations('grp', 'visible,read', $userId);
    $crss = \ilUtil::_getObjectsByOperations('crs', 'visible,read', $usr_id);

    // Return groups & courses
    return array_merge($grps, $crss);
  }
}
