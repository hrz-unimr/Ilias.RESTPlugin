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
 *
 */
class RESTController extends \Slim\Slim {
    /**
     *
     */
    protected static function loadFile($file) {
        if (file_exists($file)) {
            include($file);
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
     *  Otherwise it will fallback to zhe Slim-Framework auto-loader,
     *  stripping RESTController from $className.
     */
    public static function autoload($className) {
        // Fetch sub namespaces
        $subNames = explode('\\', $className);
        
        // Only load classes inside own namespace (RESTController)
        if ($subNames[0] === __NAMESPACE__) {
            // Get base include directory
            $baseDir = __DIR__;
            if (substr($baseDir, -strlen($thisClass)) === $thisClass) 
                $baseDir = substr($baseDir, 0, -strlen($thisClass));
            
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
            
            // TODO: use parent autoloader, but with RESTController stripped
        }
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
     * Constructor
     * @param  array $userSettings Associative array of application settings
     */
    public function __construct($appDirectory, array $userSettings = array()) {
        parent::__construct();
        
        // Use Custom Router
        $this->container->singleton('router', function ($c) {
            return new \RESTController\libs\RESTRouter();
        });

        // Enable debugging (to own file or ilias if not possible)
        $this->config('debug', true);
        if (is_writable(ILIAS_LOG_DIR . '/restplugin.log')) {
            $logWriter = new \Slim\LogWriter(fopen(ILIAS_LOG_DIR . '/restplugin.log', 'a'));
            $this->config('log.writer', $logWriter);
        }
        else {
            global $ilLog;
            $ilLog->write('Plugin REST -> Warning: Log file <' . ILIAS_LOG_DIR . '/restplugin.log> is not writeable!');
            $this->config('log.writer', $ilLog);
        }
        $this->log->setEnabled(true);
        $this->log->setLevel(\Slim\Log::DEBUG);


        // Set template for current view and new views
        $this->config('templates.path', $appDirectory);
        $this->view()->setTemplatesDirectory($appDirectory);


        // REST doesn't use cookies
        $this->hook('slim.after.router', function () {
            header_remove('Set-Cookie');
        });

        $this->error(function (\Exception $e) use ($app) {
            $this->render('views/error.php');
        });
        $this->notFound(function () use ($app) {
            $this->render('views/404.php');
        });


        // Global information that should be available to all routes/models
        $env = $this->environment();
        $env['client_id'] = CLIENT_ID;
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
       foreach (glob(realpath(__DIR__)."/core/*/models/*.php") as $filename) 
            include_once($filename);
        foreach (glob(realpath(__DIR__)."/core/*/routes/*.php") as $filename) 
            include_once($filename);

        // Load extension models & routes
        foreach (glob(realpath(__DIR__)."/extensions/*/models/*.php") as $filename) 
            include_once($filename);
        foreach (glob(realpath(__DIR__)."/extensions/*/routes/*.php") as $filename) 
            include_once($filename);

        parent::run();
    }
}
