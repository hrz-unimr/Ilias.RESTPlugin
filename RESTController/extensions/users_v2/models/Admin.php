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


/**
 * Class: Admin
 *  TODO: Refactor class into smaller parts!!!
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
 *  loc_zoom <Int> - Default Zoom-Level for maps
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
  const MSG_RBAC_EDIT_DENIED    = 'Permission to read, create or modify user-account denied by RBAC-System.';
  const ID_RBAC_EDIT_DENIED     = 'RESTController\\extensions\\users_v2\\Admin::ID_RBAC_CREATE_DENIED';
  const MSG_RBAC_READ_DENIED    = 'Permission to read user-account denied by RBAC-System.';
  const ID_RBAC_READ_DENIED     = 'RESTController\\extensions\\users_v2\\Admin::ID_RBAC_READ_DENIED';
  const MSG_NO_GLOBAL_ROLE      = 'Access-token user has no global role that could be inherited by new users.';
  const ID_NO_GLOBAL_ROLE       = 'RESTController\\extensions\\users_v2\\Admin::ID_NO_GLOBAL_ROLE';
  const MSG_USER_PICTURE_EMPTY  = 'User picture does not contain any base64-encoded data.';
  const ID_USER_PICTURE_EMPTY   = 'RESTController\\extensions\\users_v2\\Admin::ID_USER_PICTURE_EMPTY';
  const MSG_INVALID_MODE        = 'Invalid mode, must either be \'create\' or \'update\'.';
  const ID_INVALID_MODE         = 'RESTController\\extensions\\users_v2\\Admin::ID_INVALID_MODE';
  const MSG_INVALID_FIELD       = 'Given user-data contains an invalid field: {{field}}';
  const ID_INVALID_FIELD        = 'RESTController\\extensions\\users_v2\\Admin::ID_INVALID_FIELD';
  const MSG_MISSING_FIELD       = 'Given user-data is missing a required field: {{field}}';
  const ID_MISSING_FIELD        = 'RESTController\\extensions\\users_v2\\Admin::ID_MISSING_FIELD';
  const MSG_DELETE_SELF         = 'Not allowed to delete own user-account.';
  const ID_DELETE_SELF          = 'RESTController\\extensions\\users_v2\\Admin::ID_DELETE_SELF';
  const MSG_REFID_MISMATCH      = 'Time-Limit owner ({{owner}}) does not match given ref-id ({{ref_id}}).';
  const ID_REFID_MISMATCH       = 'RESTController\\extensions\\users_v2\\Admin::ID_REFID_MISMATCH';


  // Redefine global constants as local constants
  const DEFAULT_ROLE_ID     = 4;
  const SYSTEM_ROLE_ID      = SYSTEM_ROLE_ID;
  const ANONYMOUS_ROLE_ID   = ANONYMOUS_ROLE_ID;
  const ROLE_FOLDER_ID      = ROLE_FOLDER_ID;
  const USER_FOLDER_ID      = USER_FOLDER_ID;


  // Pseudo-Enum values to control user-data storage behaviour (create or update existing)
  const MODE_CREATE = 'create';
  const MODE_UPDATE = 'update';


  // List of valid user-data fields that can be SET
  // Naming of fields was taken mostly unchanged from ilObjUserGUI
  const fields = array(
    'login',
    'id',
    'auth_mode',
    'client_ip',
    'active',
    'time_limit_from',
    'time_limit_until',
    'time_limit_unlimited',
    'interests_general',
    'interests_help_offered',
    'interests_help_looking',
    'latitude',
    'longitude',
    'loc_zoom',
    'udf',
    'language',
    'birthday',
    'gender',
    'institution',
    'department',
    'street',
    'city',
    'zipcode',
    'country',
    'sel_country',
    'phone_office',
    'phone_home',
    'phone_mobile',
    'fax',
    'matriculation',
    'hobby',
    'referral_comment',
    'delicious',
    'email',
    'im_icq',
    'im_yahoo',
    'im_msn',
    'im_aim',
    'im_skype',
    'im_jabber',
    'im_voip',
    'title',
    'firstname',
    'lastname',
    'hits_per_page',
    'show_users_online',
    'hide_own_online_status',
    'skin_style',
    'session_reminder_enabled',
    'passwd',
    'ext_account',
    'disk_quota',
    'wsp_disk_quota',
    'userfile',
    'roles',
    'send_mail'
  );


  /**
   * Function: GetDefaultValue($field)
   *  Returns a default value (if possible) for the given user-data field.
   *  Returns null if no default value can be generated.
   *
   * Parameters:
   *  field <String> - User-data field to return a default value for
   *
   * Returns
   *  <Any> Default value for given field
   */
  protected static function GetDefaultValue($field) {
    // Fetch reference to ILIAS objects
    global $ilUser, $ilSetting, $ilClientIniFile;

    // Return default value based on field
    switch ($field) {
      // Set default email notification trigger
      case 'send_mail':
        return $ilUser->getPref('send_info_mails') == 'y';
      // Set default language
      case 'language':
        return $ilSetting->get('language');
      // Set default skin
      case 'skin_style':
        return sprintf('%s:%s', $ilClientIniFile->readVariable('layout', 'skin'), $ilClientIniFile->readVariable('layout', 'style'));
      // The default time-limit (from)
      case 'time_limit_from':
        return time();
      // The default time-limit (until)
      case 'time_limit_until':
        return time();
      // Set to unlimited by default
      case 'time_limit_unlimited':
        return 1;
      // Show online status
      case 'hide_own_online_status':
        return false;
      // Enable session reminder
      case 'session_reminder_enabled':
        return $ilSetting->get('session_reminder_enabled');
      // Set to 'default' authenticatio mode
      case 'auth_mode':
        return 'default';
      // Set default value for HitsPerPage
      case 'hits_per_page':
        return $ilSetting->get('hits_per_page');
      // Show online users?
      case 'show_users_online':
        return $ilSetting->get('show_users_online');
      // New accounts are active by default
      case 'active':
        return 1;
      // Fetch default role
      case 'roles':
        return self::DEFAULT_ROLE_ID;
    }
  }


  /**
   * Function: TransformField($field, $value)
   *  Since ILIAS is the most consistent software writen since
   *  there was any good Sonic game we transform input to the
   *  RESTPlugin for ILIAS as well as output from the RESTPlugin
   *  taken from ILIAS into a more consistent format.
   *  Most common transformations are (1,y)->true (0,n)->false,
   *  numeric conversions of strings, ISO 6801 (and ILIAS pseudo UTC-Format)
   *  to and from unix-time, etc...
   *
   * Parameters:
   *  field <String> - User-data field, required to select correct transformation
   *  value <Any> - User-data field-value to be transformed
   *
   * Returns:
   *  <Any> Possibly transformed value
   */
  protected static function TransformField($field, $value) {
    // Transform based on field
    switch ($field) {
      // Requires unix-time
      case 'time_limit_from':
      case 'time_limit_until':
        return self::GetUnixTime($value);
      // Requires date string without time value
      case 'birthday':
        return self::GetISODate($value);
      // Requires int instead of boolean for some reason (Tip: Its ILIAS... -.-)
      case 'time_limit_unlimited':
      case 'active':
      case 'session_reminder_enabled':
        if (is_string($value))
          $value = strcmp(strtolower(substr($value, 0, 1)), 'y');
        return intval($value);
      // Requires boolean
      case 'send_mail':
        if (is_string($value))
          return strcmp(strtolower(substr($value, 0, 1)), 'y');
        elseif (is_int($value))
          return $value > 0;
        return $value;
      // Needs to be 'm' / 'f'
      case 'gender':
        if (is_string($value))
          return strtolower(substr($value, 0, 1));
        return $value;
      // Needs to be an integer value
      case 'id':
      case 'hits_per_page':
      case 'disk_quota':
      case 'wsp_disk_quota':
      case 'loc_zoom':
        return intval($value);
      // Needs to be 'y' / 'n' instead of boolean (Tip: You guessed it, because 'ILIAS' ...)
      case 'hide_own_online_status':
      case 'show_users_online':
        if (is_bool($value))
          return ($value) ? 'y' : 'n';
        if (is_string($value))
          return strtolower(substr($value, 0, 1));
        return $value;
      // Needs to be numeric (float)
      case 'latitude':
      case 'longitude':
        return floatval($value);
      // Needs to be an array
      case 'interests_general':
      case 'interests_help_offered':
      case 'interests_help_looking':
      case 'roles':
      case 'udf':
        if (!is_array($value))
          return array($value);
        return $value;
      // No transformation for any other field
      default:
        return $value;
    }
  }


  /**
   * Function: IsMissingField($field, $value, $mode, $refId)
   *
   *
   * Parameters:
   *  field <String> - User-data field to check if it is arequired field
   *  value <Any> - User-data field-value to check if it counts as missing
   *  mode <MODE_CREATE/MODE_UPDATE> - Different modes require different fields
   *  redId <Int> - refId of local category or USER_FOLDER_ID
   *
   * Returns:
   *  <Bool> True if field is required and missing, false otherwise
   */
  protected static function IsMissingField($field, $value, $mode, $refId) {
    // Check required fields for edit mode
    if ($mode == self::MODE_UPDATE) {
      // Check based on field
      switch ($field) {
        // User-ID is required
        case 'id':
          return !isset($value);
        // No other field is required in edit mode
        default:
          return false;
      }
    }

    // Check required fields for new user mode
    elseif ($mode == self::MODE_CREATE) {
      // Include required classes
      include_once('./Services/User/classes/class.ilUserDefinedFields.php');

      // Load ILIAS objects
      global $ilSetting;

      // Check based on field
      switch ($field) {
        // ID is created
        case 'id':
          return false;
        // This settings are always required
        case 'login':
        case 'passwd':
        case 'firstname':
        case 'lastname':
          return !isset($value);
        // Check User-Defined-Values against ILIAS settings
        case 'udf':
          $instance    = \ilUserDefinedFields::_getInstance();
          $definitions = ($refId == self::USER_FOLDER_ID) ? $instance->getDefinitions() : $instance->getChangeableLocalUserAdministrationDefinitions();
          foreach ($definitions as $defField => $definition)
            if ($definition['required'] && !array_key_exists($defField, $value))
              return true;
          return false;
        // Check all other values against ILIAS settings
        default:
          // Fetch ILIAS settings for checking requirement
          $settings = $ilSetting->getAll();

          // Check if missing and set to required
          $reqField = sprintf('require_%s', $field);
          return !isset($value) && array_key_exists($reqField, $settings) && $settings[$reqField] == 1;
      }
    }
  }


  /**
   * Function: IsValidField($field, $value, $mode, $refId)
   *  Checks if the given input-value is valid for its field.
   *
   * Parameters:
   *  field <String> - Field to check value for
   *  value <Mixed> - Value to be cheched
   *  mode <MODE_CREATE/MODE_UPDATE> - Creating or mofifying existing account
   *  refId <Int> - Reference-id of local category (or admin-panel)
   *
   * Return:
   *  <Bool> - True if value if valid for given field
   */
  protected static function IsValidField($field, $value, $mode, $refId) {
    // Load ILIAS objects
    global $lng;

    // Include required classes for validation
    include_once('./Services/Style/classes/class.ilObjStyleSettings.php');
    include_once('./Services/Style/classes/class.ilStyleDefinition.php');
    include_once('./Services/Authentication/classes/class.ilAuthUtils.php');
    include_once('./Services/User/classes/class.ilUserDefinedFields.php');

    //
    switch ($field) {
      // Validate login
      case 'login':
        return is_string($value) && \ilUtil::isLogin($value) && ($mode != self::MODE_CREATE || !\ilObjUser::_loginExists($value));
      // Validate password
      case 'passwd':
        return is_string($value) && \ilUtil::isPassword($value);
      // Validate email
      case 'email':
        return is_string($value) && \ilUtil::is_email($value);
      // Validate language
      case 'language':
        return is_string($value) && in_array($value, $lng->getInstalledLanguages());
      // Validate skin and style
      case 'skin_style':
        // Needs to be a string
        if (!is_string($value))
          return false;

        // Extract skin/style values
        $skin_style = explode(':', $value);
        $skin       = $skin_style[0];
        $style      = $skin_style[1];

        // Check wether skin is available and active
        return \ilStyleDefinition::styleExists($skin, $style) && \ilObjStyleSettings::_lookupActivatedStyle($skin, $style);
      // Validate authentication mode
      case 'auth_mode':
        $modes = \ilAuthUtils::_getActiveAuthModes();
        return $modes[$value] == 1;
      // Validate list of roles
      case 'roles':
        return is_array($value) &&  self::ValidateRoles($value, $refId);
      // Validate 'y' / 'n' values
      case 'hide_own_online_status':
      case 'show_users_online':
        return $value === 'y' || $value === 'n';
      // Validate integer values
      case 'id':
      case 'time_limit_from':
      case 'time_limit_until':
      case 'loc_zoom':
      case 'hits_per_page':
      case 'disk_quota':
      case 'wsp_disk_quota':
        return is_int($value);
      // Validate 0 / 1 values
      case 'time_limit_unlimited':
      case 'active':
      case 'session_reminder_enabled':
        return ($value === 0 || $value === 1);
      // Validate client ip
      case 'client_ip':
        return is_string($value) &&  (strlen($value) === 0 || filter_var($value, FILTER_VALIDATE_IP) !== false);
      // Validate string values
      case 'interests_general':
      case 'interests_help_offered':
      case 'interests_help_looking':
        return is_array($value);
      case 'institution':
      case 'department':
      case 'street':
      case 'city':
      case 'country':
      // TODO: Check sel_country via ilCountry::getCountryCodes()
      case 'sel_country':
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
      // Validate string / number values
      case 'matriculation':
      case 'zipcode':
        return is_string($value) || is_numeric($value);
      // Valiate float values
      case 'latitude':
      case 'longitude':
        return is_float($value);
      // Validate array values
      case 'udf':
        // Fetch valid definitions
        $instance    = \ilUserDefinedFields::_getInstance();
        $definitions = ($refId == self::USER_FOLDER_ID) ? $instance->getDefinitions() : $instance->getChangeableLocalUserAdministrationDefinitions();
        $definitions = array_map(function($definition) { return intval($definition['field_id']); }, $definitions);

        // Check for excess definitions
        foreach ($value as $udfField => $udfValue)
          if (!((is_int($udfField) || ctype_digit($udfField)) && in_array(intval($udfField), $definitions)))
            return false;

        return is_array($value);
      // Validate gender values
      case 'gender':
        return $value == 'm' || $value == 'f' || $value == 't';
      // Validate userfile data
      case 'userfile':
        return preg_match('#^data:image/\w+;base64,#i', $value) === 1;
      // Validate birthday format
      case 'birthday':
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value) === 1;
      // Validate boolean values
      case 'send_mail':
        return is_bool($value);
      // All other fields are valid by default
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
  public static function CheckUserData($userData, $mode = self::MODE_CREATE, $refId = self::USER_FOLDER_ID) {
    // TODO: Be more verbose about validation-issues, eg. existing 'login'

    // Set default values for (optional) missing parameters
    if ($mode == self::MODE_CREATE)
      foreach (self::fields as $field)
        if (!array_key_exists($field, $userData)) {
          $default = self::GetDefaultValue($field);
          if (isset($default))
            $userData[$field] = $default;
        }

    // Transform input values to be more flexible (transform time formats, string/booleans/integer as required)
    foreach ($userData as $field => $value)
      $userData[$field] = self::TransformField($field, $value);

    // Check all fields
    foreach (self::fields as $field)
      // Throw if field is required and missing
      if (self::IsMissingField($field,$userData[$field], $mode, $refId))
        throw new LibExceptions\Parameter(
          self::MSG_MISSING_FIELD,
          self::ID_MISSING_FIELD,
          array(
            'field' => $field
          )
        );

    foreach ($userData as $field => $value)
      // Check for invalid parameters
      if (!self::IsValidField($field, $value, $mode, $refId))
        throw new LibExceptions\Parameter(
          self::MSG_INVALID_FIELD,
          self::ID_INVALID_FIELD,
          array(
            'field' => $field,
            'value' => $userData[$field]
          )
        );

    // Return updated user data
    return $userData;
  }


  /**
   * Function: FetchUserData($userId, $refId)
   *  Returns user-data for the given ILIAS user.
   *
   * Paramters:
   *  userId <Int> - ILIAS user id to fetch data for
   *  refId <Int> - Ref-id for local user administration
   *
   * Return:
   *  <USER-DATA> - Fetched user-data for given ILIAS user
   */
  public static function FetchUserData($userId, $refId = self::USER_FOLDER_ID) {
    // Include required classes (who needs an AutoLoader/DI-System anyway?! -.-)
    include_once('./Services/Authentication/classes/class.ilAuthUtils.php');

    // Import ILIAS systems (all praise the glorious 'DI-System')
    global $rbacsystem, $rbacadmin, $rbacreview, $ilSetting, $ilUser;

    // Load user object
    $userObj = new \ilObjUser($userId);

    // Check for local administration access-rights (Note: getTimeLimitOwner() should be $refId for new users)
    if ($refId != USER_FOLDER_ID && !$rbacsystem->checkAccess('cat_administrate_users', $refId))
      throw new LibExceptions\RBAC(
        self::MSG_RBAC_EDIT_DENIED,
        self::ID_RBAC_EDIT_DENIED
      );
      // TODO: Validate that refId == time_limit_owner ?

    // Check for Admin-GUI access-rights to users
    if ($refId == USER_FOLDER_ID && !$rbacsystem->checkAccess('visible,read', $refId))
      throw new LibExceptions\RBAC(
        self::MSG_RBAC_READ_DENIED,
        self::ID_RBAC_READ_DENIED
      );

    // RefID must match time-limit owner
    if ($refId != $userObj->getTimeLimitOwner())
      throw new LibExceptions\RBAC(
        self::MSG_REFID_MISMATCH,
        self::ID_REFID_MISMATCH,
        array(
          owner  => $userObj->getTimeLimitOwner(),
          ref_id => $refId
        )
      );

    // Magnitude of byte units (1024)
    $magnitude = \ilFormat::_getSizeMagnitude();

    // TODO: Return time as ISO 6801 format, use ilFormat::formatDate($this->object->getCreateDate(),'datetime',true);

    // Collect user-data
    $userData                             = array();
    $userData['id']                       = $userId;
    $userData['roles']                    = $rbacreview->assignedRoles($userId);
    $userData['login']                    = $userObj->getLogin();
    $userData['time_limit_owner']         = $userObj->getTimeLimitOwner();
    $userData['owner']                    = $userObj->getOwner();
    $userData['owner_login']              = \ilObjUser::_lookupLogin($userData['owner']);
    $userData['auth_mode']                = $userObj->getAuthMode();
    $userData['client_ip']                = $userObj->getClientIP();
    $userData['active']                   = $userObj->getActive();
    $userData['time_limit_from']          = $userObj->getTimeLimitFrom();
    $userData['time_limit_until']         = $userObj->getTimeLimitUntil();
    $userData['time_limit_unlimited']     = $userObj->getTimeLimitUnlimited();
    $userData['interests_general']        = $userObj->getGeneralInterests();
    $userData['interests_help_offered']   = $userObj->getOfferingHelp();
    $userData['interests_help_looking']   = $userObj->getLookingForHelp();
    $userData['latitude']                 = $userObj->getLatitude();
    $userData['longitude']                = $userObj->getLongitude();
    $userData['loc_zoom']                 = $userObj->getLocationZoom();
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
    $userData['time_limit_message']       = $userObj->getTimeLimitMessage();
    $userData['profile_incomplete']       = $userObj->getProfileIncomplete();
    $userData['disk_quota']               = $userObj->getPref('disk_quota')     / $magnitude / $magnitude;
    $userData['wsp_disk_quota']           = $userObj->getPref('wsp_disk_quota') / $magnitude / $magnitude;
    $userData['session_reminder_enabled'] = $userObj->getPref('session_reminder_enabled');
    if (self::IsChangeable('language', $refId))
      $userData['language']               = $userObj->getLanguage();
    if (self::IsChangeable('birthday', $refId))
      $userData['birthday']               = $userObj->getBirthday();
		if (self::IsChangeable('gender', $refId))
			$userData['gender']                 = $userObj->getGender();
    if (self::IsChangeable('institution', $refId))
			$userData['institution']            = $userObj->getInstitution();
		if (self::IsChangeable('department', $refId))
			$userData['department']             = $userObj->getDepartment();
		if (self::IsChangeable('street', $refId))
			$userData['street']                 = $userObj->getStreet();
		if (self::IsChangeable('city', $refId))
			$userData['city']                   = $userObj->getCity();
		if (self::IsChangeable('zipcode', $refId))
			$userData['zipcode']                = $userObj->getZipcode();
		if (self::IsChangeable('country', $refId))
			$userData['country']                = $userObj->getCountry();
		if (self::IsChangeable('sel_country', $refId))
			$userData['sel_country']            = $userObj->getSelectedCountry();
		if (self::IsChangeable('phone_office', $refId))
			$userData['phone_office']           = $userObj->getPhoneOffice();
		if (self::IsChangeable('phone_home', $refId))
			$userData['phone_home']             = $userObj->getPhoneHome();
		if (self::IsChangeable('phone_mobile', $refId))
			$userData['phone_mobile']           = $userObj->getPhoneMobile();
		if (self::IsChangeable('fax', $refId))
			$userData['fax']                    = $userObj->getFax();
		if (self::IsChangeable('matriculation', $refId))
			$userData['matriculation']          = $userObj->getMatriculation();
		if (self::IsChangeable('hobby', $refId))
			$userData['hobby']                  = $userObj->getHobby();
		if (self::IsChangeable('referral_comment', $refId))
			$userData['referral_comment']       = $userObj->getComment();
    if (self::IsChangeable('delicious', $refId))
      $userData['delicious']              = $userObj->getDelicious();
    if (self::IsChangeable('hits_per_page', $refId))
      $userData['hits_per_page']          = $userObj->getPref('hits_per_page');
    if (self::IsChangeable('show_users_online', $refId))
      $userData['show_users_online']      = $userObj->getPref('show_users_online');
    if (self::IsChangeable('hide_own_online_status', $refId))
      $userData['hide_own_online_status'] = $userObj->getPref('hide_own_online_status');
		if (self::IsChangeable('email', $refId))
			$userData['email']                  = $userObj->getEmail();
    if (self::IsChangeable('skin_style', $refId))
      $userData['fullname']               = sprintf('%s:%s', $userObj->setPref('skin',  $skin), $userObj->setPref('style', $style));
    if (self::IsChangeable('instant_messengers', $refId)) {
      $userData['im_icq']                 = $userObj->getInstantMessengerId('icq');
      $userData['im_yahoo']               = $userObj->getInstantMessengerId('yahoo');
      $userData['im_msn']                 = $userObj->getInstantMessengerId('msn');
      $userData['im_aim']                 = $userObj->getInstantMessengerId('aim');
      $userData['im_skype']               = $userObj->getInstantMessengerId('skype');
      $userData['im_jabber']              = $userObj->getInstantMessengerId('jabber');
      $userData['im_voip']                = $userObj->getInstantMessengerId('voip');
    }
    if (self::IsChangeable('title', $refId)) {
      $userData['title']                  = $userObj->getUTitle();
      $userData['fullname']               = $userObj->getFullname();
    }
    if (self::IsChangeable('firstname', $refId)) {
      $userData['firstname']              = $userObj->getFirstname();
      $userData['fullname']               = $userObj->getFullname();
    }
    if (self::IsChangeable('lastname', $refId)) {
      $userData['lastname']               = $userObj->getLastname();
      $userData['fullname']               = $userObj->getFullname();
    }
    if (!$userObj->getActive())
      $userData['inactivation_date']      = $userObj->getInactivationDate();

    // Convert profile-picture to base64 encoded data
    if (self::IsChangeable('upload', $refId)) {
      $picturePath = $userObj->getPersonalPicturePath();
      if (is_string($picturePath)) {
        $type = pathinfo($picturePath, PATHINFO_EXTENSION);
        $data = file_get_contents($picturePath);
        if (is_string($type) && is_string($data) && strlen($data) > 0)
          $userData['upload'] = sprintf('data:image/%s;base64,%s', $type, base64_encode($data));
      }
    }

    // TODO: Values are returned unformated, convert 0/1/y/n to true/false, and numeric to int/float (depending on field) [reuse Transform method?]
    // udf[f_<id>] -> udf[<id>]

    // Return collected user-data
    return $userData;
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
   *  refId <Int> - Ref-id for local user administration
   *
   * Return:
   *  userfile <Bool> - Contains addition information if a virus was detected in the users profile-picture
   *  email <Bool> - Wether a notification email was send successfully...
   *  user <ilObjUser> - ILIAS user object that was created or updated
   */
  public static function StoreUserData($userData, $mode = self::MODE_CREATE, $refId = self::USER_FOLDER_ID) {
    // Include required classes (who needs an AutoLoader/DI-System anyway?! -.-)
    include_once('./Services/Authentication/classes/class.ilAuthUtils.php');
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
      // Check of user is allowd to create user globally or in given category/org-unit
      if (!$rbacsystem->checkAccess('create_usr', $refId) && !$ilAccess->checkAccess('cat_administrate_users', '', $refId))
        throw new LibExceptions\RBAC(
          self::MSG_RBAC_EDIT_DENIED,
          self::ID_RBAC_EDIT_DENIED
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

      // Check for local administration access-rights (Note: getTimeLimitOwner() should be $refId for new users)
      if ($refId != USER_FOLDER_ID && !$rbacsystem->checkAccess('cat_administrate_users', $refId))
        throw new LibExceptions\RBAC(
          self::MSG_RBAC_EDIT_DENIED,
          self::ID_RBAC_EDIT_DENIED
        );
        // TODO: Validate that refId == time_limit_owner ?

      // Check for Admin-GUI access-rights to users
      if ($refId == USER_FOLDER_ID && !$rbacsystem->checkAccess('visible,read', $refId))
        throw new LibExceptions\RBAC(
          self::MSG_RBAC_READ_DENIED,
          self::ID_RBAC_READ_DENIED
        );

      // RefID must match time-limit owner
      if ($refId != $userObj->getTimeLimitOwner())
        throw new LibExceptions\RBAC(
          self::MSG_REFID_MISMATCH,
          self::ID_REFID_MISMATCH,
          array(
            owner  => $userObj->getTimeLimitOwner(),
            ref_id => $refId
          )
        );

      // Update login of existing account
      if (self::HasUserValue($userData, 'login'))
        $userObj->updateLogin($userData['login']);
    }

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
    if (self::HasUserValue($userData, 'loc_zoom'))
      $userObj->setLocationZoom($userData['loc_zoom']);
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

    // Additional user value we could set (but don't)
    // $userObj->setAgreeDate($userData['agree_date']);
    // $userObj->setLastLogin($userData['last_login']);
    // $userObj->setApproveDate($userData['approve_date']);
    // $userObj->setPasswordEncodingType($userData['password_encoding_type']);
    // $userObj->setPasswordSalt($userData['password_salt']);
    // $userObj->setTimeLimitMessage($userData['time_limit_message']);
    // if ($userData['password_change'])
    //   $userObj->setLastPasswordChangeToNow();

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
    if (self::HasUserValue($userData, 'userfile') &&  self::IsChangeable('upload', $refId)) {
      $hasVirus = self::ProcessUserPicture($userObj, $userData['userfile']);
      if (isset($hasVirus))
        $result['userfile'] = $hasVirus;
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
      $result['email'] = $mail->send();
    }

    // Return on success with some additional information
    $result['user'] = $userObj;
    return $result;
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
  public function DeleteUser($userId,  $refId = self::USER_FOLDER_ID) {
    global $rbacsystem, $ilAccess, $ilUser;

    // Check if allowed to delete user
    if ($refId == self::USER_FOLDER_ID && !$rbacsystem->checkAccess('delete', $refId)
    ||  $refId != self::USER_FOLDER_ID && !$ilAccess->checkAccess('cat_administrate_users', '', $refId))
     throw new LibExceptions\RBAC(
       self::MSG_RBAC_DENIED,
       self::MSG_RBAC_DENIED
     );

    // Can't delte yourself
    if ($ilUser->getId() == $userId)
      throw new LibExceptions\RBAC(
        self::MSG_DELETE_SELF,
        self::ID_DELETE_SELF
      );

    // Check if given refid matches
    $userObj = new \ilObjUser($userId);
    if ($refId != $userObj->getTimeLimitOwner())
      throw new LibExceptions\RBAC(
        self::MSG_REFID_MISMATCH,
        self::ID_REFID_MISMATCH,
        array(
          owner  => $userObj->getTimeLimitOwner(),
          ref_id => $refId
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
  protected function HasUserValue($userData, $field) {
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
  protected function IsChangeable($field, $refId) {
    // Fetch reference to ILIAS settings
    global $ilSetting;

    // All settings can be changed via the admin-panel / for global accounts
    if ($refId == USER_FOLDER_ID)
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
    if (!isset($imgData))
      $userObj->removeUserPicture();

    // Create user pciture files (profile-pricutre and thumbnails)
    else {
      // Extract base64 encoded image data
      // TODO: Allow general image formats ('#^data:image/\w+;base64,#i')
      $encodedData = preg_replace('#^data:image/jpeg;base64,#i', '', $imgData);
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
    elseif (array_key_exists('date', $data) && array_key_exists('time', $data)) {
      $time = new \ilDateTime(sprintf('%s %s', $data['date'], $data['time']));
      return $time->get(IL_CAL_UNIX);
    }

    // Try to use DateTime to extract unix-time
    if (is_string($data)) {
      try {
        $date = new \DateTime($data);
        if ($date)
          return $date->getTimestamp();
      } catch (\Exception $e) { }
    }

    // Absolute fallback-case (should only happen on wrong input)
    return time();
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
    include_once('./Services/AccessControl/classes/class.ilObjRole.php');
    global $rbacreview;


    // Fetch list of assignable roles
    $local  = $rbacreview->getRolesOfRoleFolder($refId);
    $global = $rbacreview->getGlobalRoles();
    if ($refId != USER_FOLDER_ID)
      $global = array_filter($global, function($role) {
        return \ilObjRole::_getAssignUsersStatus($role);
      });
    $assignable = array_merge($local, $global);
    $assignable = array_map('intval', $assignable);

    // Check if all roles are assignable
    return count(array_diff($roles, $assignable)) == 0;
  }
}
