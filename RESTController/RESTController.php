<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController;

// Include SLIM-Framework
require_once('Slim/Slim.php');

// Allow to use short-hands
use \RESTController\database        as Database;
use \RESTController\core\oauth2_v2  as Auth;
use \RESTController\libs\Exceptions as LibExceptions;


/**
 * Class: RESTController
 *  This is the RESTController Slim-Application
 *  Handles all REST related logic and uses ILIAS
 *  Services to fetch requested data.
 *
 * Usage:
 *  require_once("<PATH-TO-THIS-FILE>". "/app.php");
 *  \RESTController\RESTController::registerAutoloader();
 *  $app = new \RESTController\RESTController("<PATH-TO-THIS-FILE>");
 *  $app->run();
 */
class RESTController extends \Slim\Slim {
  // Allow to re-use status messages and codes
  const MSG_NO_ROUTE  = 'There is no route matching URI \'{{URI}}\' using method \'{{method}}\'; see \'/v2/util/routes\' for a list of all available routes.';
  const ID_NO_ROUTE   = 'RESTController\RESTController::ID_NO_ROUTE';


  /**
   * Function: autoload($classname)
   *  PSR-0 autoloader for RESTController classes.
   *  Automatically adds a "models" subname into the namespace of \RESTController\core und
   *  @See \Slim\Slim::autoload(...)
   *  Register this outload via RESTController::registerAutoloader().
   *
   * Parameters:
   *  $className <String> - Fully quantified classname (includes namespace) of a class that needs to be loaded
   */
  public static function autoload($className) {
    // Fetch sub namespaces
    $subNames = explode('\\', $className);

    // Only load classes inside RESTController namespace
    if ($subNames[0] === __NAMESPACE__) {
      // (Core-) Extentions can leave-out the "models" subname in their namespace
      if ($subNames[1] == 'extensions' || $subNames[1] == 'core') {
        // Add 'Models' to class namespace
        array_splice($subNames, 3, 0, array('models'));
        array_shift($subNames);
        parent::autoload(implode($subNames, '\\'));

        // Fallback (without appending 'models')
        if (!class_exists($className, false))
          parent::autoload(substr($className, strlen(__NAMESPACE__)));
      }
      // Everything else gets forwarded directly to Slim
      else
        parent::autoload(substr($className, strlen(__NAMESPACE__)));
    }
    // Use Slim-Frameworks autoloder for non-RESTController classes
    else
      parent::autoload($className);
  }


  /**
   * Function: registerAutoloader()
   *  Register PSR-0 autoloader. Call this before doing $app = new RESTController();
   */
  public static function registerAutoloader() {
    spl_autoload_register(__NAMESPACE__.'\\RESTController::autoload');
  }


  /**
   * Constructor: RESTController($userSettings)
   *  Creates a new instance of the RESTController. There should always
   *  be only one instance and a reference can be fetches via:
   *   RESTController::getInstance()
   *
   * Parameters:
   *  $iliasRoot <String> - Absolute path to ILIAS directory
   *  $userSettings <Array[Mixed]> - Associative array of application settings
   */
  public function __construct($iliasRoot, array $userSettings = array()) {
    // Call parent (SLIM) constructor
    parent::__construct($userSettings);

    // Fetch environment and remeber base-directory (just in case)
    $env = $this->environment();
    $env['ilias_root'] = $root;
    $env['ctl_root']   = __DIR__;
    // Alternatively set as hard-coded path: "$root/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST/RESTController"

    // Add Content-Type middleware (support for JSON/XML requests)
    $contentType = new libs\Middleware\ContentTypes();
    $this->add($contentType);

    // Attach our custom RESTRouter, RESTRequest, RESTResponse
    $this->container->singleton('router',   function ($c) { return new libs\RESTRouter(); });
    $this->container->singleton('response', function ($c) { return new libs\RESTResponse(); });
    $this->container->singleton('request',  function ($c) { return new libs\RESTRequest($this->environment()); });

    // Initialize ILIAS (if not created via restplugin.php)
    $this->initILIAS();

    # Configure the logger
    $this->initLogWriter();

    // Setup error-handler
    $this->setErrorHandlers();

    // Set output-format
    $this->initResponseFormat();

    // Set default template base-directory
    // DoIt: Extract using ILIAS (or keep constant)
    $this->view()->setTemplatesDirectory($appDirectory);

    // Set default 404 template
    $this->notFound(function () {
      // Fetch request URI and method
      $request = $this->request();
      $method  = $request->getMethod();
      $uri     = $request->getResourceUri();

      // Build new exception
      $exception = new libs\RESTException(
        self::MSG_NO_ROUTE,
        self::ID_NO_ROUTE,
        array(
          'method' => $method,
          'URI'    => $uri
        )
      );

      // Send formated exception
      $exception->send(404);
    });
  }


  /**
   * Function: Run()
   *  This method starts the actual RESTController application, including the middleware stack#
   *  and the core Slim application, which includes route-handling, etc.
   */
  public function run() {
    // DoIt: Terminate if disabled

    // Make $app variable available in all route-files
    $app = $this;

    // Load core and extension routes
    foreach (glob(realpath(__DIR__).'/core/*/routes/*.php') as $filename)
      include_once($filename);
    foreach (glob(realpath(__DIR__).'/extensions/*/routes/*.php') as $filename)
      include_once($filename);


    // Log each access triggered
    $this->logPreRun();
    parent::run();
    $this->logPostRun();
  }


  /**
   * Function: success(($data)
   *  This function should be used by any route that wants to return
   *  data after a successfull query. The application will be terminated
   *  afterwards, so make sure any required cleanup happens before
   *  a call to success(...).
   *
   *  @See RESTController->halt(...) for additional notes!
   *
   * Parameters:
   *  $data <String>/<Array[Mixed]> -
   */
  public function success($data) {
    // Delegate to halt(...)
    $this->halt(200, $data, null);
  }


  /**
   * Function: halt(($httpStatus, $data, $restStatus)
   *  This function should be used by any route that wants to return
   *  data or any kind of information after query/request has failed
   *  for some reason . The application will be terminated afterwards,
   *  so make sure any required cleanup happens before a call to halt(...).
   *
   *
   * Note 1:
   *  It is important to note, that this will imidiately send the given $data
   *  (as JSON, unless changed via response->setFormat(...)) and in addition
   *  will cause the application to be terminated by internally throwing
   * 'Slim\Exception\Stop'. This is to prevent any further data from 'leaking'
   *  to the client, which could invalidate the transmitted JSON object.
   *  In case of failure this also negates the requirement to manually invoke
   *  die() or exit() each time...
   *
   * Note 2:
   *  In the rare cases where this behaviour might not be usefull, there is also
   *  the options to directly access the response-object via $app->response() and
   *  (See libs\RESTResponse and Slim\Http\Response for additonal details)
   *  The Data will then be send either after the exiting the route-function or
   *  by manually throwing 'Slim\Exception\Stop'. (Not recommended)
   *  (Transmitting data this way should be used sparingly!)
   *
   * Note 3:
   *  Never use this method or access the $app->request() and $app->response()
   *  object from within a model, since this would make it difficult to reuse.
   *  Only use inside a route or IO-Class and pass data from/to models!
   *
   * Parameters:
   *  $httpStatus <Integer> -
   *  $data <String>/<Array[Mixed]> - [Optional]
   *  $restStatus <String> - [Optional]
   */
  public function halt($httpStatus, $data = null, $restStatus = 'halt') {
    // Do some pre-processing on the $data
    $response = libs\RESTResponse::responseObject($data, $restStatus);

    // Delegate transmission of response to SLIM
    parent::halt($httpStatus, $response);
  }


  /**
   * Function: logRun()
   *  Logs some valuable information for each access triggering the RESTController to run.
   */
  protected function logPreRun() {
    // Fetch all information that should be logged
    $log     = $this->getLog();
    $request = $this->request();
    $ip      = $request->getIp();
    $method  = $request->getMethod();
    $route   = $request->getResourceUri();
    $when    = date('d/m/Y, H:i:s', time());

    // Log additional information in debug-mode (with parameters)
    if ($log->getLevel() == \Slim\Log::DEBUG) {
      $parameters = $request->getParameter();
      $log->debug(sprintf(
        "[%s]: REST was called from '%s' on route '%s' [%s] with Parameters:\n%s",
        $when,
        $ip,
        $route,
        $method,
        print_r($parameters, true)
      ));
    }
    // Log access without request parameters
    else
      $log->info(sprintf(
        "[%s]: REST was called from '%s' on route '%s' [%s]...",
        $when,
        $ip,
        $route,
        $method,
        print_r($parameters, true)
      ));
  }


  /**
   * Function: logRun()
   *  Logs some valuable information for each access triggering the RESTController to run.
   */
  protected function logPostRun() {
    // Fetch logger
    $log     = $this->getLog();

    // Log additional information in debug-mode (with parameters)
    if ($log->getLevel() == \Slim\Log::DEBUG) {
      // Fetch all information that should be logged
      $request  = $this->request();
      $response = $this->response();
      $ip       = $request->getIp();
      $method   = $request->getMethod();
      $route    = $request->getResourceUri();
      $when     = date('d/m/Y, H:i:s', time());
      $status   = $response->getStatus();
      $headers  = $response->headers->all();
      $body     = $response->decode($response->getBody());

      // Output log
      $log->debug(sprintf(
        "[%s]: REST call from '%s' on route '%s' [%s] finished with:\nStatus: '%s'\nHeaders:\n%s\nBody:\n%s",
        $when,
        $ip,
        $route,
        $method,
        $status,
        print_r($headers, true),
        print_r($body, true)
      ));
    }
  }


  /**
   * Configure custom LogWriter
   * @param $logFile - string consisting of full path + filename
   */
  protected function initLogWriter() {
    // Fetch config location from database
    try {
      $settings = Database\RESTconfig::fetchSettings(array('log_file', 'log_level'));
      $logFile  = $settings['log_file'];
      $logLevel = $settings['log_level'];
    }
    catch (LibExceptions\Database $e) { }

    // Use fallback values
    if (!isset($logLevel) || !is_string($logFile))
      $logFile = sprintf('%s/restplugin-%s.log', ILIAS_LOG_DIR, CLIENT_ID);
    if (!isset($logLevel))
      $logLevel = 'WARN';

    // Create file if it does not exist
    if (!file_exists($logFile)) {
      $fh = fopen($logFile, 'w');
      fclose($fh);
    }

    // Check wether file exists and is writeable
    if (!is_writable($logFile))
      $app->halt(500, sprintf('Can\'t write to log-file: %s (Make sure file exists and is writeable by the PHP process)', $logFile));

    // Open the logfile for writing to using Slim
    $logWriter = new \Slim\LogWriter(fopen($logFile, 'a'));
    $log       = $this->getLog();
    $log->setWriter($logWriter);

    // Set logging level
    switch (strtoupper($logLevel)) {
      case 'EMERGENCY':  $log->setLevel(\Slim\Log::EMERGENCY);  break;
      case 'ALERT':      $log->setLevel(\Slim\Log::ALERT);      break;
      case 'CRITICAL':   $log->setLevel(\Slim\Log::CRITICAL);   break;
      case 'FATAL':      $log->setLevel(\Slim\Log::FATAL);      break;
      case 'ERROR':      $log->setLevel(\Slim\Log::ERROR);      break;
      default:
      case 'WARN':       $log->setLevel(\Slim\Log::WARN);       break;
      case 'NOTICE':     $log->setLevel(\Slim\Log::NOTICE);     break;
      case 'INFO':       $log->setLevel(\Slim\Log::INFO);       break;
      case 'DEBUG':     $log->setLevel(\Slim\Log::DEBUG);       break;
    }
  }


  /**
   * Function: initResponseFormat()
   *  Tries to autodetect the preffered output-format.
   *  If the request-route ends in .json or .xml
   *  this format is used, else the request content-type
   *  is used. If none is available, JSON is used as
   *  default fallback.
   */
  protected function initResponseFormat() {
    // Set output-format
    $requestURI    = $this->request()->getResourceUri();
    $routeFormat   = $this->router()->getResponseFormat($requestURI);
    $requestFormat = $this->request()->getFormat();

    // Prefer format set via route 'file-ending'
    if ($routeFormat)
      $this->response()->setFormat($routeFormat);
    // Use request format as fallback
    elseif ($requestFormat)
      $this->response()->setFormat($requestFormat);
    // Lastly fallback to json
    else
      $this->response()->setFormat('json');
  }


  /**
   * Function: initILIAS()
   *  Makes sure ILIAS was initialized, eg.
   *  when this has not already been done
   *  by the restplugin.php
   */
  protected function initILIAS() {
    // Initialize ILIAS (if not created via restplugin.php)
    if (!defined('CLIENT_ID')) {
      // Fetch request object
      $request = $this->request();

      // Try to fetch ilias-client from access-token
      try {
        $token  = $request->getToken('access', true);
        $array  = Auth\Tokens\Base::deserializeToken($token);
        $client = $array['ilias_client'];
      }
      // Try to fetch ilias-client from refresh-token instead
      catch (LibExceptions\Parameter $e) {
        try {
          $token  = $request->getToken('refresh', true);
          $array  = Auth\Tokens\Base::deserializeToken($token);
          $client = $array['ilias_client'];
        }
        // Catch if there is no refresh-token
        catch (LibExceptions\Parameter $e) { }
      }

      // Initialize ilias with given client (null means: the client given as GET or COOKIE like normal)
      ob_start();
      libs\RESTilias::initILIAS($client);
      ob_end_clean();
    }
  }


  /**
   * Function: setErrorHandlers()
   *  Registers both a custom error-handler for errors/exceptions caughts by
   *  SLIM as well as registering a shutdown function for other FATAL errors.
   *  Additionally also disable PHP's display_errors flag!
   */
  protected function setErrorHandlers() {
    // Disable fancy debug-messages
    $this->config('debug', false);

    // Set default error-handler for exceptions caught by SLIM
    $this->error(function (\Exception $error) {
      // Log the error
      $this->getLog()->error($error);

      // Stop executing on error
      $this->halt(500, libs\RESTError::parseError($error));
    });

    // Set default error-handler for any error/exception not caught by SLIM
    ini_set('display_errors', false);
    register_shutdown_function(function() { libs\RESTError::ErrorHandler($this); });
  }
}
