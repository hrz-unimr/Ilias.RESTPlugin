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


  /**
   * Static-Function: Array2XML($array)
   *  Converts the given input assoziative array to an xml
   *  string-representation of said array.
   *
   * Parameters:
   *  simpleXML <SimpleXMLElement> - Assoziative array to be converted
   *
   * Return:
   *  <String> - XML string-representation of converted array
   */
  public static function Array2XML($array) {
    // Create new DomDocument and append data
    $xml  = new \DOMDocument('1.0', 'utf-8');
    $node = $xml->createElement('payload');
    $xml->appendChild($node);

    // Build XML if there is data
    if (isset($array) && is_array($array) && count($array) > 0)
      self::Array2XML_Recursive($xml, $node, $array);

    // Convert DomDocument to string
    return $xml->saveXML();
  }


  /**
   * Static-Function: Array2XML_Recursive($xml, $root, $mixed)
   *  Recursively convert arrayto an xml string. Does not return
   *  anything but instead manipulates the $root parameter.
   *
   * Parameters:
   *  $xml <DOMDocument> - Instance of DOMDocument (used for construction)
   *  $root <DOMElement> - Current root element to append values to
   *  $mixed <Mixed> - Array to be converted
   */
  protected static function Array2XML_Recursive($xml, $root, $mixed) {
    // Need to iterate over mixed?
		if (is_array($mixed)) {
      // Ensure array is sorted by key
      ksort($mixed);

      // Iterate over keys
			foreach ($mixed as $key => $value) {
        // Convert underscores to dashes
        $key = str_replace('_', '-', $key);

        // Try to use keys as tags...
        if (preg_match('/\A(?!XML)[a-z][\w0-9-]*/i', $key)) {
          $node = $xml->createElement($key);
          $root->appendChild($node);
        }
        // Key is an REAL positive integer-value (without 0 prefix)
        elseif (preg_match('/^(0|([1-9][0-9]*))$/i', $key)) {
          $key = intval($key);
          if ($key == 0)
						$node = $root;
					else {
						$node = $xml->createElement($root->tagName);
						$root->parentNode->appendChild($node);
					}
        }
        // ...otherwise fallback to <item key=$key>
        else {
          $node = $xml->createElement('item');
          $node->setAttribute('key', $key);
          $root->appendChild($node);
        }

        // Build XML ecursively
				self::Array2XML_Recursive($xml, $node, $value);
			}
		}
    // Create a text-node representation
    else {
      $text = $xml->createTextNode($mixed);
			$root->appendChild($text);
		}
	}


  /**
   * Static-Function: XML2Array($string)
   *  Converts the imput string to an assoziative array.
   *
   * Parameters:
   *  $string <String> - String representation of XML data
   *
   * Return:
   *  <Array> - Assoziative array representing the input xml object
   */
  public static function XML2Array($string) {
    if (isset($string) && is_string($string) && strlen($string) > 0) {
      // Set new error-handler to catch otherwise non catchable xml parsing errors (because PHP...)
      set_error_handler(array('\RESTController\libs\RESTLib', 'HandleXmlError'));

      // Parse XML to array
      $xml = new \DOMDocument();
      $xml->loadXML($string);
      $array = self::XML2Array_Recursive($xml->documentElement);

      // Restore original error-handler and return decoded xml
      restore_error_handler();
      return $array;
    }

    // Nothing to decode?!
    return array();
  }


  /**
   * Static-Function: XML2Array_Recursive($simpleXML)
   *  Recursively convert SimpleXMLElement object to
   *  an assoziative array.
   *
   * Parameters:
   *  simpleXML <SimpleXMLElement> - XML object to convert to array
   *
   * Return:
   *  <Array> - Assoziative array representing the input xml object
   */
  protected static function XML2Array_Recursive($node) {
    // Iterate over XML-Elements
    if ($node->hasChildNodes() && $node->nodeType == XML_ELEMENT_NODE) {
      $tags   = array();

      // Fill result array
      foreach ($node->childNodes as $child) {
        // Fetch values recursively
        $value = self::XML2Array_Recursive($child);

        // Its an xml-elemt with additional sub-elements
        if ($child->nodeType == XML_ELEMENT_NODE) {
          // Extract array keys from tagname or key-attribute as fallback
          // Convert dashed to underscores (since most routes use underscores parameters and no dashes), use <item key=''> syntax to keep dashes
          $key = ($child->hasAttributes() && $child->tagName == 'item' && $child->getAttribute('key') != '') ? $child->getAttribute('key') : $child->tagName;

          // Append value(s) to tags
          $tags[$key] = isset($tags[$key]) ? array_merge_recursive((array) $tags[$key], (array) $value) : $value;
        }
        // XML-Element (text-) content
        else
          $values = isset($values) ? array_merge_recursive((array) $values, (array) $value) : $value;
      }

      // We don't support mixed tags and values, return only tags
      if (count($tags) > 0)
        return $tags;

      // Return values if no tags have been set
      return $values;
    }
    // Return single value
    else
      return $node->nodeValue;
  }


  /**
   * Function: HandleXmlError($errno, $errstr, $errfile, $errline)
   *  ErroHandler that throws a catchable exception. Used to handle
   *  xml parsing errors since they are otherwise not catchable...
   */
  protected static function HandleXmlError($errno, $errstr, $errfile, $errline) {
    if ($errno == E_WARNING && substr_count($errstr, "DOMDocument::loadXML()") > 0)
      throw new DOMException($errstr);
    else
      return false;
 }
}
