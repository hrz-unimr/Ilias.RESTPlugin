<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\users_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs            as Libs;
use \RESTController\libs\Exceptions as LibExceptions;


// Helper class to trick bad ILIAD implementation
class ilAuthFake {
  public function getUsername() {
    global $ilUser;
    return $ilUser->getLogin();
  }
}


/**
 * Class: Admin
 *  This class managed administative user-management and support local user admins.
 *
 * Definition of USER-DATA:
 *  ref_id <Int> ILIAS internal ref-id of category in which local user-account given by <user_id> exists (omit for global user-accounts)
 *  id <Int> - ILIAS internal user-id of user to update user-data for
 *  login <String> - User login
 *  auth_mode <String> - Authentication-Mode for user (@See ilAuthUtils::_getAuthModeName(...))
 *  client_ip <String> - Restrict user to given ip
 *  active <Bool> - Active or deactive user account
 *  time_limit_from <String/Int> - Set time limit from-which user should be able to use account (Unix-Time or ISO 6801)
 *  time_limit_until <String/Int> - Set time limit until-which user should be able to use account (Unix-Time or ISO 6801)
 *  time_limit_unlimited <Bool> - Set account to unlimited (otherwise time_limit_from & time_limit_until are active)
 *  interests_general <Array<String>> - General interrest fields of user
 *  interests_help_offered <Array<String>> - Help offered fields of user
 *  interests_help_looking <Array<String>> - Help looking fields of user
 *  latitude <Number> - GPS-Location of user, latitude
 *  longitude <Number> - GPS-Location of user, longitude
 *  zoom <Int> - Default Zoom-Level for maps
 *  udf <Array<Mixed> - List of user-defined key => value pairs (@See: Administration -> User Administration -> User-Defined Fields)
 *  language <String> - Current language of user (@See ilLanguage->getInstalledLanguages())
 *  birthday <String> - The users birthday (Only date-section of ISO 6801)
 *  gender <m/f> - Gender of user (can also be Male/Female)
 *  institution <String> - Institution of user
 *  department <String> - Department of user
 *  street <String> - Street of user
 *  city <String> - City of user
 *  zipcode <String> - City-Zipcode of user
 *  country <String> - Country of user (Free-text)
 *  sel_country <String> - Country of user (Via selection) (@See ilCountry::getCountryCodes())
 *  phone_office <String> - Office phone-number of user
 *  phone_home <String> - Home phone-number of user
 *  phone_mobile <String> - Mobile phone-number of user
 *  fax <String> - FAX-Number of user
 *  matriculation <String> - Matriculation (ID) of user
 *  hobby <String> - Hobby-text of user
 *  referral_comment <String> - Referral comment of user
 *  delicious <String> - Delicious account of user
 *  email <String> - Email-Address of user
 *  im_icq <String> - Instant-Messenging ICQ-Account of user
 *  im_yahoo <String> - Instant-Messenging Yahoo-Account of user
 *  im_msn <String> - Instant-Messenging MSN-Account of user
 *  im_aim <String> - Instant-Messenging AIM-Account of user
 *  im_skype <String> - Instant-Messenging Skype-Account of user
 *  im_jabber <String> - Instant-Messenging Jabber-Account of user
 *  im_voip <String> - Instant-Messenging VOIP-Number of user
 *  title <String> - Title of user
 *  firstname <String> - Firstname of user
 *  lastname <String> - Lastname of user
 *  hits_per_page <Int> - Hits-Per-Page setting of user
 *  show_users_online <Bool> - Show-Users-Online setting of user
 *  hide_own_online_status <Bool> - Hide-Online-Status setting of user
 *  skin_style <String> - Skin & Style setting of user, needs to be in Format 'SKIN:STYLE' (colon-delimited)
 *  session_reminder_enabled <Bool> - Session-Reminder setting of user
 *  passwd <String> - Plain-Text password of user
 *  ext_account <String> - External account name of user
 *  disk_quota <Number> - Global disk-quota for user (courses, groups, files, etc)
 *  wsp_disk_quota <Number> - Personal workspace disk-quota for user
 *  userfile <String> - BASE64-Encoded JPG image (Example: data:image/jpeg;base64,<BASE-64-PAYLOAD>, without <>)
 *  roles <Array<Int>> - A list of ilias roles (numeric-ids) of roles to assign the user to
 */
class Admin extends Libs\RESTModel {
  // Allow to re-use status messages and codes
  const MSG_USER_PICTURE_EMPTY = 'User picture does not contain any base64-encoded data.';
  const ID_USER_PICTURE_EMPTY  = 'RESTController\\extensions\\users_v2\\Admin::ID_USER_PICTURE_EMPTY';
  const MSG_INVALID_MODE       = 'Invalid mode, must either be \'create\' or \'update\'.';
  const ID_INVALID_MODE        = 'RESTController\\extensions\\users_v2\\Admin::ID_INVALID_MODE';
  const MSG_DELETE_SELF        = 'Not allowed to delete own user-account.';
  const ID_DELETE_SELF         = 'RESTController\\extensions\\users_v2\\Admin::ID_DELETE_SELF';
  const MSG_IMAGE_VIRUS        = 'Could not upload user profile image, virus detected. User was otherwise created/updated.';
  const ID_IMAGE_VIRUS         = 'RESTController\\extensions\\users_v2\\Admin::ID_IMAGE_VIRUS';


  // Redefine global constants as local constants
  const DEFAULT_ROLE_ID   = 4;
  const SYSTEM_ROLE_ID    = SYSTEM_ROLE_ID;
  const ANONYMOUS_ROLE_ID = ANONYMOUS_ROLE_ID;
  const ROLE_FOLDER_ID    = ROLE_FOLDER_ID;
  const USER_FOLDER_ID    = USER_FOLDER_ID;


  // Pseudo-Enum values to control user-data storage behaviour (create or update existing)
  const MODE_CREATE = 'create';
  const MODE_UPDATE = 'update';


  /**
   * Function: AddInputDefaults($userData)
   *  Adds default values to userData if manditory field is missing and has a default.
   *
   * Parameters:
   *  userData <UserData> - User-Data array to be manipulated
   *
   * Return:
   *  <UserData> -  Modified User-Data
   */
  protected static function AddInputDefaults($userData) {
    global $ilUser, $ilSetting, $ilClientIniFile;

    if (!self::HasUserValue($userData, 'send_mail'))
      $userData['send_mail']                = $ilUser->getPref('send_info_mails') == 'y';
    if (!self::HasUserValue($userData, 'language'))
      $userData['language']                 = $ilSetting->get('language');
    if (!self::HasUserValue($userData, 'skin_style'))
      $userData['skin_style']               = sprintf('%s:%s', $ilClientIniFile->readVariable('layout', 'skin'), $ilClientIniFile->readVariable('layout', 'style'));
    if (!self::HasUserValue($userData, 'time_limit_from'))
      $userData['time_limit_from']          = time();
    if (!self::HasUserValue($userData, 'time_limit_until'))
      $userData['time_limit_until']         = time();
    if (!self::HasUserValue($userData, 'time_limit_unlimited'))
      $userData['time_limit_unlimited']     = 1;
    if (!self::HasUserValue($userData, 'hide_own_online_status'))
      $userData['hide_own_online_status']   = false;
    if (!self::HasUserValue($userData, 'session_reminder_enabled'))
      $userData['session_reminder_enabled'] = $ilSetting->get('session_reminder_enabled');
    if (!self::HasUserValue($userData, 'auth_mode'))
      $userData['auth_mode']                = 'default';
    if (!self::HasUserValue($userData, 'hits_per_page'))
      $userData['hits_per_page']            = $ilSetting->get('hits_per_page');
    if (!self::HasUserValue($userData, 'show_users_online'))
      $userData['show_users_online']        = $ilSetting->get('show_users_online');
    if (!self::HasUserValue($userData, 'active'))
      $userData['active']                   = 1;
    if (!self::HasUserValue($userData, 'roles'))
      $userData['roles']                    = array(self::DEFAULT_ROLE_ID);

    return $userData;
  }


  /**
   * Function: InputToILIAS($userData)
   *  Converts streamlined input values (eg. ids are numbers, thrustvalues are boolean, etc.) to
   *  the partially crazy formats ILIAS wants to have...
   *
   * Parameters:
   *  userData <UserData> - User-Data array to be manipulated
   *
   * Return:
   *  <UserData> -  Modified User-Data
   */
  protected static function InputToILIAS($field, $value) {
    // Transform based on field
    switch ($field) {
      case 'time_limit_from':
      case 'time_limit_until':
        return self::GetUnixTime($value);
      case 'birthday':
        return self::GetISODate($value);
      case 'time_limit_unlimited':
      case 'active':
      case 'session_reminder_enabled':
        return self::ToNumericBoolean($value);
      case 'send_mail':
        return self::ToBoolean($value);
      case 'gender':
        if (is_string($value))
          return strtolower(substr($value, 0, 1));
        return $value;
      case 'id':
      case 'hits_per_page':
      case 'disk_quota':
      case 'wsp_disk_quota':
      case 'zoom':
        return intval($value);
      case 'hide_own_online_status':
      case 'show_users_online':
        return self::ToYesNo($value);
      case 'sel_country':
        return strtoupper($value);
      case 'latitude':
      case 'longitude':
        return floatval($value);
      case 'interests_general':
      case 'interests_help_offered':
      case 'interests_help_looking':
      case 'roles':
      case 'udf':
        if (!is_array($value))
          return array($value);
        return $value;
      default:
        return $value;
    }
  }


  /**
   * Function: ThrowIfMissingField($userData, $field, $isUDF = false)
   *  Throws an exception if field is missing in given user-data array.
   *
   * Parameters:
   *   userData <UserData> - User-Data array in which field should be checked
   *   field <String> - Field to check in User-Data array
   *   isUDF <Boolean> - Wether this is a User-Data Sub-Field ($userData['udf']) or not ($userData)
   */
  protected static function ThrowIfMissingField($userData, $field, $isUDF = false) {
    if (!self::HasUserValue($userData, $field))
      throw new LibExceptions\Parameter(
        Libs\RESTrequest::MSG_MISSING,
        Libs\RESTrequest::ID_MISSING,
        array(
          'key' => ($isUDF) ? "udf['$field']" : $field
        ),
        400
      );
  }


  /**
   * Function: CheckInputMissing($userData, $refId, $mode = self::MODE_CREATE)
   *  Checks wether manditory field is missing in given user-data. Throws if a
   *  manditory field is missing.
   *
   * Parameters:
   *  userData <UserData> - User-Data array to be checked
   *  refId <Numeric> - TimeLimitOwner of user (to check wether local or global user)
   *  mode <MODE_CREATE/MODE_UPDATE> - Wether to check field for user-creation or updating an existing user
   */
  protected static function CheckInputMissing($userData, $refId, $mode = self::MODE_CREATE) {
    // Check essentials when updating account
    if ($mode == self::MODE_UPDATE)
      self::ThrowIfMissingField($userData, 'id');

    // Check essentials when creating account
    elseif ($mode == self::MODE_CREATE) {
      global $ilSetting;

      // Check absolute essentials
      self::ThrowIfMissingField($userData, 'login');
      self::ThrowIfMissingField($userData, 'passwd');
      self::ThrowIfMissingField($userData, 'firstname');
      self::ThrowIfMissingField($userData, 'lastname');

      // Check for required field as set by ILIAS
      $settings = $ilSetting->getAll();
      foreach ($settings as $field => $required)
        if ($required === 1)
          self::ThrowIfMissingField($userData, $field);

      // Check for required user-data field as set by ILIAS
      include_once('Services/User/classes/class.ilUserDefinedFields.php');
      $instance    = \ilUserDefinedFields::_getInstance();
      $definitions = ($refId == self::USER_FOLDER_ID) ? $instance->getDefinitions() : $instance->getChangeableLocalUserAdministrationDefinitions();
      foreach ($definitions as $field => $definition)
        if ($definition['required'])
          self::ThrowIfMissingField($userData['udf'], $field, true);
    }
  }


  /**
   * Function: CheckStyleValue($value)
   *  Helper class to validate style input parameter.
   *
   * Parameters:
   *  value <String> - String-value containing <SKIN>:<STYLE> value (without <>)
   *
   * Return:
   *  <Boolean> - True if skin is valid, available and active
   */
  protected static function CheckStyleValue($value) {
    // Include required classes for validation
    include_once('Services/Style/classes/class.ilStyleDefinition.php');
    include_once('Services/Style/classes/class.ilObjStyleSettings.php');

    // Needs to be a string
    if (!is_string($value))
      return false;

    // Extract skin/style values
    $skin_style = explode(':', $value);
    if (sizeof($skin_style) != 2)
      return false;

    // Check wether skin is available and active
    return \ilStyleDefinition::styleExists($skin_style[0], $skin_style[1]) && \ilObjStyleSettings::_lookupActivatedStyle($skin_style[0], $skin_style[1]);
  }


  /**
   * Function: CheckValidUDF($udfArray, $refId)
   *  Utility function to validate all user-defined-fields in the user-data.
   *
   * Parameters:
   *  udfArray <Array> - Key/Value pair array containing values for user-defined-fields inside User-Data
   *  refId <Numeric> - RefID of user to select local or global user-defined-fields list
   *
   * Return:
   *  <Boolean> - True if all given user-defined-fields are valid
   */
  protected static function CheckValidUDF($udfArray, $refId) {
    // Include required classes for validation
    include_once('Services/User/classes/class.ilUserDefinedFields.php');

    // Fetch valid definitions
    $instance    = \ilUserDefinedFields::_getInstance();
    $definitions = ($refId == self::USER_FOLDER_ID) ? $instance->getDefinitions() : $instance->getChangeableLocalUserAdministrationDefinitions();
    $definitions = array_map(function($definition) { return intval($definition['field_id']); }, $definitions);

    // Check for excess definitions
    foreach ($udfArray as $field => $value)
      if (!((is_int($field) || ctype_digit($field)) && in_array(intval($field), $definitions)))
        return false;

    return is_array($udfArray);
  }


  /**
   * Function: ValidateTransformedInput($field, $value, $refId, $mode)
   *  This method validates all user-data input fields, including the user-defined-fields.
   *
   * Parameters:
   *  field <String> - Field inside given user-data to validate
   *  value <Mixed> - User-Data value contained in field, this value will be validated
   *  refId <Numeric> - TimeLimitOwner of given user
   *  mode <MODE_CREATE/MODE_UPDATE> - Wether validating input for creating or editing user
   *
   * Return:
   *  <Boolean>- True if all fields are valid
   */
  protected static function ValidateTransformedInput($field, $value, $refId, $mode = self::MODE_CREATE) {
    // Include required classes for validation
    include_once('Services/Authentication/classes/class.ilAuthUtils.php');
    include_once('Services/Utilities/classes/class.ilCountry.php');

    // Import language object
    global $lng;

    // Check based on field name
    switch ($field) {
      case 'login':
        return is_string($value) && \ilUtil::isLogin($value) && ($mode != self::MODE_CREATE || !\ilObjUser::_loginExists($value));
      case 'passwd':
        return is_string($value) && \ilUtil::isPassword($value);
      case 'email':
        return is_string($value) && \ilUtil::is_email($value);
      case 'language':
        return is_string($value) && in_array($value, $lng->getInstalledLanguages());
      case 'skin_style':
        return self::CheckStyleValue($value);
      case 'auth_mode':
        $modes = \ilAuthUtils::_getActiveAuthModes();
        return $modes[$value] == 1;
      case 'roles':
        return is_array($value) && self::ValidateRoles($value, $refId);
      case 'hide_own_online_status':
      case 'show_users_online':
        return $value === 'y' || $value === 'n';
      case 'id':
      case 'time_limit_from':
      case 'time_limit_until':
      case 'zoom':
      case 'hits_per_page':
      case 'disk_quota':
      case 'wsp_disk_quota':
        return is_int($value);
      case 'time_limit_unlimited':
      case 'active':
      case 'session_reminder_enabled':
        return ($value === 0 || $value === 1);
      case 'client_ip':
        return is_string($value) &&  (strlen($value) === 0 || filter_var($value, FILTER_VALIDATE_IP) !== false);
      case 'interests_general':
      case 'interests_help_offered':
      case 'interests_help_looking':
        return is_array($value);
      case 'sel_country':
        return in_array($value, \ilCountry::getCountryCodes());
      case 'institution':
      case 'department':
      case 'street':
      case 'city':
      case 'phone_office':
      case 'phone_home':
      case 'phone_mobile':
      case 'fax':
      case 'hobby':
      case 'referral_comment':
      case 'delicious':
      case 'im_icq':
      case 'im_yahoo':
      case 'im_msn':
      case 'im_aim':
      case 'im_skype':
      case 'im_jabber':
      case 'im_voip':
      case 'title':
      case 'firstname':
      case 'lastname':
      case 'ext_account':
        return is_string($value);
      case 'matriculation':
      case 'zipcode':
        return is_string($value) || is_numeric($value);
      case 'latitude':
      case 'longitude':
        return is_float($value);
      case 'udf':
        return self::CheckValidUDF($value, $refId);
      case 'gender':
        return $value == 'm' || $value == 'f' || $value == 't';
      case 'userfile':
        return $value === false || preg_match('#^data:image/\w+;base64,#i', $value) === 1;
      case 'birthday':
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value) === 1;
      case 'send_mail':
        return is_bool($value);
      default:
        return true;
    }
  }


  /**
   * Function: CheckUserData($userData, $mode, $refId)
   *  Checks wether the given user-data is valid and has values for all required fields.
   *
   * Prameters:
   *  userData <USER-DATA> - User-data to be checked
   *  mode <MODE_CREATE/MODE_UPDATE> - Wether to create or update ILIAS user account
   *  refId <Int> - Ref-id for local user administration
   *
   * Return:
   *  <USER-DATA> - Potentially cleaned up user-data with additional default values where appropriate
   */
  protected static function TransformInput($userData, $refId, $mode = self::MODE_CREATE) {
    // Add manditory default-values when creating new users
    if ($mode == self::MODE_CREATE)
      $userData = self::AddInputDefaults($userData);

    // Transform input such that ILIAS likes it
    foreach ($userData as $field => $value)
      $userData[$field] = self::InputToILIAS($field, $value);

    // Check if a required field is missing
    self::CheckInputMissing($userData, $refId, $mode);

    // Check if all inputs are valid
    foreach ($userData as $field => $value)
      if (!self::ValidateTransformedInput($field, $value, $refId, $mode))
        throw new LibExceptions\Parameter(
          Libs\RESTrequest::MSG_INVALID,
          Libs\RESTrequest::ID_INVALID,
          array(
            'key'   => $field,
            'value' => $value
          )
        );

    // Return updated user data
    return $userData;
  }


  /**
   *
   */
  protected static function TransformOutputField($field, $value, $refId) {
    switch ($field) {
      case 'id':
      case 'owner':
      case 'time_limit_owner':
      case 'zoom':
      case 'time_format':
      case 'date_format':
      case 'login_attemps':
      case 'hits_per_page':
        return intval($value);
      case 'roles':
        return array_map(function($role) { return intval($role); }, $value);
      case 'active':
      case 'time_limit_unlimited':
      case 'show_users_online':
      case 'hide_own_online_status':
      case 'time_limit_message':
        return self::ToBoolean($value);
      case 'time_limit_from':
      case 'time_limit_until':
      case 'passwd_timestamp':
        if (isset($value))
          return (new \ilDateTime($value, IL_CAL_UNIX))->get(IL_CAL_ISO_8601);
        return $value;
      case 'latitude':
      case 'longitude':
        return floatval($value);
      case 'last_update':
      case 'agree_date':
      case 'create_date':
      case 'last_login':
      case 'approve_date':
        if (isset($value))
          return (new \ilDateTime($value, IL_CAL_DATETIME))->get(IL_CAL_ISO_8601);
        return $value;
      case 'udf':
        if (is_array($value)) {
          //
          include_once('Services/User/classes/class.ilUserDefinedFields.php');
          $instance    = \ilUserDefinedFields::_getInstance();
          $definitions = ($refId == self::USER_FOLDER_ID) ? $instance->getDefinitions() : $instance->getChangeableLocalUserAdministrationDefinitions();

          //
          $mapped = array();
          foreach ($value as $udfKey => $udfValue) {
            $udfId   = intval(substr($udfKey, 2));
            $udfName = $definitions[$udfId]['field_name'];

            $mapped[$udfId] = array(
              'name'  => $udfName,
              'value' => $udfValue,
            );
          }

          return $mapped;
        }
        return $value;
      default:
        return $value;
    }
  }


  /**
   * Function: TransformOutput($userData, $refId)
   *  Transforms input from ilias to a format better suited for JSON output.
   *
   * Parameters:
   *  userData <UserData> - User-Data to be converted
   *  refId <Numeric> - Refid of category or org-unit (or global) which contains the user
   *
   * Returns:
   *  <UserData> - Cleaned/Converted user-data
   */
  protected static function TransformOutput($userData, $refId) {
    // Transform input such that ILIAS likes it
    foreach ($userData as $field => $value)
      $userData[$field] = self::TransformOutputField($field, $value, $refId);

    return $userData;
  }


  /**
   * Function: FetchUserData($userId, $refId)
   *  Returns user-data for the given ILIAS user.
   *
   * Paramters:
   *  userId <Int> - ILIAS user id to fetch data for
   *
   * Return:
   *  <USER-DATA> - Fetched user-data for given ILIAS user
   */
  public static function FetchUserData($userId) {
    // Include required classes (who needs an AutoLoader/DI-System anyway?! -.-)
    include_once('Services/Authentication/classes/class.ilAuthUtils.php');

    // Import ILIAS systems (all praise the glorious 'DI-System')
    global $rbacsystem, $rbacadmin, $rbacreview, $ilSetting, $ilUser;

    // Load user object
    $userObj = new \ilObjUser($userId);
    $refId   = $userObj->getTimeLimitOwner();

    // Check for local administration access-rights (Note: getTimeLimitOwner() should be $refId for new users)
    if ($refId != self::USER_FOLDER_ID && !$rbacsystem->checkAccess('cat_administrate_users', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_READ_DENIED,
        Libs\RESTilias::ID_RBAC_READ_DENIED,
        array(
          'object' => 'user-object'
        )
      );

    // Check for Admin-GUI access-rights to users
    if ($refId == self::USER_FOLDER_ID && !$rbacsystem->checkAccess('visible,read', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_READ_DENIED,
        Libs\RESTilias::ID_RBAC_READ_DENIED,
        array(
          'object' => 'user-object'
        )
      );

    // Magnitude of byte units (1024)
    $magnitude = \ilFormat::_getSizeMagnitude();

    // Collect user-data
    $userData                             = array();
    $userData['id']                       = $userId;
    $userData['roles']                    = $rbacreview->assignedRoles($userId);
    $userData['login']                    = $userObj->getLogin();
    $userData['owner']                    = $userObj->getOwner();
    $userData['owner_login']              = \ilObjUser::_lookupLogin($userData['owner']);
    $userData['auth_mode']                = $userObj->getAuthMode();
    $userData['client_ip']                = $userObj->getClientIP();
    $userData['active']                   = $userObj->getActive();
    $userData['time_limit_owner']         = $userObj->getTimeLimitOwner();
    $userData['time_limit_from']          = $userObj->getTimeLimitFrom();
    $userData['time_limit_until']         = $userObj->getTimeLimitUntil();
    $userData['time_limit_unlimited']     = $userObj->getTimeLimitUnlimited();
    $userData['interests_general']        = $userObj->getGeneralInterests();
    $userData['interests_help_offered']   = $userObj->getOfferingHelp();
    $userData['interests_help_looking']   = $userObj->getLookingForHelp();
    $userData['latitude']                 = $userObj->getLatitude();
    $userData['longitude']                = $userObj->getLongitude();
    $userData['zoom']                     = $userObj->getLocationZoom();
    $userData['udf']                      = $userObj->getUserDefinedData();
    $userData['ext_account']              = $userObj->getExternalAccount();
    $userData['time_zone']                = $userObj->getTimeZone();
    $userData['time_format']              = $userObj->getTimeFormat();
    $userData['date_format']              = $userObj->getDateFormat();
    $userData['user_agreement_accepted']  = $userObj->hasAcceptedUserAgreement();
    $userData['last_update']              = $userObj->getLastUpdate();
    $userData['login_attemps']            = $userObj->getLoginAttempts();
    $userData['passwd_change_demanded']   = $userObj->isPasswordChangeDemanded();
    $userData['passwd_expired']           = $userObj->isPasswordExpired();
    $userData['passwd_enc_type']          = $userObj->getPasswordEncodingType();
    $userData['passwd_timestamp']         = $userObj->getLastPasswordChangeTS();
    $userData['agree_date']               = $userObj->getAgreeDate();
    $userData["create_date"]              = $userObj->getCreateDate();
    $userData['last_login']               = $userObj->getLastLogin();
    $userData['approve_date']             = $userObj->getApproveDate();
    $userData['inactivation_date']        = $userObj->getInactivationDate();
    $userData['time_limit_message']       = $userObj->getTimeLimitMessage();
    $userData['profile_incomplete']       = $userObj->getProfileIncomplete();
    $userData['disk_quota']               = $userObj->getPref('disk_quota')     / $magnitude / $magnitude;
    $userData['wsp_disk_quota']           = $userObj->getPref('wsp_disk_quota') / $magnitude / $magnitude;
    $userData['session_reminder_enabled'] = $userObj->getPref('session_reminder_enabled');
    $userData['language']                 = $userObj->getLanguage();
    $userData['birthday']                 = $userObj->getBirthday();
		$userData['gender']                   = $userObj->getGender();
		$userData['institution']              = $userObj->getInstitution();
		$userData['department']               = $userObj->getDepartment();
		$userData['street']                   = $userObj->getStreet();
		$userData['city']                     = $userObj->getCity();
		$userData['zipcode']                  = $userObj->getZipcode();
		$userData['country']                  = $userObj->getCountry();
		$userData['sel_country']              = $userObj->getSelectedCountry();
		$userData['phone_office']             = $userObj->getPhoneOffice();
		$userData['phone_home']               = $userObj->getPhoneHome();
		$userData['phone_mobile']             = $userObj->getPhoneMobile();
		$userData['fax']                      = $userObj->getFax();
		$userData['matriculation']            = $userObj->getMatriculation();
		$userData['hobby']                    = $userObj->getHobby();
		$userData['referral_comment']         = $userObj->getComment();
    $userData['delicious']                = $userObj->getDelicious();
    $userData['hits_per_page']            = $userObj->getPref('hits_per_page');
    $userData['show_users_online']        = $userObj->getPref('show_users_online');
    $userData['hide_own_online_status']   = $userObj->getPref('hide_own_online_status');
		$userData['email']                    = $userObj->getEmail();
    $userData['skin_style']               = sprintf('%s:%s', $userObj->setPref('skin',  $skin), $userObj->setPref('style', $style));
    $userData['im_icq']                   = $userObj->getInstantMessengerId('icq');
    $userData['im_yahoo']                 = $userObj->getInstantMessengerId('yahoo');
    $userData['im_msn']                   = $userObj->getInstantMessengerId('msn');
    $userData['im_aim']                   = $userObj->getInstantMessengerId('aim');
    $userData['im_skype']                 = $userObj->getInstantMessengerId('skype');
    $userData['im_jabber']                = $userObj->getInstantMessengerId('jabber');
    $userData['im_voip']                  = $userObj->getInstantMessengerId('voip');
    $userData['title']                    = $userObj->getUTitle();
    $userData['fullname']                 = $userObj->getFullname();
    $userData['firstname']                = $userObj->getFirstname();
    $userData['lastname']                 = $userObj->getLastname();

    // Convert profile-picture to base64 encoded data
    if ($userObj->getPref('profile_image')) {
      $picturePath = $userObj->getPersonalPicturePath();
      if (is_string($picturePath)) {
        $type = pathinfo($picturePath, PATHINFO_EXTENSION);
        $data = file_get_contents($picturePath);
        if (is_string($type) && is_string($data) && strlen($data) > 0)
          $userData['upload'] = sprintf('data:image/%s;base64,%s', $type, base64_encode($data));
      }
    }

    // Return collected, cleaned-up user-data
    return self::TransformOutput($userData, $refId);
  }


  /**
   * Function: StoreUserData($userData, $mode, $refId)
   *  Creates a new ILIAS user account or updates an existing one with the given user-data.
   *
   * Note 1:
   *  refID is either self::USER_FOLDER_ID, which in the context of ILIAS means the Admin-GUI
   *  or the Reference-ID of a categorie or organisational-unit for local administration.
   * Note 2:
   *  The RBAC-System needs to be initialized with the access-token user account. (RESTIlias::loadIlUser())
   * Note 3:
   *  This method does not do any input validation, this is the responisbility of functions like self::CheckUserData().
   *
   * Parameters:
   *  userData <USER-DATA> - User data used to create or update ILIAS user
   *  mode <MODE_CREATE/MODE_UPDATE> - Wether to create or update account
   *
   * Return:
   *  userfile <Bool> - Contains addition information if a virus was detected in the users profile-picture
   *  email <Bool> - Wether a notification email was send successfully...
   *  user <ilObjUser> - ILIAS user object that was created or updated
   */
  public static function StoreUserData($userData, $mode = self::MODE_CREATE) {
    // Include required classes (who needs an AutoLoader/DI-System anyway?! -.-)
    include_once('Services/Authentication/classes/class.ilAuthUtils.php');
    include_once('Services/User/classes/class.ilUserProfile.php');
    include_once('Services/Mail/classes/class.ilAccountMail.php');

    // Import ILIAS systems (all praise the glorious 'DI-System')
    global $rbacsystem, $rbacadmin, $rbacreview, $ilSetting, $ilAccess, $ilUser;

    // Make sure mode is correct
    if ($mode != self::MODE_CREATE && $mode != self::MODE_UPDATE)
      throw new LibExceptions\Parameter(
        self::MSG_INVALID_MODE,
        self::ID_INVALID_MODE
      );

    // Will contain return values if any
    $result = array();

    // Check rights to create user
    if ($mode == self::MODE_CREATE) {
      // Make sure input is in a format that ilias understands and likes
      $refId    = intval($userData['ref_id']) ?: self::USER_FOLDER_ID;
      $userData = Admin::TransformInput($userData, $refId, $mode);

      // Check of user is allowd to create user globally or in given category/org-unit
      if (!$rbacsystem->checkAccess('create_usr', $refId) && !$ilAccess->checkAccess('cat_administrate_users', '', $refId))
        throw new LibExceptions\RBAC(
          Libs\RESTilias::MSG_RBAC_WRITE_DENIED,
          Libs\RESTilias::ID_RBAC_WRITE_DENIED,
          array(
            'object' => 'user-object'
          )
        );

      // Create new user object
      $userObj = new \ilObjUser();
      $userObj->setLogin($userData['login']);
      $userObj->setTimeLimitOwner($refId);
    }
    // Check rights to edit user
    else {
      // Load user object
      $userObj = new \ilObjUser($userData['id']);
      $refId   = $userObj->getTimeLimitOwner();

      // Make sure input is in a format that ilias understands and likes
      $userData = Admin::TransformInput($userData, $refId, $mode);

      // Check for local administration access-rights (Note: getTimeLimitOwner() should be $refId for new users)
      if ($refId != self::USER_FOLDER_ID && !$rbacsystem->checkAccess('cat_administrate_users', $refId))
        throw new LibExceptions\RBAC(
          Libs\RESTilias::MSG_RBAC_WRITE_DENIED,
          Libs\RESTilias::ID_RBAC_WRITE_DENIED,
          array(
            'object' => 'user-object'
          ),
          400
        );

      // Check for Admin-GUI access-rights to users
      if ($refId == self::USER_FOLDER_ID && !$rbacsystem->checkAccess('visible,read', $refId))
        throw new LibExceptions\RBAC(
          Libs\RESTilias::MSG_RBAC_WRITE_DENIED,
          Libs\RESTilias::ID_RBAC_WRITE_DENIED,
          array(
            'object' => 'user-object'
          )
        );

      // Update login of existing account
      if (self::HasUserValue($userData, 'login'))
        $userObj->updateLogin($userData['login']);
    }

    // Note:
    //  This is a hack required for ilObjUser::getLoginFromAuth() during ilObjUser->update()
    //  when changing a users activation status, which required global $ilAuth which does not
    //  get set durinng init from ilInitialisation with REST context.
    $GLOBALS['ilAuth'] = new ilAuthFake();

    // Set user-values
    if (self::HasUserValue($userData, 'auth_mode'))
      $userObj->setAuthMode($userData['auth_mode']);
    if (self::HasUserValue($userData, 'client_ip'))
      $userObj->setClientIP($userData['client_ip']);
    if (self::HasUserValue($userData, 'active'))
      $userObj->setActive($userData['active'], $ilUser->getId());
    if (self::HasUserValue($userData, 'time_limit_from'))
      $userObj->setTimeLimitFrom($userData['time_limit_from']);
    if (self::HasUserValue($userData, 'time_limit_until'))
      $userObj->setTimeLimitUntil($userData['time_limit_until']);
    if (self::HasUserValue($userData, 'time_limit_unlimited'))
      $userObj->setTimeLimitUnlimited($userData['time_limit_unlimited']);
    if (self::HasUserValue($userData, 'interests_general'))
      $userObj->setGeneralInterests($userData['interests_general']);
    if (self::HasUserValue($userData, 'interests_help_offered'))
      $userObj->setOfferingHelp($userData['interests_help_offered']);
    if (self::HasUserValue($userData, 'interests_help_looking'))
      $userObj->setLookingForHelp($userData['interests_help_looking']);
    if (self::HasUserValue($userData, 'latitude'))
      $userObj->setLatitude($userData['latitude']);
    if (self::HasUserValue($userData, 'longitude'))
      $userObj->setLongitude($userData['longitude']);
    if (self::HasUserValue($userData, 'zoom'))
      $userObj->setLocationZoom($userData['zoom']);
    if (self::HasUserValue($userData, 'time_zone'))
      $userObj->setPref('user_tz', $userData['time_zone']);
    if (self::HasUserValue($userData, 'time_format'))
      $userObj->setPref('time_format', $userData['time_format']);
    if (self::HasUserValue($userData, 'date_format'))
      $userObj->setPref('date_format', $userData['date_format']);
    if (self::HasUserValue($userData, 'udf'))
      $userObj->setUserDefinedData($userData['udf']);
    if (self::HasUserValue($userData, 'language') && self::IsChangeable('language', $refId))
      $userObj->setLanguage($userData['language']);
    if (self::HasUserValue($userData, 'birthday') && self::IsChangeable('birthday', $refId))
      $userObj->setBirthday($userData['birthday']);
		if (self::HasUserValue($userData, 'gender') && self::IsChangeable('gender', $refId))
			$userObj->setGender($userData['gender']);
    if (self::HasUserValue($userData, 'institution') && self::IsChangeable('institution', $refId))
			$userObj->setInstitution($userData['institution']);
		if (self::HasUserValue($userData, 'department') && self::IsChangeable('department', $refId))
			$userObj->setDepartment($userData['department']);
		if (self::HasUserValue($userData, 'street') && self::IsChangeable('street', $refId))
			$userObj->setStreet($userData['street']);
		if (self::HasUserValue($userData, 'city') && self::IsChangeable('city', $refId))
			$userObj->setCity($userData['city']);
		if (self::HasUserValue($userData, 'zipcode') && self::IsChangeable('zipcode', $refId))
			$userObj->setZipcode($userData['zipcode']);
		if (self::HasUserValue($userData, 'country') && self::IsChangeable('country', $refId))
			$userObj->setCountry($userData['country']);
		if (self::HasUserValue($userData, 'sel_country') && self::IsChangeable('sel_country', $refId))
			$userObj->setSelectedCountry($userData['sel_country']);
		if (self::HasUserValue($userData, 'phone_office') && self::IsChangeable('phone_office', $refId))
			$userObj->setPhoneOffice($userData['phone_office']);
		if (self::HasUserValue($userData, 'phone_home') && self::IsChangeable('phone_home', $refId))
			$userObj->setPhoneHome($userData['phone_home']);
		if (self::HasUserValue($userData, 'phone_mobile') && self::IsChangeable('phone_mobile', $refId))
			$userObj->setPhoneMobile($userData['phone_mobile']);
		if (self::HasUserValue($userData, 'fax') && self::IsChangeable('fax', $refId))
			$userObj->setFax($userData['fax']);
		if (self::HasUserValue($userData, 'matriculation') && self::IsChangeable('matriculation', $refId))
			$userObj->setMatriculation($userData['matriculation']);
		if (self::HasUserValue($userData, 'hobby') && self::IsChangeable('hobby', $refId))
			$userObj->setHobby($userData['hobby']);
		if (self::HasUserValue($userData, 'referral_comment') && self::IsChangeable('referral_comment', $refId))
			$userObj->setComment($userData['referral_comment']);
    if (self::HasUserValue($userData, 'delicious') && self::IsChangeable('delicious', $refId))
      $userObj->setDelicious($userData['delicious']);
		if (self::HasUserValue($userData, 'email') && self::IsChangeable('email', $refId)) {
			$userObj->setEmail($userData['email']);
      $userObj->setDescription($userObj->getEmail());
    }
    if (self::IsChangeable('instant_messengers', $refId)) {
      if (self::HasUserValue($userData, 'im_icq'))
        $userObj->setInstantMessengerId('icq',    $userData['im_icq']);
      if (self::HasUserValue($userData, 'im_yahoo'))
        $userObj->setInstantMessengerId('yahoo',  $userData['im_yahoo']);
      if (self::HasUserValue($userData, 'im_msn'))
        $userObj->setInstantMessengerId('msn',    $userData['im_msn']);
      if (self::HasUserValue($userData, 'im_aim'))
        $userObj->setInstantMessengerId('aim',    $userData['im_aim']);
      if (self::HasUserValue($userData, 'im_skype'))
        $userObj->setInstantMessengerId('skype',  $userData['im_skype']);
      if (self::HasUserValue($userData, 'im_jabber'))
        $userObj->setInstantMessengerId('jabber', $userData['im_jabber']);
      if (self::HasUserValue($userData, 'im_voip'))
        $userObj->setInstantMessengerId('voip',   $userData['im_voip']);
    }
    if (self::HasUserValue($userData, 'title') && self::IsChangeable('title', $refId)) {
      $userObj->setUTitle($userData['title']);

      // Update fullname and full title based on firstname, lastname and user-title
      $userObj->setFullname();
      $userObj->setTitle($userObj->getFullname());
    }
    if (self::HasUserValue($userData, 'firstname') && self::IsChangeable('firstname', $refId)) {
      $userObj->setFirstname($userData['firstname']);

      // Update fullname and full title based on firstname, lastname and user-title
      $userObj->setFullname();
      $userObj->setTitle($userObj->getFullname());
    }
    if (self::HasUserValue($userData, 'lastname') && self::IsChangeable('lastname', $refId)) {
      $userObj->setLastname($userData['lastname']);

      // Update fullname and full title based on firstname, lastname and user-title
      $userObj->setFullname();
      $userObj->setTitle($userObj->getFullname());
    }

    // Set user-preferences which can have change-restrictions
    if (self::HasUserValue($userData, 'hits_per_page') && self::IsChangeable('hits_per_page', $refId))
      $userObj->setPref('hits_per_page', $userData['hits_per_page']);
    if (self::HasUserValue($userData, 'show_users_online') && self::IsChangeable('show_users_online', $refId))
      $userObj->setPref('show_users_online', $userData['show_users_online']);
    if (self::HasUserValue($userData, 'hide_own_online_status') && self::IsChangeable('hide_own_online_status', $refId))
      $userObj->setPref('hide_own_online_status', $userData['hide_own_online_status']);
    if (self::IsChangeable('skin_style', $refId)) {
      // Extract sk/style arguments
      $skin_style = explode(':', $userData['skin_style']);
      $skin       = $skin_style[0];
      $style      = $skin_style[1];

      // Set skin/style presserence
      $userObj->setPref('skin',  $skin);
      $userObj->setPref('style', $style);
    }

    // Set session reminder
    if (self::HasUserValue($userData, 'session_reminder_enabled') && $ilSetting->get('session_reminder_enabled'))
      $userObj->setPref('session_reminder_enabled', $userData['session_reminder_enabled']);

    // Set password on creation or update if allowed
    $userAuthMode    = $userObj->getAuthMode();
    $userAuthModeID  = \ilAuthUtils::_getAuthMode($userAuthMode);
    $pwChangeAllowed = \ilAuthUtils::_allowPasswordModificationByAuthMode($userAuthModeID);
    if (self::HasUserValue($userData, 'passwd') && ($mode == self::MODE_CREATE || $pwChangeAllowed)) {
      $userObj->setPasswd($userData['passwd'], IL_PASSWD_PLAIN);
      $userObj->setLastPasswordChangeTS(time());
    }

    // Set attached external account if enabled
    if (self::HasUserValue($userData, 'ext_account') && \ilAuthUtils::_isExternalAccountEnabled())
      $userObj->setExternalAccount($userData['ext_account']);

    // Set disk quotas (overall abd workspace)
    require_once 'Services/WebDAV/classes/class.ilDiskQuotaActivationChecker.php';
    if (self::HasUserValue($userData, 'disk_quota')     && \ilDiskQuotaActivationChecker::_isActive())
      $userObj->setPref('disk_quota',     $userData['disk_quota']     * \ilFormat::_getSizeMagnitude() * \ilFormat::_getSizeMagnitude());
    if (self::HasUserValue($userData, 'wsp_disk_quota') && \ilDiskQuotaActivationChecker::_isPersonalWorkspaceActive())
      $userObj->setPref('wsp_disk_quota', $userData['wsp_disk_quota'] * \ilFormat::_getSizeMagnitude() * \ilFormat::_getSizeMagnitude());

    // Check wether profile is incomplete ()
    $userObj->setProfileIncomplete(\ilUserProfile::isProfileIncomplete($userObj));

    // Create and save user account data
    if ($mode == self::MODE_CREATE) {
      $userObj->create();
      $userObj->saveAsNew();
      $userObj->writePrefs();
    }
    // Update user account in database
    else
      $userObj->update();

    // Reset login attempts if account state might have changed
    if ($userData['active'])
      \ilObjUser::_resetLoginAttempts($userObj->getId());

    // Create profile-picture from attached based64 encoded image
    if (self::HasUserValue($userData, 'upload') &&  self::IsChangeable('upload', $refId)) {
      $hasVirus = self::ProcessUserPicture($userObj, $userData['upload']);
    }

    // Assign user to given roles (and deassigned missing roles)
    if (is_array($userData['roles'])) {
      $assignedRoles = $rbacreview->assignedRoles($userObj->getId());
      $dropRoles     = array_diff($assignedRoles, $userData['roles']);
      $addRoles      = array_diff($userData['roles'], $assignedRoles);
      foreach ($dropRoles as $role)
        $rbacadmin->deassignUser($role, $userObj->getId());
      foreach ($addRoles as $role)
        $rbacadmin->assignUser($role, $userObj->getId());
    }

    // Send email?
    if ($mode == self::MODE_CREATE && $userData['send_mail'] == 'y') {
      // Create new eamil object
      $mail = new \ilAccountMail();
      $mail->useLangVariablesAsFallback(true);
      $mail->setUserPassword($userData['passwd']);
      $mail->setUser($userObj);

      // Send email and return any error-code
      $mail->send();
    }

    if (isset($hasVirus))
      throw new LibExceptions\Parameter(
        self::MSG_IMAGE_VIRUS,
        self::ID_IMAGE_VIRUS,
        array(
          'id' => intval($userObj->getId())
        )
      );

    // Return on success with some additional information
    return $userObj;
  }


  /**
   * Function: DeleteUser($userId,  $refId)
   *  Checks RBAC permissions and deletes the given (local/global) user account,
   *  with the given user-id.
   *
   * Parameters:
   *  userId <Int> - User id of user to be deleted
   *  refId <Int> - Ref-id for local user administration
   */
  public static function DeleteUser($userId) {
    global $rbacsystem, $ilAccess, $ilUser;

    // Can't delte yourself
    if ($ilUser->getId() == $userId)
      throw new LibExceptions\RBAC(
        self::MSG_DELETE_SELF,
        self::ID_DELETE_SELF
      );

    // Check if given refid matches
    $userObj = new \ilObjUser($userId);
    $refId   = $userObj->getTimeLimitOwner();

    // Check if allowed to delete user
    if ($refId == self::USER_FOLDER_ID && !$rbacsystem->checkAccess('delete', $refId)
    ||  $refId != self::USER_FOLDER_ID && !$ilAccess->checkAccess('cat_administrate_users', '', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_DELETE_DENIED,
        Libs\RESTilias::ID_RBAC_DELETE_DENIED,
        array(
          'object' => 'user-object'
        )
      );

    // Delete user
    $userObj->delete();
  }


  /**
   * Function: HasUserValue($userData, $field)
   *  Checks if the given user-data has a value for the given field.
   *
   * Paramters:
   *  userData <USER-DATA> - User data
   *  field <String> - Field inside user data
   *
   * Return:
   *  <Bool> - User-data contains a value for the given field
   */
  protected static function HasUserValue($userData, $field) {
    return (is_array($userData) && array_key_exists($field, $userData));
  }


  /**
   * Function: IsChangeable($field, $refId)
   *  Checks wether this user-field is allowed to be edited.
   *  @See Administration -> User Administration -> Default Fields / User-Defined Fields
   *  for setting wether a field is changeable (and/or required).
   *
   * Parameters:
   *  field <String> - User-data field to check
   *  refId <Int> - Ref-id for local user administration
   *
   * Return:
   *  <Bool> - True wether the field is allowed to be changed
   */
  protected static function IsChangeable($field, $refId) {
    // Fetch reference to ILIAS settings
    global $ilSetting, $rbacsystem;

    // All settings can be changed via the admin-panel / for global accounts
    if ($refId == self::USER_FOLDER_ID || $rbacsystem->checkAccess('visible,read', self::USER_FOLDER_ID))
      return true;

    // Fetch ILIAS settings for checking changability
    $settings = $ilSetting->getAll();

    // Check wether setting is marked as changeable
    return (bool) $settings[sprintf('usr_settings_changeable_lua_%s', $field)];
  }


  /**
   * Function: ProcessUserPicture($userObj, $imgData)
   *  Converts input base64-encoded image into a file that can be used by ILIAS.
   *  (Also applies virus-scanner to created file to be sure)
   *
   * Parameters:
   *  userObj <ilObUser> - ILIAS User-Object to attach profile picture to
   *  imgData <String> - Base64 encoded image data
   */
  protected static function ProcessUserPicture($userObj, $imgData) {
    // Delete user picture files
    if (!isset($imgData) || $imgData === false)
      $userObj->removeUserPicture(true);

    // Create user pciture files (profile-pricutre and thumbnails)
    else {
      // Extract base64 encoded image data
      $encodedData = preg_replace('#^data:image/\w+;base64,#i', '', $imgData);
      if (!isset($encodedData) || strlen($encodedData) == 0 || strcmp($imgData, $encodedData) == 0)
        throw new LibExceptions\Parameter(
          self::MSG_USER_PICTURE_EMPTY,
          self::ID_USER_PICTURE_EMPTY
        );

      // Store decoded image data to file
      //  Note: ilObjUserGUI sets chmod to 0770; beats me why one would enabled execution bit on an UPLOADED file...
      $tmpFile = sprintf('%s/usr_images/upload_%d', ilUtil::getWebspaceDir(), $userObj->getId());
      file_put_contents($tmpFile, base64_decode($encodedData));
      chmod($tmpFile, 0664);

      // Check uploaded file for virus and delete + fail if one was detected
      $scanResult = \ilUtil::virusHandling($tmpFile, sprintf('Profile-Picutre [User: %s]', $userObj->getLogin()));
      if (!$scanResult[0]) {
        // Delete file
        unlink($tmpFile);

        // Return scan result
        return $scanResult;
      }

      // Generate tumbnails, write and update prefs
      $userObj->_uploadPersonalPicture($tmpFile, $userObj->getId());
      $userObj->setPref('profile_image', sprintf('usr_%d.jpeg', $userObj->getId()));
    }
  }


  /**
   * Function: GetUnixTime($data)
   *  Converts various input-formats to uni-time.
   *  Supported input-formats are unit-time, array with [date][time] keys
   *  and any format supported by DateTime.
   *
   * Parameters:
   *  data <Mixed> - Input time
   *
   * Return:
   *  <Int> - Input time converted to unit-time
   */
  protected static function GetUnixTime($data) {
    // Time seems to be in unix-time
    if (is_int($data))
      return $data;
    // Time seems to be in unix-time (but a string)
    elseif (ctype_digit($data))
      return intval($data);
    // Date and time given, convert to uni-time
    elseif (is_array($data) && array_key_exists('date', $data) && array_key_exists('time', $data)) {
      $time = new \ilDateTime(sprintf('%s %s', $data['date'], $data['time']));
      return $time->get(IL_CAL_UNIX);
    }

    // Try to use ilDateTime to extract unix-time
    if (is_string($data)) {
      $date = \DateTime::createFromFormat(\DateTime::ATOM, $data);
      if ($date)
        return $date->getTimeStamp();

      $offset     = \Date('P');
      $fixed_data = "{$data}{$offset}";
      $date       = \DateTime::createFromFormat(\DateTime::ATOM, $fixed_data);
      if ($date)
        return $date->getTimeStamp();
    }

    // Conversion failed...
    return $data;
  }


  /**
   * Function: GetISODate($data)
   *  Convert various ILIAS time-formats into a valid ISO 8601 format.
   *  This only returns the DATE (not time) part!
   *
   * Parameter:
   *  data <Mixed> - Input time (any supported by DateTime)
   *
   * Return:
   *  <String> - Time formated in ISO 8601 format
   */
  protected static function GetISODate($data) {
    // Time seems to be in unix-time
    if (is_int($data))
      return date('Y-m-d', $data);
    // Time seems to be in unix-time (but a string)
    elseif (ctype_digit($data))
      return date('Y-m-d', intval($data));
    // Time seems to be in a special format
    elseif (is_string($data)) {
      // String seems to contain more than date data
      if (strlen($data) > 8) {
        try {
          $date = new \DateTime($data);
          if ($date)
            return $date->format('Y-m-d');
        // Fallback case...
        } catch (\Exception $e) {
          return $data;
        }
      }
      // String hopefully only contains the date part
      else
        return $data;
    }
  }


  /**
   * Function: ToNumericBoolean($value)
   *  Convert a string, boolean or number to a numeric value (0 or 1).
   *
   * Parameters:
   *   value <String/Boolean/Numeric> - Value to be converted
   *
   * Return:
   *   <0/1> Converted value
   */
  protected static function ToNumericBoolean($value) {
    if (is_numeric($value))
      return intval($value);
    if (is_string($value))
      return ($value === 'true' || substr($value, 0, 1) === 'y') ? 1 : 0;
    return intval($value);
  }


  /**
   * Function: ToBoolean($value)
   *  Convert a string, boolean or number to a boolean value.
   *
   * Parameters:
   *   value <String/Boolean/Numeric> - Value to be converted
   *
   * Return:
   *   <Boolean> Converted value
   */
   protected static function ToBoolean($value) {
     if (is_numeric($value))
       return intval($value) > 0;
     if (is_string($value))
       return ($value === 'true' || substr($value, 0, 1) === 'y');
     return $value;
   }


   /**
    * Function: ToYesNo($value)
    *  Convert a string or boolean value to 'y' or 'n'.
    *
    * Parameters:
    *   value <String/Boolean> - Value to be converted
    *
    * Return:
    *   <y/n> Converted value
    */
  protected static function ToYesNo($value) {
    if (is_bool($value))
      return ($value) ? 'y' : 'n';
    if (is_string($value)) {
      if (strtolower($value) === 'true')
        return 'y';
      if (strtolower($value) === 'false')
        return 'n';
      return strtolower(substr($value, 0, 1));
    }
    return $value;
  }


  /**
   * Function: ValidateRoles($roles, $refId)
   *  Checks wether all given rules match what the currently active
   *  ILIAS user is allowed to assign. System admins are allowed to assign
   *  all roles, all others only those roles assigned to themself.
   *
   * Note: This check is rather restrictive (even for admins),
   *  only global and local roles in Categorie/Organisational-Unit
   *  are valid.
   *  Alternitivly we could allow admins to set any role from
   *   $rbacreview->getAssignableRoles();
   *  and local admins any role from
   *   $rbacreview->getAssignableRolesInSubtree($refId);
   *
   * Parameters:
   *  roles <Array<Int>> - List of roles to check
   *  refId <Int> - Ref-Id of category (or system-panel) for local roles
   *
   * Return:
   *  <Bool> - True if all roles can be assigned by the currently active ILIAS user
   */
  protected static function ValidateRoles($roles, $refId) {
    include_once('Services/AccessControl/classes/class.ilObjRole.php');
    global $rbacreview;

    // Fetch list of assignable roles
    $global = $rbacreview->getGlobalRoles();
    if ($refId != self::USER_FOLDER_ID) {
      $local  = $rbacreview->getRolesOfRoleFolder($refId);
      $global = array_filter($global, function($role) {
        return \ilObjRole::_getAssignUsersStatus($role);
      });
      $assignable = array_merge($local, $global);
    }
    else
      $assignable = $global;
    $assignable   = array_map('intval', $assignable);

    // Check if all roles are assignable
    return count(array_diff($roles, $assignable)) == 0;
  }
}
