<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Class: RESTModel
 *  Base class for all ('non-io') 'models'. Models should contain only program
 *  logic and are not allowed to parse input parameters and send
 *  responses via SLIM in order to be as reusable as possible, while 'io models'
 *  should doing the input parsing and reesponse sending.
 */
class RESTModel {
    /*
     * Injected RESTController. Use with caution!
     *  Do not use $app to do any "global-like"
     *  stuff (eg. halt(), success(), environment())
     *  with $app inside a model as much as possible.
     */
    protected static $app;


    /*
     * Inject ilDB. Should remove "global-like"
     * nature of $ilDB.
     */
    protected static $sqlDB;

    /**
     * Inject ilPluginAdmin. Should remove "global-like"
     * nature of $ilPluginAdmin.
     */
    protected static $plugin;


    /**
     * Create a new instance & inject RESTController
     */
    public function __construct() {
        // Inject RESTController
        if (!self::$app)
            $app = self::getApp();

        // Inject $ilDB
        if (!self::$sqlDB)
            self::$sqlDB = self::getDB();
    }


    /**
     *
     */
    public static function getApp() {
      return \RESTController\RESTController::getInstance();
    }


    /**
     *
     */
    public static function getDB() {
      return $GLOBALS['ilDB'];
    }
}
