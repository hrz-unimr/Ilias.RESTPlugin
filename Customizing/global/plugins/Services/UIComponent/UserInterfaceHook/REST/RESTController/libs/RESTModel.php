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
 *  Base class for all 'models'. Models should contain only buisness-logic.
 *  If possible the should not read input parameters themselves or
 *  produce output directly unless this code is strictly separated
 *  from program-logic code.
 *  In other words a Model-Function should either:
 *   - Read (and pre-process) input data
 *   - Write data to the output
 *   - Do buiness-logic calculation
 *  But never two or more of the above at the same time, to keep all
 *  componentfunctions reuseable!
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
