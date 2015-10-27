<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;


/**
 * Baseclass for all models.
 * Implements some common functionality, like
 * injecting the RESTController into the model.
 * Offering easier logging, etc.
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
