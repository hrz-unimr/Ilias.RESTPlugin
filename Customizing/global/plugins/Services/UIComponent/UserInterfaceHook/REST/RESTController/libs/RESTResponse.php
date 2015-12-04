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

    // Set default format
    $this->setFormat('JSON');
  }


  /**
   * Function: write($body, $replace)
   *  Extends default write method with JSON support.
   *
   *  @See \Slim\Http\Response->write(...) for more details
   */
  public function write($body, $replace = false) {
    // Convert all non-emptry data to JSON
    if (isset($body) && $body != '')
      // Convert to desired format
      switch ($this->format) {
        case 'JSON':
          $payload = (is_string($body)) ? $body : json_encode($body);
          break;
        case 'RAW':
        case 'HTML':
        default:
          $payload = $body;
          break;
      }
    else
      $payload = $body;

    // Write (store) formated data and return original
    parent::write($payload, $replace);
    return $body;
  }


  /**
   * Function: getBody()
   *  Extends default getBody() method with JSON support.
   *
   *  @See \Slim\Http\Response->getBody() for more details
   */
  public function getBody() {
    // Fetch stored JSON data
    $payload = parent::getBody();

    // Convert from desired format
    switch ($this->format) {
      case 'JSON':
        return json_decode($payload, true);
      case 'RAW':
      case 'HTML':
      default:
        return $payload;
    }
  }


  /**
   * Function: body($body)
   *  Extends default body() method with JSON support.
   *  (DEPRECATED but still USED inside core SLIM ... -.-)
   *
   *  @See \Slim\Http\Response->body(...) for more details
   */
  public function body($body = null) {
    // Fetch stored JSON data
    $payload = parent::body($body);

    // This route will use return value of write() which is already decoded
    if (!is_null($body))
      return $payload;
    // While this will access body property directly... -.-
    else
      // Convert from desired format
      switch ($this->format) {
        case 'JSON':
          return json_decode($payload, true);
        case 'RAW':
        case 'HTML':
        default:
          return $payload;
      }
  }


  /**
   * Function finalize()
   *  Extends default finalize() method with JSON support.
   *
   *  @See \Slim\Http\Response->finalize() for more details
   */
  public function finalize()  {
    // Disable cookies for all rest responses
    header_remove('Set-Cookie');

    // Finalize response via parent
    list($status, $headers, $body) = parent::finalize();

    // Add correct header for format
    switch ($this->format) {
      case 'JSON':
        $headers->set('Content-Type', 'application/json');
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
   * Function: setFormat($format)
   *  Change format to given value, currently supported are:
   *   - JSON - @See https://de.wikipedia.org/wiki/JavaScript_Object_Notation
   *   - RAW - Do not modify in- or output (this means you can only pass strings to body(), getBody(), write(), etc.)
   *
   * Parameters:
   *  $format <String> - Desired output format
   */
  public function setFormat($format) {
    // Get unformated data
    $payload = $this->getBody();

    // Update internal format
    $this->format = strtoupper($format);

    // Format and store data
    $this->setBody($payload);
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
