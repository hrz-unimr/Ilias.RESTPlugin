<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Class: RESTLib
 *  This class provides some common utility functions not
 *  directly related to any model.
 */
class RESTLib {
  /**
   * Function: getClientCertificate()
   *  Utility method to nicely fetch client-certificate (ssl) data from
   *  gfobal namespace and preformat it...
   *
   * Return:
   *  <Array[String]> - See below...
   */
  public static function FetchClientCertificate() {
    // Build a more readable ssl client-certificate array...
    return array(
      verify  => $_SERVER['SSL_CLIENT_VERIFY'],
      serial  => $_SERVER['SSL_CLIENT_M_SERIAL'],
      issuer  => $_SERVER['SSL_CLIENT_I_DN'],
      subject => $_SERVER['SSL_CLIENT_S_DN'],
      expires => $_SERVER['SSL_CLIENT_V_END'],
      ttl     => $_SERVER['SSL_CLIENT_V_REMAIN']
    );
  }


  /**
   * Function: FetchUserAgentIP()
   *  Return IP-Address of resource-owner user-agent.
   *  For Reverse-Proxied servers the workers require a module such as mod_rpaf
   *  that makes sure $_SERVER['REMOTE_ADDR'] does not contain the reverse-proxy
   *  but the user-agents ip.
   *
   * Return:
   *  <String> - IP-Address of resource-owner user-agent
   */
  public static function FetchUserAgentIP() {
    return $_SERVER['REMOTE_ADDR'];
  }


  /**
   * Function: CheckComplexRestriction($pattern, $subjects, $delimiter)
   *  Checks if the subjects element(s) are all convered by the restrictions given by pattern.
   *  Pattern can either be a regular expressen, in which case all elements of subject are preg_match()'ed
   *  or a string-list which must contain all subject elements. (List-Delimiter can be given as parameter)
   *  (Used to check if requested scope is covered by client-scope, ip and/or user is allowed to use a client, etc...)
   *
   * Parameters:
   *  $pattern <String> - A regular expression that all subject(s) need to match against or is string-list
   *                      with all must contain all subject(s)
   *  $subjects <String>/<Array[String]> - Subjects that should be checks for the restrictions given by pattern
   *  $delimiter <String> - Optional delimiter used to explode() the restriction-list (pattern) if not a regular-expression
   *
   * Return:
   *  <Boolean> - If ALL subjects are covered by pattern true will be returned, otherwise false
   */
  public static function CheckComplexRestriction($pattern, $subjects, $delimiter = ',') {
    // Treat all subjects as array (easer to read code...)
    if (!is_array($subjects))
      $subjects = array($subjects);

    // No pattern set -> no restriction set
    if (!isset($pattern) || $pattern === false || strlen($pattern) == 0)
      return true;

    // Restriction is given as regex?
    elseif (preg_match('/^\/.*\/$/', $pattern) == 1) {
      // Check if ALL given subjects match the given restriction
      foreach ($subjects as $subject)
        if (preg_match($pattern, $subject) != 1)
          return false;
    }

    // Restriction is given as (string-) list of strings
    else {
      // Extract list-items (string list with given delimiter)
      $patterns = explode($delimiter, $pattern);

      // Check if ALL given subjects match the given restriction
      foreach ($subjects as $subject)
        if (!in_array($subject, $patterns))
          return false;
    }

    // Not returned by now -> means all subjects matched given pattern/restrictions
    return true;
  }


  /**
   * Function: CheckSimpleRestriction($pattern, $subject)
   *  Checks if a given parameter (subject) matches the given setting (pattern)
   *  or if the settings is disabled anyway.
   *
   * Parameters:
   *  $pattern <String> - The settings that needs to be matched
   *  $subject <Boolean>/<String> - The parameter that need to match the given setting
   *
   * Return:
   *  <Boolean> True if subject matches pattern (or pattern or subject is disabled), false otherwise
   */
  public static function CheckSimpleRestriction($pattern, $subject) {
    if (!isset($pattern) || $pattern === false || strlen($pattern) == 0 || $pattern == $subject)
      return true;
    else
      return false;
  }
}
