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
                array_splice($subNames, 3, 0, array("models"));
                array_shift($subNames);
                parent::autoload(implode($subNames, "\\"));

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

                // Dsplay error
                $this->displayError($err['message'], $err['type'], $err['file'], $err['line'], $e->getTraceAsString());
            }
        });

        // Set error-handler
        $this->error(function (\Exception $error) {
            // fetch error-data
            $msg = $error->getMessage();
            $code = $error->getCode();
            $file = str_replace('/', '\\', $error->getFile());
            $line = $error->getLine();
            $trace = str_replace('/', '\\', $error->getTraceAsString());

            // Display error
            $this->displayError($msg, $code, $file, $line, $trace);
        });

        // Set 404 fallback
        $this->notFound(function () {
            $this->halt(404, 'There is no route matching this URI!', ID_NO_ROUTE);
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
            return new \RESTController\libs\RESTRequest($this->environment());
        });

        // Use custom Request
        $this->container->singleton('response', function ($c) {
            return new \RESTController\libs\RESTResponse();
        });
    }


    /**
     * Constructor
     *
     * @param $appDirectory - Directory in which the app.php is contained
     * @param $userSettings - Associative array of application settings
     */
    public function __construct($appDirectory, array $userSettings = array()) {
        parent::__construct($userSettings);

        // Global information that should be available to all routes/models
        $env = $this->environment();
        $env['client_id'] = CLIENT_ID;
        $env['app_directory'] = $appDirectory;

        // Set template for current view and new views
        $this->config('templates.path', $appDirectory);
        $this->view()->setTemplatesDirectory($appDirectory);

        // Add Content-Type middleware (mostly for JSON)
        $contentType = new \Slim\Middleware\ContentTypes();
        $this->add($contentType);

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
        $this->response->headers->remove('Content-Type');
        header('Content-Type: application/json');
        echo json_encode($data); // Move to response class?

        $this->stop();
    }


    /**
     *
     */
    public function halt($httpCode, $message = null, $restCode = "", $format = null) {
        // 'halt($code, $message) <Stub - Implement Me>';
        // Build a JSON with msg=$message, status=failed, code=$restCode
        if (isset($message) && $message != '') {
            $data = array(
                'status' => 'failure',
                'msg' => $message
            );
            $data = json_encode($data);
            parent::halt($httpCode, $data);
        }
        else
            parent::halt($httpCode, $message);
    }


    /**
     *
     */
    protected function displayError($msg, $code, $file, $line, $trace) {
        // Generate standard message
$errStr = '{
    "msg": "An error occured while handling this route!",
    "data": {
        "message": ' . json_encode(($msg)   ?: '') . ',
        "code": ' .    json_encode(($code)  ?: '') . ',
        "file": ' .    json_encode(($file)  ?: '') . ',
        "line": ' .    json_encode(($line)  ?: '') . ',
        "trace": ' .   json_encode(($trace) ?: '') . '
    }
}';

        // Log error to file
        $this->log->debug($errStr);

        // Display error
        header('content-type: application/json');
        echo $errStr;
    }
}
