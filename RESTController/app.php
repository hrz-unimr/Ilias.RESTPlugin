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
     * PSR-0 autoloader
     */
    public static function autoload($className) {
        if (substr($className, 0, strlen(__NAMESPACE__)) === __NAMESPACE__) {            
            $thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);
            $baseDir = __DIR__;

            if (substr($baseDir, -strlen($thisClass)) === $thisClass) 
                $baseDir = substr($baseDir, 0, -strlen($thisClass));

            $className = ltrim($className, '\\');
            $fileName  = $baseDir;
            $namespace = '';
            if ($lastNsPos = strripos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            if (file_exists($fileName)) 
                require($fileName);
        }
        else
            parent::autoload($className);
    }
    
    
    /**
     * Register PSR-0 autoloader
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


        // --------------------------[!! Please do not remove !!]---------------------------
        require_once('libs/RESTResponse.php');
        require_once('libs/RESTRequest.php');
        require_once('libs/RESTSoapAdapter.php');
        require_once('libs/AuthLib.php');
        require_once('libs/TokenLib.php');
        require_once('libs/AuthMiddleware.php');
        // --------------------------[!! Please do not remove !!]---------------------------
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
