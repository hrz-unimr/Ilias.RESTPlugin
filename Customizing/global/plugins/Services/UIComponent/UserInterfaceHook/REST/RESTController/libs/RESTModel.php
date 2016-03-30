<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\libs;

// Requires RESTController
// Requires <$ilDB>


/**
 * Class: RESTModel
 *  Base class for all 'models'. Models should contain only business-logic.
 *
 *  Models should not read input parameters or produce output themselves directly
 *  unless the code is strictly separated from program-logic code.
 *
 */
class RESTModel {
  /**
   * Function: getApp()
   *  Inject RESTController into model.
   *
   * Return:
   *  <RESTController> - (Singleton-) Instance of the RESTController
   */
  public static function getApp() {
    return \RESTController\RESTController::getInstance();
  }


  /**
   * Function: getDB()
   *  Inject ilDB into model.
   *
   * Return:
   *  <ilDB> - (Singleton-) Instance of ilDB
   */
  public static function getDB() {
    return $GLOBALS['ilDB'];
  }
}
