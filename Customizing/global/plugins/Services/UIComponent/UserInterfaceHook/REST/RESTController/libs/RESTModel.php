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
  /**
   * Static-Function: getApp()
   *  Inject RESTController into model.
   *
   * Return:
   *  <RESTController> - (Singleton-) Instance of the RESTController
   */
  public static function getApp() {
    return \RESTController\RESTController::getInstance();
  }


  /**
   * Static-Function: getDB()
   *  Inject ilDB into model.
   *
   * Return:
   *  <ilDB> - (Singleton-) Instance of ilDB
   */
  public static function getDB() {
    return $GLOBALS['ilDB'];
  }
}
