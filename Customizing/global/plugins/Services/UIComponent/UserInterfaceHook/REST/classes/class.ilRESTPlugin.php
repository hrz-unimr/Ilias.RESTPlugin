<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
 
 
// Include core UIHook plugin slot class
include_once("./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php");


/**
 * REST Plugin
 *
 * @author Dirk Schäfer <schaefer@hrz.uni-marburg.de>
 * @version $Id$
 *
 */
class ilRESTPlugin extends ilUserInterfaceHookPlugin
{
    /**
     * Returns plugin name (CASE-SENSITIVE) that will be displayed
     * inside ILIAS and also be used to find all plugin classes.
     *
     * @return (String) Plugin name
     */
    function getPluginName() {
        return "REST";
    }
}
