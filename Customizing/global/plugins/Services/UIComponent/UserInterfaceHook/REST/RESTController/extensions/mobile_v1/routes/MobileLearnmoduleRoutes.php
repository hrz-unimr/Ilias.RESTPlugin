<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\admin as Admin;
use \RESTController\extensions\users_v1 as Users;
use \RESTController\extensions\courses_v1 as Courses;
use \RESTController\extensions\desktop_v1 as Desktop;
use \RESTController\extensions\groups_v1 as Groups;
use \RESTController\extensions\contacts_v1 as Contacts;
use \RESTController\extensions\calendar_v1 as Calendar;

$app->group('/v1/m', function () use ($app) {

    /**
     * Initiates a new ILIAS session using a valid token.
     * Redirects to the HTML Learning Module url.
     *
     * Note: it is important, that the url opens this endpoint in the browser component of the device, since
     * cookies will be set and be required due to the access checker.
     * This can be done e.g. with "https://<hostname>/restplugin.php/v1/m/htlm/145?access_token=cm9v..."
     *
     */
    $app->get('/htlm/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenRouteAuth',  function ($ref_id) use ($app) {
        $auth = new Auth\Util();
        $user_id = $auth->getAccessToken()->getUserId();

        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https://';
        }
        else {
            $protocol = 'http://';
        }
        $domainName = $_SERVER['HTTP_HOST'];
        if ($GLOBALS['COOKIE_PATH']=='/') {
            $lmurl = $protocol.$domainName.'/';
        } else {
            $lmurl = $protocol.$domainName.$GLOBALS['COOKIE_PATH'].'/';
        }


        Libs\RESTLib::initSession($user_id);

        // Mimics showLearningModule() of Modules/HTMLLearningModule/ilObjFileBasedLMGUI
        // TODO: add ilLearningProgress::_tracProgress support
        require_once("./Modules/HTMLLearningModule/classes/class.ilObjFileBasedLMAccess.php");
        $obj_id = Libs\RESTLib::getObjIdFromRef($ref_id);
        \ilObjFileBasedLMAccess::_determineStartUrl($obj_id);
        $startfile =  \ilObjFileBasedLMAccess::$startfile[(string)$obj_id];
        if ($startfile != "")
        {
            $lmurl.= Libs\RESTLib::getWebDir()."/lm_data"."/lm_".$obj_id;
            $lmurl.='/'.$startfile;
            $app->log->debug('redirect to : '.$lmurl);
            header("Location: ".$lmurl, true, 301);
            exit();
        }
        $app->success("Could not locate Learning Module.",404);
    });

});
