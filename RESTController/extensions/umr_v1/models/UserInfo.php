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
class UserInfo extends Libs\RESTModel {
  // Allow to re-use status-messages and status-codes
  const MSG_INVALID_USER  = 'Request failed, requesting invalid user.';
  const MSG_INVALID_TYPE  = 'Request failed, malformed request.';
  const ID_INVALID_USER   = 'RESTController\\extensions\\umr_v1\\UserInfo::ID_INVALID_USER';
  const ID_INVALID_TYPE   = 'RESTController\\extensions\\umr_v1\\UserInfo::ID_INVALID_TYPE';


  /**
   *
   */
  protected static function allowedToView($usingToken, $ilObjUser, $prefName = null) {
    global $ilSetting;

    // User with his (own) token or values without profile-setting allowed by default
    if ($usingToken || $prefName == null)
      return true;

    // Check if profile and value in profile is enabled
    $valueEnabled   = $ilObjUser->getPref($prefName);
    $profileEnabled = $ilObjUser->getPref('public_profile') == 'y' || ($ilObjUser->getPref('public_profile') == 'g' && $ilSetting->get('enable_global_profiles'));

    return ($profileEnabled && $valueEnabled);
  }


  /**
   *
   */
  protected static function getAvatar($ilObjUser) {
    // Build path to uploaded file
    $webspaceDir  = \ilUtil::getWebspaceDir('user');
		$checkDir     = \ilUtil::getWebspaceDir();
		$imageFile    = $webspaceDir . '/usr_images/' . $ilObjUser->getPref('profile_image') . '?dummy=' . rand(1, 999999);
		$checkFile    = $checkDir    . '/usr_images/' . $ilObjUser->getPref('profile_image');

    // Use ILIAS generated file if not found
		if (!@is_file($checkFile))
			$imageFile = \ilObjUser::_getPersonalPicturePath($ilObjUser->getId(), 'small', false, true);

    return $imageFile;
  }


  /**
   *
   */
  protected static function getUserInfo_Array($userId, $usingToken) {
    // Fetch user-information
    $ilObj = \ilObjectFactory::getInstanceByObjId($userId);

    // Check if clients request is allowed
    if (!$ilObj || !is_a($ilObj, 'ilObjUser'))
      throw new Exceptions\UserInfo(self::MSG_INVALID_USER, self::ID_INVALID_USER);

    // Build user information from ilObjUser
    $userInfo = array(
      firstname                       =>
        $ilObj->firstname,
      lastname                        =>
        $ilObj->lastname,
      referral_comment                =>
        $ilObj->referral_comment,
      id                              =>
        intval($ilObj->id),
      login                           => ($usingToken) ?
        $ilObj->login                 : null,
      utitle                          => (self::allowedToView($usingToken, $ilObj, 'public_title')) ?
        $ilObj->utitle                : null,
      institution                     => (self::allowedToView($usingToken, $ilObj, 'public_institution')) ?
        $ilObj->institution           : null,
      department                      => (self::allowedToView($usingToken, $ilObj, 'public_department')) ?
        $ilObj->department            : null,
      gender                          => (self::allowedToView($usingToken, $ilObj, 'public_gender')) ?
        $ilObj->gender                : null,
      street                          => (self::allowedToView($usingToken, $ilObj, 'public_street')) ?
        $ilObj->street                : null,
      city                            => (self::allowedToView($usingToken, $ilObj, 'public_city')) ?
        $ilObj->city                  : null,
      zipcode                         => (self::allowedToView($usingToken, $ilObj, 'public_zip')) ?
        $ilObj->zipcode               : null,
      country                         => (self::allowedToView($usingToken, $ilObj, 'public_country')) ?
        $ilObj->country               : null,
      sel_country                     => (self::allowedToView($usingToken, $ilObj, 'public_country')) ?
        $ilObj->sel_country           : null,
      phone_office                    => (self::allowedToView($usingToken, $ilObj, 'public_phone_office')) ?
        $ilObj->phone_office          : null,
      phone_home                      => (self::allowedToView($usingToken, $ilObj, 'public_phone_home')) ?
        $ilObj->phone_home            : null,
      phone_mobile                    => (self::allowedToView($usingToken, $ilObj, 'public_phone_mobile')) ?
        $ilObj->phone_mobile          : null,
      fax                             => (self::allowedToView($usingToken, $ilObj, 'public_fax')) ?
        $ilObj->fax                   : null,
      email                           => (self::allowedToView($usingToken, $ilObj, 'public_email')) ?
        $ilObj->email                 : null,
      hobby                           => (self::allowedToView($usingToken, $ilObj, 'public_hobby')) ?
        $ilObj->hobby                 : null,
      matriculation                   => (self::allowedToView($usingToken, $ilObj, 'public_matriculation')) ?
        $ilObj->matriculation         : null,
      im_icq                          => (self::allowedToView($usingToken, $ilObj, 'public_im_icq')) ?
        $ilObj->im_icq                : null,
      im_yahoo                        => (self::allowedToView($usingToken, $ilObj, 'public_im_yahoo')) ?
        $ilObj->im_yahoo              : null,
      im_msn                          => (self::allowedToView($usingToken, $ilObj, 'public_im_msn')) ?
        $ilObj->im_msn                : null,
      im_aim                          => (self::allowedToView($usingToken, $ilObj, 'public_im_aim')) ?
        $ilObj->im_aim                : null,
      im_skype                        => (self::allowedToView($usingToken, $ilObj, 'public_im_skype')) ?
        $ilObj->im_skype              : null,
      im_jabber                       => (self::allowedToView($usingToken, $ilObj, 'public_im_jabber')) ?
        $ilObj->im_jabber             : null,
      im_voip                         => (self::allowedToView($usingToken, $ilObj, 'public_im_voip')) ?
        $ilObj->im_voip               : null,
      interests_general               => (self::allowedToView($usingToken, $ilObj, 'public_interests_general') && method_exists($ilObj , 'getGeneralInterests')) ?
        $ilObj->getGeneralInterests() : null,
      interests_help_offered          => (self::allowedToView($usingToken, $ilObj, 'public_interests_help_offered') && method_exists($ilObj , 'getOfferingHelp')) ?
        $ilObj->getOfferingHelp()     : null,
      interests_help_looking          => (self::allowedToView($usingToken, $ilObj, 'public_interests_help_looking')  && method_exists($ilObj , 'getLookingForHelp')) ?
        $ilObj->getLookingForHelp()   : null,
      avatar                          => (self::allowedToView($usingToken, $ilObj, 'public_upload')) ?
        self::getAvatar($ilObj)       : null
    );

    // Filter null from array
    $userInfo = array_filter($userInfo, function($value) { return !is_null($value); });

    // Return user-info
    return $userInfo;
  }


  /**
   *
   */
  public static function getUserInfo($userId) {
      // Delegate to actual implementation
      return self::getUserInfo_Array($userId, false);
  }

  /**
   *
   */
  public static function getFullUserInfo($accessToken) {
      // Delegate to actual implementation
      return self::getUserInfo_Array($accessToken->getUserId(), true);
  }
}
