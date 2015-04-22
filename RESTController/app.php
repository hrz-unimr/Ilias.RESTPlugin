<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController;

        
// Include SLIM-Framework
require_once('Slim/Slim.php');
        
 
/**
 * This is the RESTController Slim-Application
 * Handles all REST related logic and uses ILIAS
 * Services to fetch requested data.
 *
 *  Usage:
 *   require_once("<PATH-TO-THIS-FILE>". "/app.php");
 *   \RESTController\RESTController::registerAutoloader();
 *   $app = new \RESTController\RESTController("<PATH-TO-THIS-FILE>");
 *   $app->run();
 */
class RESTController extends \Slim\Slim {
    /**
     * Loads given file and returns success status.
     *
     * @param $file - Path to the (class) file that should be loaded
     * @return bool - true if file was found and loaded, false otherwise
     */
    protected static function loadFile($file) {
        if (file_exists($file)) {
            require($file);
            return true;
        }
        
        return false;
    }
    
    
    /**
     * PSR-0 autoloader for RESTController classes
     *
     *  It will first look in the following directories:
     *   "RESTController\libs\*" namespace will search in ".\libs" folder
     *   "RESTController\core\*" namespace will search in ".\core\*\models" folder
     *   "RESTController\extensions\*" namespace will search in ".\extensions\*\models" folder
     *  Otherwise it will fallback to the Slim-Framework auto-loader,
     *  stripping RESTController from $className.
     *
     * @param $className - Fully quantified classname (includes namespace) of a class that needs to be loaded
     */
    public static function autoload($className) {
        // Fetch sub namespaces
        $subNames = explode('\\', $className);
        
        // Only load classes inside own namespace (RESTController)
        if ($subNames[0] === __NAMESPACE__) {            
            // Get base include directory
            $baseDir = __DIR__;
            
            // Get name of class
            $className = ltrim($className, '\\');
            if ($lastNsPos = strripos($className, '\\')) 
                $className = substr($className, $lastNsPos + 1);
            
            // Only look in certain folders
            $success = false;
            switch ($subNames[1]) {
                case 'libs':
                    $success = self::loadFile($baseDir . "\\" . $subNames[1] . "\\" . $className . ".php");
                    break;
                case 'extensions':
                case 'core':
                    $success = self::loadFile($baseDir . "\\" . $subNames[1] . "\\" . $subNames[2] . "\\models\\" . $className . ".php");
                    break;
            };
            
            // Fallback to Slim-Frameworks outoloader, but strip base namespace (RESTController)
            parent::autoload(substr($className, strlen(__NAMESPACE__)));
        }
        // Use Slim-Frameworks autoloder otherwise
        else
            parent::autoload($className);
    }
    
    
    /**
     * Register PSR-0 autoloader
     *  Call this before doing $app = new RESTController();
     */
    public static function registerAutoloader() {
        spl_autoload_register(__NAMESPACE__ . "\\RESTController::autoload");
    }
    
    
    /**
     *
     */
    protected function setErrorHandler() {
        // Handle fatal errors
        register_shutdown_function(function () {
            // Fetch errors
            $err = error_get_last();
            
            // Display error
            if ($err['type'] === E_ERROR) {
                // Work-around to get better backtrace
                $e = new \Exception;
                
                // Process data
                $error = array(
                    'message' => $err['message'],
                    'code' => $err['type'],
                    'file' => $err['file'],
                    'line' => $err['line'],
                    'trace' => $e->getTraceAsString()
                );
                $app = $this;
                
                // Show error
                include('views/error.php');
            }
        });
        
        // Set error-handler
        $this->error(function (\Exception $error) {
            $this->render('views/error.php', array(
                'error' => array(
                    'message' => $error->getMessage(),
                    'code' => $error->getCode(),
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'trace' => $error->getTraceAsString()
                ),
                'app' => $this
            ));
        });
        
        // Set 404 fallback
        $this->notFound(function () {
            $this->render('views/404.php');
        });
        
        // Disable defailt error output
        ini_set('display_errors', 'off');
    }
    
    
    /**
     *
     */
    protected function setLogHandler() {
        // Disable fancy debugging
        $this->config('debug', false);
        
        // Log directory
        $restLog = ILIAS_LOG_DIR . '/restplugin.log';
        
        // Create a new file?
        if (!file_exists($restLog)) {
            $fh = fopen($restLog, 'w');
            fclose($fh);
        }
        
        // Use own file or use ILIAS for logging
        if (is_writable($restLog)) {
            $logWriter = new \Slim\LogWriter(fopen(ILIAS_LOG_DIR . '/restplugin.log', 'a'));
            $this->config('log.writer', $logWriter);
        }
        else {
            global $ilLog;
            $ilLog->write('Plugin REST -> Warning: Log file <' . $restLog . '> is not writeable!');
            $this->config('log.writer', $ilLog);
        }
        
        // Enable logging
        $this->log->setEnabled(true);
        $this->log->setLevel(\Slim\Log::DEBUG);
    }
    
    
    /**
     *
     */
    protected function setCustomContainer() {
        // Use custom Router
        $this->container->singleton('router', function ($c) {
            return new \RESTController\libs\RESTRouter();
        });

        // Use custom Request
        $this->container->singleton('request', function ($c) {
            return new \RESTController\libs\RESTRequest($this);
        });
    }

    
    /**
     * Constructor
     *
     * @param $appDirectory - Diretly in which the RESTController\app.php is contained
     * @param $userSettings - Associative array of application settings
     */
    public function __construct($appDirectory, array $userSettings = array()) {        
        parent::__construct();
        
        // Global information that should be available to all routes/models
        $env = $this->environment();
        $env['client_id'] = CLIENT_ID;
        $env['app_directory'] = $appDirectory;

        // Set template for current view and new views
        $this->config('templates.path', $appDirectory);
        $this->view()->setTemplatesDirectory($appDirectory);

        // REST doesn't use cookies
        $this->hook('slim.after.router', function () {
            header_remove('Set-Cookie');
        });
        
        // Setup custom router, request- & response classes
        $this->setCustomContainer();

        // Setup logging
        $this->setLogHandler();

        // Set default error-handler and 404 result
        $this->setErrorHandler();
    }
    
    
    /**
     * Run
     *
     * This method invokes the middleware stack, including the core Slim application;
     * the result is an array of HTTP status, header, and body. These three items
     * are returned to the HTTP client.
     */
    public function run() {
        // Log some debug usage information
        $this->log->debug("REST call from " . $_SERVER['REMOTE_ADDR'] . " at " . date("d/m/Y,H:i:s", time()));
        
        // Make $this available in all included models/routes
        $app = self::getInstance();
        
        // Load core models & routes
        foreach (glob(realpath(__DIR__)."/core/*/routes/*.php") as $filename) 
            include_once($filename);

        // Load extension models & routes
        foreach (glob(realpath(__DIR__)."/extensions/*/routes/*.php") as $filename) 
            include_once($filename);

        parent::run();
    }
    
    
    /**
     * 
     */
    public function success($data, $format = null) {
        // 'sendData($data, $format) <Stub - Implement Me>';
        // Handle format!
        // $result['status'] = 'success';
        $data['status'] = 'success';
        echo json_encode($data); // Move to response class?
    }
    
    
    /**
     * 
     */
    // Move to response (& request -> should be based on request-header -> content-type) class?
    public function setFormat($format) {
    }
    
    
    /**
     * 
     */
    public function halt($httpCode, $message = null, $restCode = "") {
        // 'halt($code, $message) <Stub - Implement Me>';
        // Build a JSON with msg=$message, status=failed, code=$restCode
        $data = array(
            'status' => 'failure',
            'msg' => $message
        );
        parent::halt($httpCode, json_encode($data));
    }
}
