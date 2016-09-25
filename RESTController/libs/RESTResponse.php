<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// Requires ../Slim/Http/Response


/**
 * Class: RESTResponse
 *  Extends Slims default response-object to support
 *  JSON output.
 *  (Or maybe even xml which is currently not implemented)
 *
 * Here are some HTTP-Codes, used throughout the RESTController
 *  - 200 OK                    - For successfull requests,
 *  - 400 Bad Request           - Wrong (input) syntax, eg. missing manditory parameter
 *  - 401 Not Authorized        - Authorization has failed, eg. invalid token
 *  - 403 Forbidden             - User could not be authorized due du missing permissions
 *  - 422 Unprocessable Entity  - Correct (input) syntax, but wrong data, eg wrong input format
 *  - 404 Not Found             - No route here or wrong URI (maybe missing url-parameter)
 *  - 500 Server Fault          - Issue on the server-side, eg. failed SQL query
 * Additionally all non 200-responses should additionally contain a
 * more detailed REST-Code (machine-readable).
 */
class RESTResponse extends \Slim\Http\Response {
  // Allow to re-use status messages and codes
  const MSG_UNSOPPORTED_RESPONSE_CONTENT  = 'Cannot send non-string RESTResponse when using \'{{format}}\' format.';
  const ID_UNSOPPORTED_RESPONSE_CONTENT   = 'RESTController\\libs\\RESTResponse::ID_UNSOPPORTED_RESPONSE_CONTENT';

  // Stores the current active format
  protected $format;


  /**
   * Constrcutor: RESTResponse($body, $status, $headers)
   *  Creates a new response object. This should really only be called
   *  once by either SLIM or the RESTController.
   *  Access the singletom instance of the created object via $app->response().
   *
   * @See SLIM->__construct() for additional information.
   */
  public function __construct($body = '', $status = 200, $headers = array()) {
    // Call parent constrcutor first
    parent::__construct($body, $status, $headers);
  }


  /**
   * Function: write($body, $replace)
   *  Extends default write method with JSON support.
   *
   *  @See \Slim\Http\Response->write(...) for more details
   */
  public function write($body, $replace = false) {
    // Merged new body with old content
    if ($replace === false) {
      // Decode old content
      $oldBody = $this->decode($this->getBody());

      // Can only merge two arrays
      if (is_array($oldBody) && is_array($body))
        $body    = $oldBody + $body;
      // Cannot be merged, keep old content
      elseif ($oldBody !== null)
        $body    = $oldBody;

      // Manually merged => replace old content
      $replace = true;
    }

    // Write new body as is?
    if (!isset($body) || is_string($body))
      return parent::write($body, $replace);
    // Write new encoded body
    else
      return parent::write($this->encode($body), $replace);
  }


  /**
   * Function finalize()
   *  Extends default finalize() method with JSON support.
   *
   *  @See \Slim\Http\Response->finalize() for more details
   */
  public function finalize()  {
    // Notiz: Aus irgendeinem grund ist body hier doppelt, obwohl es in write (und überall sonstwo einfach ist)
    list($status, $headers, $body) = parent::finalize();

    // Add correct header according to format
    switch ($this->format) {
      case 'JSON':
        $headers->set('Content-Type', 'application/json');
        break;
      case 'XML':
        $headers->set('Content-Type', 'application/xml');
        break;
      case 'HTML':
        $headers->set('Content-Type', 'text/html');
        break;
      case 'RAW':
      default:
        $headers->set('Content-Type', 'text/plain');
        break;
    }

    // Add WWW-Authenticate header for 401 responses
    // to indicate required authorization
    if ($status == 401)
      $headers->set('WWW-Authenticate', sprintf('Bearer realm="%s"', $_SERVER['SERVER_NAME']));

    // Return updated response
    return array($status, $headers, $body);
  }


  /**
   * Function: Encode($body)
   *  Encodes the given body using the current format
   *  and returns it. (Errors of output is non a string!)
   *
   * Parameters:
   *  body <Mixed> - Input to be converted
   *
   * Return:
   *  <String> - Converted input according to format setting
   */
  public function encode($body) {
    // Add correct header according to format
    switch ($this->format) {
      case 'JSON':
        $result = json_encode($body);
        break;
      case 'XML':
        $result = RESTLib::Array2XML($body);
        break;
      case 'HTML':
      case 'RAW':
      default:
        $result = $body;
        break;
    }

    // Final encoded 'object' musst be of type string
    // Should only trigger when using RAW/HTML format with non-string body...
    if (!is_string($result)) {
      // Switch to JSON on error
      $format = $this->getFormat();
      $this->setFormat('json');

      // Throw error and terminate
      throw new Exceptions\Parameter(
        self::MSG_UNSOPPORTED_RESPONSE_CONTENT,
        self::ID_UNSOPPORTED_RESPONSE_CONTENT,
        array(
          'content' => $result,
          'format'  => strtolower($format)
        )
      );
    }

    // Return result-object
    return $result;
  }


  /**
   * Function: Decode($body)
   *  Decodes the given body using the current format
   *  and returns it.
   *
   * Parameters:
   *  body <Mixed> - Input to be converted
   *
   * Return:
   *  <String> - Converted input according to format setting
   */
  public function decode($body) {
    // Add correct header according to format
    switch ($this->format) {
      case 'JSON':
        return json_decode($body, TRUE);
      case 'XML':
        return RESTLib::XML2Array($body);
      case 'HTML':
      case 'RAW':
      default:
        return $body;
    }
  }


  /**
   * Function: setFormat($format)
   *  Change format to given value, currently supported are:
   *   - JSON - @See https://de.wikipedia.org/wiki/JavaScript_Object_Notation
   *   - RAW - Do not modify in- or output (this means you can only pass strings to body(), getBody(), write(), etc.)
   *
   * Parameters:
   *  $format <String> - Desired output format
   */
  public function setFormat($format) {
    // Get decoded content
    $payload = $this->decode($this->getBody());

    // Update internal format
    $this->format = strtoupper($format);

    // Update content using new format
    $this->setBody($this->encode($payload));
  }


  /**
   * Function: getFormat()
   *  Returns the current output format, see RESTResponse->setFormat() for supported formats.
   *
   * Return:
   *  <String> - Current output format.
   */
  public function getFormat() {
    // Return internal format
    return $this->format;
  }


  /**
   * Function: noCache($reset)
   *  Disable all http cache settings. Should stop any
   *  decend client/server from caching (thus potentially exposing)
   *  any data contained in the response.
   *
   * Parameters:
   *  $reset <Boolean> - Remove all cache restriction
   */
  public function noCache($reset = false) {
    // Remove all cache restrictions (headers)
    if ($reset) {
      $this->headers->remove('Cache-Control');
      $this->headers->remove('Pragma');
      $this->headers->remove('Expires');
    }
    // Add caching restrictions (headers)
    else
      $this->headers->replace(array(
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma'        => 'no-cache',
        'Expires '      => 0
      ));
  }


  /**
   * Function: responseObject($data, $status)
   *  Creates a responseObject from given $data and $status
   *  Should be used whenever someone wants to emulate
   *  $app->success(...) or $app->halt(...) response
   *  without actually transmitting and terminating with
   *  said response.
   *
   * Parameters:
   *  $data <Mixed> - Data that should be send, should be an Array or a String
   *  $status <String> -
   *
   * Return:
   *  <Array[Mixed]> - Specially constructed response-object (eg. used by $app->halt())
   */
  public static function responseObject($data, $status) {
    // Add a status-code to response object?
    if ($status != null) {
      // Do NOT overwrite status key inside $data
      if (is_array($data))
        $data['status'] = ($data['status']) ?: $status;

      // If data is not empty, construct array with status and original data
      elseif ($data != '')
        $data = array(
          'status'  => $status,
          'message' => $data
        );
    }

    // Return formated response-object
    return $data;
  }
}
