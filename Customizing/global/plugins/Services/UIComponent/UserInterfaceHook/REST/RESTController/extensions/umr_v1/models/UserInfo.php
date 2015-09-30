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
class UserInfo {
  // Allow to re-use status-messages and status-codes
  const MSG_INVALID_USER  = 'Request failed, requesting invalid user.';
  const ID_INVALID_USER   = 'RESTController\\core\\extensions\\umr_v1\\UserInfo::ID_INVALID_USER';


  /**
   *
   */
  public static function getUserInfo($accessToken) {
    // Extract user name & id
    $userName     = $accessToken->getUserName();
    $userId       = $accessToken->getUserId();

    // Fetch user-information
    $ilObj = \ilObjectFactory::getInstanceByObjId($userId);

    // Check if clients request is allowed
    if (!$ilObj || get_class($ilObj) != 'ilObjUser' || $ilObj->login != $userName)
      throw new Exceptions\UserInfo(self::MSG_INVALID_USER, self::ID_INVALID_USER);

    // Build user information from ilObjUser
    $userInfo = array(
      id                      => $ilObj->id,
      login                   => $ilObj->login,
      firstname               => $ilObj->firstname,
      lastname                => $ilObj->lastname,
      utitle                  => $ilObj->utitle,
      institution             => $ilObj->institution,
      department              => $ilObj->department,
      gender                  => $ilObj->gender,
      street                  => $ilObj->street,
      city                    => $ilObj->city,
      zipcode                 => $ilObj->zipcode,
      country                 => $ilObj->country,
      sel_country             => $ilObj->sel_country,
      phone_office            => $ilObj->phone_office,
      phone_home              => $ilObj->phone_home,
      phone_mobile            => $ilObj->phone_mobile,
      fax                     => $ilObj->fax,
      email                   => $ilObj->email,
      hobby                   => $ilObj->hobby,
      matriculation           => $ilObj->matriculation,
      referral_comment        => $ilObj->referral_comment,
      im_icq                  => $ilObj->im_icq,
      im_yahoo                => $ilObj->im_yahoo,
      im_msn                  => $ilObj->im_msn,
      im_aim                  => $ilObj->im_aim,
      im_skype                => $ilObj->im_skype,
      im_jabber               => $ilObj->im_jabber,
      im_voip                 => $ilObj->im_voip,
      delicious               => $ilObj->delicious,
      interests_general       => $ilObj->getGeneralInterests(),
      interests_help_offered  => $ilObj->getOfferingHelp(),
      interests_help_looking  => $ilObj->getLookingForHelp(),
      latitude                => $ilObj->latitude,
      longitude               => $ilObj->longitude
    );
    return $userInfo;
  }
}
