<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTPlugin {


/**
 * Class: Bootstrap
 *  This class is used to bootstrap
 *  the core of ILIAS and the RESTPlugin.
 */
class Bootstrap {
  /**
   * Function: showDisabled()
   *  Displays an error-message. Script-execution should
   *  be finished after this output, to prevent corrupted JSON.
   */
  protected static function showDisabled() {
    // Display an appropriate error-message
    header('HTTP/1.0 404 Disabled');
    header('Warning: RESTPlugin disabled');
    header('Content-Type: application/json');
    echo '{
        "status": "RESTPlugin\\Bootstrap::ID_DISABLED",
        "msg": "The RESTPlugin is currently disabled."
    }';
  }


  /**
   * Function: applyOAuth2Fix()
   *  The term "client_id" is used twice within this context:
   *   (1) ilias client_id                 [Will be ilias_client_id and client_id]
   *   (2) oauth2 client_id (RFC 6749)     [Will be api_key]
   *  In order to solve the conflict for the variable "client_id"
   *  some counter measures are necessary.
   *
   * Solution:
   *  It is required to provide the variable ilias_client_id
   *  if a specific ilias client needs to be adressed.
   */
  protected static function applyOAuth2Fix() {
    // *_client_id was set via GET
    if (isset($_GET['client_id']) || isset($_GET['ilias_client_id'])) {
      // oAuth2: Set api_key to client_id
      $_GET['api_key'] = $_GET['client_id'];

      // ILIAS: Set client_id to ilias_client_id
      if (isset($_GET['ilias_client_id']))
          $_GET['client_id'] = $_GET['ilias_client_id'];
    }
    // *_client_id was set via GET
    else if (isset($_POST['client_id']) || isset($_POST['ilias_client_id'])) {
      // oAuth2: Set api_key to client_id
      $_POST['api_key'] = $_POST['client_id'];

      // ILIAS: Set client_id to ilias_client_id
      // Note: ILIAS only cares about GET
      if (isset($_POST['ilias_client_id']))
        $_GET['client_id'] = $_POST['ilias_client_id'];
    }
  }


  /**
   * Function: getIniHost()
   *  Return the [server] -> "http_path" variable from 'ilias.init.php'.
   */
  protected static function getIniHost() {
    // Include file to read config
    require_once("./Services/Init/classes/class.ilIniFile.php");

    // Read config
		$ini = new \ilIniFile("./ilias.ini.php");
		$ini->read();

    // Return [server] -> "http_path" variable from 'ilias.init.php'
    $http_path = $ini->readVariable("server", "http_path");

    // Strip http:// & https://
    if (strpos($http_path, 'https://') !== false)
      $http_path = substr($http_path, 8);
    if (strpos($http_path, 'http://') !== false)
      $http_path = substr($http_path, 7);

    // Return clean host
    return $http_path;
  }


  /**
   * Function: initILIAS()
   *  This class will initialize ILIAS just like when calling ilias/index.php.
   *  It does some extra-work to make sure ILIAS does not get any wrong idea
   *  when having "unpredicted" values in $_SERVER array.
   *
   * Parameters:
   *  $fixedHost <String> - [Optional] Use $fixedHost value for $_SERVER['HTTP_HOST'] during ILIAS
   *                        initialisation. ILIAS will (for some idotic reason) use this value to
   *                        construct permanent links, cookies and more.
   */
  protected static function initILIAS($fixedHost = null) {
    // Apply oAuth2 fix for client_id GET/POST value
    self::applyOAuth2Fix();

    // Required included to initialize ILIAS
    require_once("Services/Context/classes/class.ilContext.php");
    require_once("Services/Init/classes/class.ilInitialisation.php");

    // Set ILIAS Context. This should tell ILIAS what to load and what not
    \ilContext::init(\ilContext::CONTEXT_REST);

    // Remember original values
    $_ORG_SERVER = array(
      'HTTP_HOST'    => $_SERVER['HTTP_HOST'],
      'REQUEST_URI'  => $_SERVER['REQUEST_URI'],
      'PHP_SELF'     => $_SERVER['PHP_SELF'],
    );

    // Overwrite $_SERVER entries which would confuse ILIAS during initialisation
    $_SERVER['REQUEST_URI'] = '';
    $_SERVER['PHP_SELF']    = '/index.php';
    if ($fixedHost)
      $_SERVER['HTTP_HOST'] = $fixedHost;
    else
      $_SERVER['HTTP_HOST'] = self::getIniHost();

    // Initialise ILIAS
    \ilInitialisation::initILIAS();
    header_remove('Set-Cookie');

    // Restore original, since this could lead to bad side-effects otherwise
    $_SERVER['HTTP_HOST']   = $_ORG_SERVER['HTTP_HOST'];
    $_SERVER['REQUEST_URI'] = $_ORG_SERVER['REQUEST_URI'];
    $_SERVER['PHP_SELF']    = $_ORG_SERVER['PHP_SELF'];
  }


  /**
   * Function: initREST()
   *  This method will load and start the RESTController
   *  if the attached Plugin was enabled in ILIAS.
   */
  protected static function initREST() {
    global $ilPluginAdmin;

    // Fetch plugin object
    $ilRESTPlugin = $ilPluginAdmin->getPluginObject(IL_COMP_SERVICE, "UIComponent", "uihk", "REST");
    $appDirectory = $ilRESTPlugin->getDirectory() . "/RESTController/";

    // Include the RESTController application
    require_once($appDirectory . '/app.php');

    // Register the RESTController Class-AutoLoader
    \RESTController\RESTController::registerAutoloader();

    // Instantate and run the RESTController application
    $restController = new \RESTController\RESTController($appDirectory);
    $restController->run();
  }


  /**
   * Function: init($fixedHost)
   *  This method will initialize ILIAS and start
   *  the RESTController afterwards.
   *
   * Parameters:
   *  @See self::initILIAS($fixedHost)
   */
  public static function init($fixedHost = null) {
    // Initialize ILIAS (disabled any output)
    ob_start();
    self::initILIAS($fixedHost);
    ob_end_clean();

    // Start RESTPlugin if its enabled
    global $ilPluginAdmin;
    if ($ilPluginAdmin->isActive(IL_COMP_SERVICE, "UIComponent", "uihk", "REST")) {
      self::initREST();
    }
    // Show an error otherwise
    else
      self::showDisabled();
  }
}


// End of namespace
}


// Initialize and start the RESTPlugin (together with ILIAS)
namespace {
  /**
   * Note:
   *  To prevent parsing ilias.ini.php twice you can manually set
   *  the $_SERVER['HTTP_HOST'] variable that will be used during
   *  ILIAS-Initialization by using eg.:
   *   \RESTPlugin\Bootstrap::init('http://www.ilias.de');
   *  Otherwise the http_path value from ilias.php.ini will
   *  be taken.
   */
  \RESTPlugin\Bootstrap::init();
}
