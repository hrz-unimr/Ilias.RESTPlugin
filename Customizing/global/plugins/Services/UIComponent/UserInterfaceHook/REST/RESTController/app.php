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
     * List of default REST error-codes
     *  Extensions are allowed to create their own error-codes.
     *  Using a unique string seems to be an easier solution than assigning unique numbers.
     */
     const ID_NO_ROUTE = 'RESTController\RESTController::ID_NO_ROUTE';


    /**
     * PSR-0 autoloader for RESTController classes
     *  Automatically adds a "models" subname into the namespace of \RESTController\core und
     *  @See \Slim\Slim::autoload(...)
     *
     * @param $className - Fully quantified classname (includes namespace) of a class that needs to be loaded
     */
    public static function autoload($className) {
        // Fetch sub namespaces
        $subNames = explode('\\', $className);

        // Only load classes inside own namespace (RESTController)
        if ($subNames[0] === __NAMESPACE__) {
            // (Core-) Extentions can leave-out the "models" subname in their namespace
            if ($subNames[1] == 'extensions' || $subNames[1] == 'core') {
                array_splice($subNames, 3, 0, array('models'));
                array_shift($subNames);
                parent::autoload(implode($subNames, '\\'));

                // Fallback
                if (!class_exists($className, false))
                    parent::autoload(substr($className, strlen(__NAMESPACE__)));
            }
            // Everything else gets forwarded directly to Slim
            else
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
        spl_autoload_register(__NAMESPACE__.'\\RESTController::autoload');
    }


    /**
     *
     */
    protected function setCustomContainers() {
        // Use custom Router
        $this->container->singleton('router', function ($c) {
            return new libs\RESTRouter();
        });

        // Use custom Request
        $this->container->singleton('request', function ($c) {
            return new libs\RESTRequest($this->environment());
        });

        // Use custom Request
        $this->container->singleton('response', function ($c) {
            return new libs\RESTResponse();
        });

        // Use custom log-writer
        $this->container->singleton('logWriter', function ($c) {
            // Log directory
            $restLog = ILIAS_LOG_DIR.'/restplugin.log';

            // Create a new file?
            if (!file_exists($restLog)) {
                $fh = fopen($restLog, 'w');
                fclose($fh);
            }

            // Use own file or use ILIAS for logging
            if (!is_writable($restLog)) {
                global $ilLog;
                $ilLog->write('Plugin REST -> Warning: Log file ' . $restLog . ' is not write-able!');

                return $ilLog;
            }

            return new \Slim\LogWriter(fopen($restLog, 'a'));
        });
    }


    /**
     *
     */
    protected function displayError($msg = '', $code = '', $file = '', $line = '', $trace = '') {
        // Format data
        $file = str_replace('/', '\\', $file);
        $trace = str_replace('/', '\\', $trace);

        // Generate standard message
        $error = array(
            'msg' => 'An error occured while handling this route!',
            'data' => array(
                'message' => $msg,
                'code' => $code,
                'file' => $file,
                'line' => $line,
                'trace' => $trace
            )
        );

        // Log error to file
        $this->log->critical($error);

        // Display error
        header('content-type: application/json');
        echo json_encode($error);
    }


    /**
     * Constructor
     *
     * @param $appDirectory - Directory in which the app.php is contained
     * @param $userSettings - Associative array of application settings
     */
    public function __construct($appDirectory, array $userSettings = array()) {
        parent::__construct($userSettings);

        // Setup custom router, request- & response classes
        $this->setCustomContainers();

        // Add Content-Type middleware (mostly for JSON)
        $contentType = new \Slim\Middleware\ContentTypes();
        $this->add($contentType);

        // Set template for current view and new views
        $this->view()->setTemplatesDirectory($appDirectory);

        // Set 404 fallback
        $this->notFound(function () {
            $this->halt(404, 'There is no route matching this URI!', self::ID_NO_ROUTE);
        });

        // Set error-handler
        $this->error(function (\Exception $error) {
            $this->displayError($error->getMessage(), $error->getCode(), $error->getFile(), $error->getLine(), $error->getTraceAsString());
        });

        // Set error.handler for fatal errors
        ini_set('display_errors', 'off');
        register_shutdown_function(function () {
            // Fetch errors
            $err = error_get_last();
            $allowed = array(
                E_ERROR => 'E_ERROR',
                E_PARSE => 'E_PARSE',
                E_CORE_ERROR => 'E_CORE_ERROR',
                E_COMPILE_ERROR => 'E_COMPILE_ERROR',
                E_USER_ERROR => 'E_USER_ERROR'
            );
            $errName = $allowed[$err['type']];

            // Display error
            if ($errName)
                $this->displayError($err['message'], $err['type'], $err['file'], $err['line'], sprintf('\'%s\' errors can\'t be traced.', $errName));

        });

        // Disable fancy debug-messages but enable logging
        $this->config('debug', false);
        $this->log->setEnabled(true);
        $this->log->setLevel(\Slim\Log::DEBUG);

        // Global information that should be available to all routes/models
        $env = $this->environment();
        $env['client_id'] = CLIENT_ID;
        $env['app_directory'] = $appDirectory;
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
        $this->log->info('REST call from ' . $_SERVER['REMOTE_ADDR'] . ' at ' . date('d/m/Y,H:i:s', time()));

        // Make $this available in all included models/routes
        $app = self::getInstance();

        // Load core models & routes
        foreach (glob(realpath(__DIR__).'/core/*/routes/*.php') as $filename)
            include_once($filename);

        // Load extension models & routes
        foreach (glob(realpath(__DIR__).'/extensions/*/routes/*.php') as $filename)
            include_once($filename);

        parent::run();
    }


    /**
     *
     */
    public function success($data, $format = null) {
        if (isset($data) && $data != '')
            if (!is_array($data))
                $data = array(
                    'status' => 'success',
                    'msg' => $data
                );
            else
                $data['status'] = 'success';

        $this->response->setBody($data);
        $this->stop();
    }


    /**
     *
     */
    public function halt($httpCode, $data = null, $restCode = 'halt', $format = null) {
        if (isset($data) && $data != '')
            if (!is_array($data))
                $data = array(
                    'status' => $restCode,
                    'msg' => $data
                );
            else
                $data['status'] = $restCode;


        parent::halt($httpCode);
    }
}
