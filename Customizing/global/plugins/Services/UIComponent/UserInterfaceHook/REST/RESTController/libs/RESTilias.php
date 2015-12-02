<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// This allows us to use shortcuts instead of full quantifier
// Requires <$ilDB>
use \RESTController\core\auth as Auth;


/**
 * Class: RESTilias
 *  This class provides some common utility functions in regards to
 *  fetching information from ILIAS but do not directly fit into any model.
 */
class RESTilias {
  // Allow to re-use status messages and codes
  const MSG_NO_OBJECT_BY_REF  = 'Could not find any ILIAS-Object with Reference-Id \'{{ref_id}}\' in database.';
  const ID_NO_OBJECT_BY_REF   = 'RESTController\\libs\\RESTilias::ID_NO_OBJECT_BY_REF';
  const MSG_NO_OBJECT_BY_OBJ  = 'Could not find any ILIAS-Object with Object-Id \'{{obj_id}}\' in database.';
  const ID_NO_OBJECT_BY_OBJ   = 'RESTController\\libs\\RESTilias::ID_NO_OBJECT_BY_OBJ';
  const MSG_NO_USER_BY_ID     = 'Could not find any user with id \'{{id}}\' in database.';
  const ID_NO_USER_BY_ID      = 'RESTController\\libs\\RESTilias::ID_NO_USER_BY_ID';
  const MSG_NO_USER_BY_NAME   = 'Could not find any user with name \'{{name}}\' in database.';
  const ID_NO_USER_BY_NAME    = 'RESTController\\libs\\RESTilias::ID_NO_USER_BY_NAME';

  // ILIAS-Admin must have this role (id) assigned to them
  const RBCA_ADMIN_ID = 2;


  /**
   * Static-Function: getPlugin()
   *  Returns RESTPlugin plugin object.
   *
   * Return:
   *  <ilComponent> - ILIAS Plugin-Object representing the RESTPlugin
   */
  public static function getPlugin() {
    // Fetch plugin object via plugin administration
    global $ilPluginAdmin;
    return $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, 'UIComponent', 'uihk', 'REST');
  }


  /**
   * Static-Function: initAccessHandling()
   *  Load ILIAS user-management. Normally this would be handled by initILIAS(),
   *  but CONTEXT:REST (intentionally) returns hasUser()->false. (Which causes
   *  initAccessHandling() [and authentification] to be skipped)
   *
   * @see ilInitialisation::initAccessHandling()
   */
  public static function initAccessHandling() {
    return ilInitialisation::initAccessHandling();
  }


  /**
   * Static-Function: loadIlUser($userId)
   *  Load ilObjUser for given user and attach to global $ilUser and $ilias->account.
   *  If no user-id is given, fetch the it from access-token.
   *
   * Parameters:
   *  $userId <Integer> - [Optional] Provider the user (id) who should be loader into ilUser.
   *                      Leave blank to load user from available access-token.
   *                      Important: Without $userId, requires the access-token to have been loaded.
   *
   * Returns:
   *  <ilObjUser> - Global ilUser object use by ILIAS
   */
  public static function loadIlUser($userId = null) {
    // Include ilObjUser and initialize
    ilInitialisation::initGlobal('ilUser', 'ilObjUser', './Services/User/classes/class.ilObjUser.php');

    // Fetch user-id from access-token if non is given
    if (!isset($userId)) {
      $accessToken  = Auth\Util::getAccessToken();
      $userId       = $accessToken->getUserId();
    }

    // Create user-object if id is given
    global $ilUser, $ilias;
    $ilUser->setId($userId);
    $ilUser->read();

    // Initialize access-handling and attach account
    self::initAccessHandling();
    $ilias->account = $ilUser;

    // Return $ilUser (reference)
    return $ilUser;
  }


  /**
   * Static-Function: authenticate($username, $password)
   *  Authentication via the ILIAS Auth mechanisms.
   *  This method is used as backend for OAuth2.
   *
   * Parameters:
   *  $username - ILIAS username to check
   *  $password - ILIS password of user to check
   *
   * Return:
   *  <Boolean> - True if authentication was successfull, false otherwise
   */
  public static function authenticate($username, $password) {
    // Initilaize role-base access-control
    self::initAccessHandling();

    // Well, its ILIAS, so there is no way to check username/password pair...
    // 'Lets just pretend we have filled out a login-form' -.-
    $_POST['username'] = $username;
    $_POST['password'] = $password;

    // Initilaize ILIAS authentication
    require_once('Services/User/classes/class.ilObjUser.php');
    require_once('Services/Authentication/classes/class.ilAuthUtils.php');
    \ilAuthUtils::_initAuth();

    // Check authentification
    global $ilAuth;
    $ilAuth->start();
    $checked_in = $ilAuth->getAuth();

    // Remove all dump created by ILIAS
    $ilAuth->logout();
    session_destroy();
    header_remove('Set-Cookie');

    // Return login-state
    return $checked_in;
  }


  /**
   * Static-Function: isAdmin($userId)
   *  Checks if given user owns the administration role in ILIAS.
   *  If no user-id is given, fetch the it from access-token.
   *
   * Parameters:
   *  $userId <Integer> - [Optional] User (id) that should be checked for admin-role.
   *                      Leave blank to load user from available access-token.
   *                      Important: Without $userId, requires the access-token to have been loaded.
   *
   * Return:
   *  <Boolean> - True if the given user havs the admin-role in ILIAS, false otherwise
   */
  public static function isAdmin($userId) {
    // Load role-based access-control review-functions
    require_once('./Services/AccessControl/classes/class.ilRbacReview.php');

    // Fetch user-id from access-token if non is given
    if (!isset($userId)) {
      $accessToken  = Auth\Util::getAccessToken();
      $userId       = $accessToken->getUserId();
    }

    // Check wether a given user has the admin-role
    if ($userId) {
      $rbacreview = new \ilRbacReview();
      $admin      = $rbacreview->isAssigned($userId, self::RBCA_ADMIN_ID);
      return $admin;
    }

    // No username given -> can't obviously be admin
    return false;
  }


  /**
   * Static-Function: getObjId($refId)
   *  Fetches the Object-Id (obj_id) of any ILIAS-Object given one of its Reference-Ids (ref_id).
   *
   * Parameters:
   *  $refId <Integer> - Reference-Id of ILIAS-Object for which the Object-Id should be queried
   *
   * Return:
   *  <Integer> - Object-Id represented by given Reference-Id
   */
  public static function getObjId($refId) {
    // Make global ilDB locally accessable
    global $ilDB;

    // Query object by its refence-id
    $sql    = RESTDatabase::safeSQL('SELECT obj_id FROM object_reference WHERE ref_id = %d', intval($refId));
    $query  = $ilDB->query($sql);
    $row    = $ilDB->fetchAssoc($query);

    // Found match?
    if ($row)
      return $row['obj_id'];

    // Otherwise throw exception
    throw new Exceptions\ilObject(
      self::MSG_NO_OBJECT_BY_REF,
      self::ID_NO_OBJECT_BY_REF,
      array(
        'ref_id' => $refId
      )
    );
  }


  /**
   * Static-Function: getRefId($objId)
   *  Fetches the Reference-Id (ref_id) of any ILIAS-Object given its Object-Id (obj_id).
   *
   * Parameters:
   *  $objId <Integer> - Object-Id of ILIAS-Object for which the Reference-Id should be queried
   *
   * Return:
   *  <Integer> - Reference-Id representing a given Object-Id
   */
  public static function getRefId($objId) {
    // Make global ilDB locally accessable
    global $ilDB;

    // Query object by its refence-id
    $sql    = RESTDatabase::safeSQL('SELECT ref_id FROM object_reference WHERE obj_id = %d', intval($objId));
    $query  = $ilDB->query($sql);
    $row    = $ilDB->fetchAssoc($query);

    // Found match?
    if ($row)
      return $row['ref_id'];

    // Otherwise throw exception
    throw new Exceptions\ilObject(
      self::MSG_NO_OBJECT_BY_OBJ,
      self::ID_NO_OBJECT_BY_OBJ,
      array(
        'obj_id' => $objId
      )
    );
  }


  /**
   * Static-Function: getRefIds($objId)
   *  Fetches all Reference-Ids (ref_id) of any ILIAS-Object given its Object-Id (obj_id).
   *
   * Parameters:
   *  $objId <Integer> - Object-Id of ILIAS-Object for which the Reference-Id should be queried
   *
   * Return:
   *  <Array[Integer]> - Array of Reference-Ids representing a given Object-Id
   */
  public static function getRefIds($objId) {
    // Make global ilDB locally accessable
    global $ilDB;

    // Query object by its refence-id
    $sql = RESTDatabase::safeSQL('SELECT ref_id FROM object_reference WHERE obj_id = %d', $objId);
    $query = $ilDB->query($sql);

    // Fetch all rows with matching obj_id
    $rows = array();
    while($row = $ilDB->fetchAssoc($query))
        $rows[] = $row['ref_id'];

    // return collected rows (each containing a ref_id)
    return $rows;
  }


  /**
   * Static-Function: getUserName($userId)
   *  Given a users id, this function returns the ilias login name of a user.
   *
   * Parameters:
   *  $userId <Integer> - User-id for user who's name should be fetched (login-name, not real-name)
   *
   * Return:
   *  <String> - Fetched user-name for given user-id
   */
  public static function getUserName($userId) {
    // Make global ilDB locally accessable
    global $ilDB;

    // Query user by his user-id
    $sql    = RESTDatabase::safeSQL('SELECT login FROM usr_data WHERE usr_id = %d', $userId);
    $query  = $ilDB->query($sql);
    $row    = $ilDB->fetchAssoc($query);

    // Found match?
    if ($row)
        return $row['login'];

    // Otherwise throw exception
    throw new Exceptions\ilUser(
      self::MSG_NO_USER_BY_ID,
      self::ID_NO_USER_BY_ID,
      array(
        'id' => $userId
      )
    );
  }


  /**
   * Static-Function: getUserId($userName)
   *  Given a users name, this function returns his user-id.
   *
   * Parameters:
   *  $userName <String> - User-name for user who's id should be fetched
   *
   * Return:
   *  <Integer> - Fetched user-id for given user-name (login-name, not real-name)
   */
  public static function getUserId($userName) {
    // Make global ilDB locally accessable
    global $ilDB;

    // Query user by his user-name
    $sql    = RESTDatabase::safeSQL('SELECT usr_id FROM usr_data WHERE login = %s', addslashes($userName));
    $query  = $ilDB->query($sql);
    $row    = $ilDB->fetchAssoc($query);

    // Found match?
    if ($row)
      return $row['usr_id'];

    // Otherwise throw exception
    throw new Exceptions\ilUser(
      self::MSG_NO_USER_BY_NAME,
      self::ID_NO_USER_BY_NAME,
      array(
        'name' => $userName
      )
    );
  }
}


/**
 * Class: ilInitialisation_Public
 *  Helper class that derives from ilInitialisation in order
 *  to 'publish' some of its methods that are (currently)
 *  required by RESTilias (some routes/models).
 *
 *  We aren't extending RESTilias directly for two reasons:
 *   - Keep the RESTilias as clean as possible of any ILIAS code/method
 *     (Reduce dependencies as much as possible)
 *   - PHP does not allow multiple inheritance (IFF we ever really
 *     needed to access another classes protected methods)
 *
 * !!! DO NOT USE THIS CLASS OUTSIDE OF RESTLIB !!!
 */
require_once('./Services/Init/classes/class.ilInitialisation.php');
class ilInitialisation extends \ilInitialisation {
  /**
   * Function; initGlobal($a_name, $a_class, $a_source_file)
   *  Derive from protected to public...
   *
   * @see \ilInitialisation::initGlobal($a_name, $a_class, $a_source_file)
   */
  public static function initGlobal($a_name, $a_class, $a_source_file = null) {
    return parent::initGlobal($a_name, $a_class, $a_source_file);
  }


  /**
   * Function: initAccessHandling()
   *  Derive from protected to public...
   *
   * @see \ilInitialisation::initAccessHandling()
   */
  public static function initAccessHandling() {
    return parent::initAccessHandling();
  }
}
