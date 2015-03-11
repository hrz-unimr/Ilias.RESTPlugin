<?php

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
    function getPluginName() {
        return "REST";
    }

}

?>