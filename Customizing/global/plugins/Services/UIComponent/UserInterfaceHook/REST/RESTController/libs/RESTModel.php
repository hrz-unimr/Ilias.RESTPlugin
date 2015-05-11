<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
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
    protected $app;


    /*
     * Inject ilDB. Should remove "global-like"
     * nature of $ilDB.
     */
    protected $sqlDB;


    /**
     * Create a new instance & inject RESTController
     */
    public function __construct($app, $sqlDB = null) {
        // Inject RESTController
        $this->app = $app;

        // Inject $ilDB
        $this->sqlDB = $sqlDB;
    }
}
