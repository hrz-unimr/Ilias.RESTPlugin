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
class MyCoursesAndGroups extends Libs\RESTModel {
  /**
   *
   */
  public static function getMyCoursesAndGroups($accessToken) {
    // Extract user name
    $userId       = $accessToken->getUserId();

    // Load ILIAS user
    $ilUser = Libs\RESTilias::loadIlUser($userId);

    // Fetch groups and courses of user
    $grps = \ilUtil::_getObjectsByOperations('grp', 'visible,read', $userId);
    $grps = array_map(function($value) { return intval($value); }, $grps);
    $crss = \ilUtil::_getObjectsByOperations('crs', 'visible,read', $userId);
    $crss = array_map(function($value) { return intval($value); }, $crss);

    // Return groups & courses
    return array(
      'group_ids'  => $grps,
      'course_ids' => $crss
    );
  }
}
