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
 * Class: RESTError
 *  Contains code for non-recoverable error-handling.
 */
class RESTError {
  /**
   * Function: ErrorHandler($app)
   *  Error-handler to transform non-recoverable errors into
   *  a sensible REST output that could be parsed by a consumer
   *  software.
   *
   * Parameters:
   *  $app <RESTController> - Reference to RESTController application for correct content-type handling
   */
  public static function ErrorHandler($app) {
    // Fetch latch error
    $error = error_get_last();

    // Check wether the error should to be displayed
    $allowed = array(
      E_ERROR         => 'E_ERROR',
      E_PARSE         => 'E_PARSE',
      E_CORE_ERROR    => 'E_CORE_ERROR',
      E_COMPILE_ERROR => 'E_COMPILE_ERROR',
      E_USER_ERROR    => 'E_USER_ERROR'
    );

    // Log and display error?
    if (array_key_exists($error['type'], $allowed)) {
      // Log the error
      $app->getLog()->fatal($error);

      // Convert error to response-object
      $errorObj = self::parseError($error);
      $app->response()->write($errorObj, true);
      list($status, $headers, $body) = $app->response()->finalize();

      // Output formated error via echo
      header(sprintf('Content-Type: %s', $headers->get('Content-Type')));
      echo $body;
    }
  }


  /**
   * Function: displayError($msg, $code, $file, $line, $trace)
   *  Send the error-message given by the parameters to the clients
   *  and add a (critical) log-message to the active logfile.
   *
   * Parameters:
   *  error <RESTException/Exception/Error> - Exception or Error-Object to be converted into a more suitable output format
   *
   * Returns:
   *  <array>
   *   $msg <String> - [Optional] Description of error/exception
   *   $code <Integer> - [Optional] Code of error/exception
   *   $file <String> - [Optional] File where the error/exception occured
   *   $line <Integer> - [Optional] Line in file where the error/exception occured
   *   $trace <String> - [Optional] Full (back-)trace (string) of error/exception
   */
  public static function parseError($error) {
    // Parse a RESTException
    if ($error instanceof libs\RESTException)
      $error = array(
        'message'   => $error->getRESTMessage(),
        'status'    => $error->getRESTCode(),
        'data'      => $error->getRESTData(),
        'error'     => array(
          'message' => $error->getMessage(),
          'code'    => $error->getCode(),
          'file'    => str_replace('/', '\\', $error->getFile()),
          'line'    => $error->getLine(),
          'trace'   => str_replace('/', '\\', $error->getTraceAsString())
        )
      );
    // Parse standard Exception
    elseif ($error instanceof \Exception)
      $error = array(
        'message'   => 'An exception was thrown!',
        'status'    => '\Exception',
        'error'     => array(
          'message' => $error->getMessage(),
          'code'    => $error->getCode(),
          'file'    => str_replace('/', '\\', $error->getFile()),
          'line'    => $error->getLine(),
          'trace'   => str_replace('/', '\\', $error->getTraceAsString())
        )
      );
    // Parse error-array object
    elseif (is_array($error))
      $error = array(
        'message'   => 'There is an error in the executed php script.',
        'status'    => 'FATAL',
        'error'     => array(
          'message' => $error['message'],
          'code'    => self::ErrorName($error['type']),
          'file'    => str_replace('/', '\\', $error['file']),
          'line'    => $error['line'],
        )
      );
    // Last resort fallback
    else
      $error = array(
        'message'   => 'Unkown error...',
        'status'    => 'UNKNOWN'
      );

    // Return error-object
    return $error;
  }


  /**
   * Function: ErrorName($type)
   *  Translates PHP numeric error['type'] to a more human-reable string
   *  matching the global error constant names.
   *  Code taken from: http://php.net/manual/de/errorfunc.constants.php#109430
   *
   * Parameters:
   *  type <Int> - Numeric php error type
   *
   * Returns:
   *  <String> -  String representation of error type
   */
  protected static function ErrorName($type) {
    $return = '';

    if ($type & E_ERROR) // 1 //
      $return.='& E_ERROR ';
    if ($type & E_WARNING) // 2 //
      $return.='& E_WARNING ';
    if ($type & E_PARSE) // 4 //
      $return.='& E_PARSE ';
    if ($type & E_NOTICE) // 8 //
      $return.='& E_NOTICE ';
    if ($type & E_CORE_ERROR) // 16 //
      $return.='& E_CORE_ERROR ';
    if ($type & E_CORE_WARNING) // 32 //
      $return.='& E_CORE_WARNING ';
    if ($type & E_COMPILE_ERROR) // 64 //
      $return.='& E_COMPILE_ERROR ';
    if ($type & E_COMPILE_WARNING) // 128 //
      $return.='& E_COMPILE_WARNING ';
    if ($type & E_USER_ERROR) // 256 //
      $return.='& E_USER_ERROR ';
    if ($type & E_USER_WARNING) // 512 //
      $return.='& E_USER_WARNING ';
    if ($type & E_USER_NOTICE) // 1024 //
      $return.='& E_USER_NOTICE ';
    if ($type & E_STRICT) // 2048 //
      $return.='& E_STRICT ';
    if ($type & E_RECOVERABLE_ERROR) // 4096 //
      $return.='& E_RECOVERABLE_ERROR ';
    if ($type & E_DEPRECATED) // 8192 //
      $return.='& E_DEPRECATED ';
    if ($type & E_USER_DEPRECATED) // 16384 //
      $return.='& E_USER_DEPRECATED ';

    return substr($return, 2);
  }
}
