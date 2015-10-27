<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
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
     * Note: it is important, that this endpoint (as url) is directly used in the browser component of the device, since
     * cookies will be set and be required due to the access checker. The bearer token has to be submitted via a GET payload.
     * This can be done e.g. with "https://<hostname>/restplugin.php/v1/m/htlm/145?access_token=cm9v..."
     *
     */
    $app->get('/htlm/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION),  function ($ref_id) use ($app) {
        $user_id = Auth\Util::getAccessToken()->getUserId();

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
            $lmurl.= MobileWebBridgeModel::getWebDir()."/lm_data"."/lm_".$obj_id;
            $lmurl.='/'.$startfile;
            $app->log->debug('redirect to : '.$lmurl);
            header("Location: ".$lmurl, true, 301);
            exit();
        }
        $app->success("Could not locate Learning Module.",404);
    });

    /**
     * This route initiates a new ILIAS session and redirects to the permalink URL of the object specified by its ref_id.
     * The route can be used to get a web view of the repository module (not necessarily its contents).
     * To accomplish the latter you have to invoke, e.g. v1/m/htlm/:ref_id.
     *
     * Note: it is important, that this endpoint (as url) is directly used in the browser component of the device, since
     * cookies will be set and be required due to the access checker. The bearer token has to be submitted via a GET payload.
     * This can be done e.g. with "https://<hostname>/restplugin.php/v1/m/permabridge/145?access_token=cm9v..."
     */
    $app->get('/permabridge/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $user_id = Auth\Util::getAccessToken()->getUserId();

        $permaLink = MobileWebBridgeModel::getPermanentLink($ref_id);;
        MobileWebBridgeModel::initSession($user_id);


        if ($permaLink!="") {
            $app->log->debug('redirect to : '.$permaLink);
            header("Location: ".$permaLink, true, 301);
            exit();
        }
        $app->success("Target not found.",404);
    });

});
