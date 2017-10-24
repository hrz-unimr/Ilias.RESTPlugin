<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// Requires RESTController


/**
 * Class: RESTException
 *  This is the base-class of all exceptions thrown by the RESTController itself
 *  This does not include the ones thrown by PHP or ILIAS.
 *  Do not use this class directly, but derive your own (customized)
 *  implementations to allow different exceptions to be distinguished.
 *
 * @See http://php.net/manual/de/class.exception.php for additional methods.
 */
class RESTException extends \Exception {
  // Error-Type used for redirection
  // See https://tools.ietf.org/html/rfc6749#section-5.2
  protected static $errorType = 'server_error';

  // All RESTException can optionally have an attached 'rest-code'
  // Unlike the default exception-code, this can be non-numeric!
  protected $restCode = 0;
  protected $httpCode = null;
  protected $restData = array();


  /**
   * Constructor: RESTException($message, $restCode, $restData, $previous)
   *  Creates a new instance of this exception which can either
   *  be thrown or used to pass data along.
   *
   * Parameters:
   *  $message <String> - A human-readable message about the cause of the exception
   *  $restCode <String> - [Optional] A machine-readable identifier for the cause of the exception
   *  $restData <Array[Mixed]> - [Optional] Optional data that should be attached to the exception. (Must be an array!)
   *  $previous <Exception> - [Optional] Attach previous exception that caused this exception
   */
  public function __construct ($message, $restCode = 0, $restData = null, $httpCode = null, $previous = NULL) {
    // Call parent constructor
    $message = self::format($message, $restCode, $restData);
    parent::__construct ($message, 0, $previous);

    // Store data
    if (is_array($restData))
      $this->restData = $restData;

    // This internal values
    $this->restCode = $restCode;
    $this->httpCode = $httpCode;
  }


  /**
   * Function: getRESTMessage()
   *  Returns the formated message.
   *  (Wrapper around getMessage() to allow overwriting)
   *
   * Return:
   *  <String> - (Formated) Message attached to this exception
   */
  public function getRESTMessage() {
    // Return already formated exception-message
    return $this->getMessage();
  }


  /**
   * Function: getRESTCode()
   *  Returns the REST-code attached to this exception.
   *
   * Return:
   *  <String> - REST-Code that was attched to this exception.
   */
  public function getRESTCode() {
    // Return internal rest-code
    return $this->restCode;
  }
  public function getHTTPCode() {
    // Return internal rest-code
    return $this->httpCode;
  }


  /**
   * Function: getData()
   *  Returns data that might have been attached to this exception.
   *
   * Return:
   *  <Array[Mixed]> - Data attached to this exception
   */
  public function getRESTData() {
    // Return internal data-array
    return $this->restData;
  }


  /**
   * Function: format($message, $data)
   *  Formats special placeholders in the given message with data
   *  from the $data-array.
   *  Supported placeholders:
   *   {{restcode}} - Will be replaced with $this->getRESTCode()
   *   {{KEY}} - Will be replaced with $data[key]
   *   {{%KEY}} - Will be replaced with $data[key] (As fallback when key needs to be restcode)
   *
   * Parameters:
   *  $message <String> - Unformated message containing format parameters
   *  $data <Array[Mixed]> - Data-Array from which should be used to repĺace placeholders in $message
   *
   * Return:
   *  <String> - Formated $message with special placeholders replaced with values from $data-array
   */
  public static function format($message, $code, $data) {
    // Format message, by replacing placeholders (restcode)
    $message = str_replace('{{restcode}}', $code, $message);

    // Format message, by replacing placeholders (data-keys)
    if (is_array($data))
      foreach($data as $key => $value) {
        $message = str_replace(sprintf('{{%s}}', $key), $value, $message);
        $message = str_replace(sprintf('{{%%%s}}', $key), $value, $message);
      }

    // Return formated message
    return $message;
  }


  /**
   * Function: responseObject()
   *  Creates a responseObject for this exception. Usefull
   *  for halt(), redirect() or logging.
   *
   * Return:
   *  <Array[Mixed]> - Specially constructed response-object (eg. used by $app->halt())
   */
  public function responseObject() {
    // Return formated response-object
    if (count($this->restData) > 0)
      return array(
        'message' => $this->getRESTMessage(),
        'status'  => $this->getRESTCode(),
        'data'    => $this->getRESTData()
      );
    else
    return array(
      'message' => $this->getRESTMessage(),
      'status'  => $this->getRESTCode()
    );
  }


  /**
   * Function: send($code)
   *  Utility-Function to make sending responses generated from RESTExceptions
   *  easier, since they 95% of the time will look the same.
   *
   * Note:
   *  This will send the preformated exception-information and terminate the application!
   *
   * Parameters:
   *  $code <Integer> - HTTP-Code that should be used
   */
  public function send($code = null) {
    // Fect instance of the RESTController
    $app = \RESTController\RESTController::getInstance();

    // Send formated exception-information
    if ($code)
      $app->halt($code, $this->responseObject());
    elseif ($this->getHTTPCode())
      $app->halt($this->getHTTPCode(), $this->responseObject());
    elseif (static::STATUS)
      $app->halt(static::STATUS, $this->responseObject());
    else
      $app->halt(500, $this->responseObject());
  }


  /**
   * Function: redirect($redirect_uri, $typeOverwrite)
   *  Utility-Function to make redirections generated from RESTExceptions
   *  easier, since they 95% of the time will look the same.
   *
   * Note:
   *  This will send the preformated exception-information and terminate the application!
   *
   * Parameters:
   *  $redirect_uri <String> - Where to redirect to (parameters will be appended via ? and &)
   *  $typeOverwrite <String> - Allow to temporary overwrite the type attached to the exception.
   */
  public function redirect($redirect_uri, $typeOverwrite = null) {
    // A redirect_uri is absolutely required
    if (!isset($redirect_uri))
      $this->send(500);

    // Fect instance of the RESTController
    $app = \RESTController\RESTController::getInstance();

    // URL-Encode error-message (without data!)
    $description  = urlencode($this->getRESTMessage());
    $status       = urlencode($this->getRESTCode());
    $type         = (isset($typeOverwrite)) ? $typeOverwrite : static::$errorType;
    $url          = sprintf('%s?error=%s&error_description=%s&error_status=%s', $redirect_uri, $type, $description, $status);

    // Redirect using generated url
    $app->redirect($url, 303);
  }
}
