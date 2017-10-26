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
 * Class Redirect
 *  This model provides methods to generate permanent-links
 *  to ILIAS-Objects as well as functions to creat new
 *  ILIAS-Sessions on the server while also fetching
 *  the authentification-cookies usable by a web-client.
 */
class Redirect extends Libs\RESTModel {
  // Variables to controll the short-lived access-token
  const shortTTL       = 30;
  const challengeSize  = 25;


  /**
   * Function: checkClientResponse($cr, $cc, $sc)
   *  Checks the response given by the client to the request
   *  asked by the server.
   *
   * Parameters:
   *  $cr <String> - Client-Response (to server-challange)
   *  $cc <String> - Original Client-Challenge
   *  $sc <String> - Original Server-Challange
   *
   * Returns:
   *  <Boolean> - True if challenge was answered correctly by client
   */
  public static function checkClientResponse($cr, $cc, $sc) {
    // Fetch refresh-token
    $refreshToken = Auth\RefreshEndpoint::getRefreshToken($accessToken, false);

    // Reconstruct own version of client-response
    $cr_test  = hash('sha256', $cc . $sc . $refreshToken->getTokenString());

    // Compare client-repsonse with expected response
    return (strcmp($cr, $cr_test) == 0);
  }


  /**
   * Function: updateAccessToken($accessToken)
   *  Generates a short-live access-token from the
   *  initial access-token. This will only last
   *  for a few seconds.
   *
   * Parameters:
   *  $accessToken <AcessToken> - Original Access-Token
   *
   * Returns:
   *  <AcessToken> - Updated Access-Token
   */
  public static function updateAccessToken($accessToken) {
    // Modify token
    $accessToken->setEntry('ttl',  strval(time() + self::shortTTL));
    $accessToken->setEntry('type', 'short-token');
    $accessToken->setEntry('misc',  Libs\RESTLib::FetchUserAgentIP());

    // Return modified access-token
    return $accessToken;
  }


  /**
   * Function: answerClientChallange($cc)
   *  Generates a server-challenge and a server-response
   *  for the client-challenge.
   *
   * Parameters:
   *  $cc <String> - Client-Challenge to answer
   *
   * Returns:
   *  server_challenge <String> - The challange that needs to be answered by the client to authenticate
   *  server_response <String> - The response for the client-challenge by the server
   */
  public static function answerClientChallange($cc) {
    // Fetch refresh-token
    $refreshToken = Auth\RefreshEndpoint::getRefreshToken($accessToken, false);

    // Generate server-challenge and server response
    $sc     = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789', 5)), 0, self::challengeSize);
    $sr     = hash('sha256', $sc . $cc . $refreshToken->getTokenString());

    /*
     * Note:
     *  The server SHOULD store $cr and $sc into the database.
     *  Otherwise he has to trust the client to re-send him
     *  those values unmodified.
     */

    // Answer client-challenge
    return array(
      'server_challenge'  => $sc,
      'server_response'   => $sr,
    );
  }


  /**
   * Function: getLink($refId, $type)
   *  Generate a permanent-link from given Reference-Id and Object-Type.
   *
   * Parameters:
   *  $refId <Integer> - Reference-Id of requests resource
   *  $type <String> - Object-Type of requested object
   *
   * Return:
   *  <String> - Permament-link to resource given by Reference-Id
   */
  public static function getLink($refId, $type) {
    // Check for special requests (so called root-objects)
    if (is_string($refId) && $refId == $type) {
      switch(strtolower($type)) {
        case 'desktop':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmd=jumpToSelectedItems';
        case 'courses-groups':
        case 'courses':
        case 'groups':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmd=jumpToMemberships';
        case 'bookmarks':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilbookmarkadministrationgui&cmdNode=tn:j';
        case 'notes-comments':
        case 'notes':
        case 'comments':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilpdnotesgui&cmdNode=tn:6u';
        case 'workspace':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilpersonalworkspacegui&cmdNode=tn:oe';
        case 'calendar':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilcalendarpresentationgui&cmdNode=tn:73';
        case 'mail':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilMailGUI&cmdClass=ilmailfoldergui&cmdNode=83:85';
        case 'contacts':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilmailaddressbookgui&cmdNode=tn:4w';
        case 'profile':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilpersonalprofilegui&cmdNode=tn:pv';
        case 'settings':
          return ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilPersonalDesktopGUI&cmdClass=ilpersonalsettingsgui&cmdNode=tn:q0';
      }
    }
    // Generate link from refId and object-type
    else {
      include_once('Services/Link/classes/class.ilLink.php');
      return \ilLink::_getLink($refId, $type);
    }
  }


  /**
   * Function: createSession($username)
   *  Create a new session on the side of ILIAS,
   *  this maily means generating a new entry
   *  in the usr_session table that cen be used
   *  together with a client-side cookie to authenticate
   *  a user.
   *
   * Note:
   *  This call also generates cookie-headers that
   *  will tell the client-side to create those cookies,
   *  but this does not play well with how Slim manages
   *  responses in general.
   *  (Either die() or use self::getSessionCookies())
   *
   * Parameters:
   *  $username <String> - The login-name of the user who's session should be created
   */
  public static function createSession($userName) {
    // Init authentification
    require_once('Services/Authentication/classes/class.ilAuthUtils.php');
    \ilAuthUtils::_initAuth();

    // Authenticate user (this will generate a session in usr_session and send PHPSESSID cookie)
    global $ilAuth;
    $ilAuth->setAuth($userName);
    $ilAuth->start();

    // TODO: Check if there is already an existent/stored session -> delete old one (otherwise database could get bloated!)
  }


  /**
   * Function: getSessionCookies()
   *  Extracts the cookie-header generated by $ilAuth
   *  such that they can be send via Slim instead.
   *  [die() 'ing works also, but seems to be bad practice...]
   *
   * Note:
   *  Should return 'PHPSESSID' and 'authchallenge' cookie.
   *
   * Return:
   *  <Array[
   *   key <String> - Name of session-cookie
   *   value <String> - Value of session-cookie
   *   expires <Number> - Expirition-date of session-cookie
   *   path <String> -  path of session-cookie
   *  ]
   */
  public static function getSessionCookies() {
    // Used to return session-cookies
    $result = array();

    // Fetch headers (waiting to be) send by php
    $headers = headers_list();
    foreach ($headers as $header)
      // We are only looking for cookies
      if (substr($header, 0, 12) === 'Set-Cookie: ') {
        // Extract cookie-settings
        $cookie   = array();
        $settings = explode(';', substr($header, 12));
        foreach ($settings as $key => $value) {
          $value              = trim($value);
          $pairs              = explode('=', $value);
          $cookie[$pairs[0]]  = $pairs[1];
        }

        // Got session-cookie?
        if (array_key_exists('PHPSESSID', $cookie))
          $result[] = array(
            'key'     => 'PHPSESSID',
            'value'   => $cookie['PHPSESSID'],
            'expires' => 0,
            'path'    => $cookie['path']
          );
        elseif (array_key_exists('authchallenge', $cookie))
          $result[] = array(
            'key'     => 'authchallenge',
            'value'   => $cookie['authchallenge'],
            'expires' => 0,
            'path'    => $cookie['path']
          );
      }

    // Return session cookies
    return $result;
  }
}
