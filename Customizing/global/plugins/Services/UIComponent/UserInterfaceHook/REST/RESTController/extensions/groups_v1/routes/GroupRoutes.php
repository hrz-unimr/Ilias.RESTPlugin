<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D. Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\groups_v1 as Groups;


$app->group('/v1', function () use ($app) {

    /**
     *  Returns all groups for the authorized user.
     *
     *  Version 15.7.08
     */
    $app->get('/groups4user', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {

        $result = array();
        $user_id = Auth\Util::getAccessToken()->getUserId();

        try {
            Libs\RESTLib::initAccessHandling();
            $grpModel = new Groups\GroupsModel();
            $my_groups = $grpModel->getGroupsOfUser($user_id);
            $result['groups'] = $my_groups;
            $app->success($result);
        } catch (Libs\ReadFailed $e) {
            $app->halt(400, $e->getFormatedMessage());
        }
    });

    $app->get('/groups/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $result = array();
        $user_id = Auth\Util::getAccessToken()->getUserId();

        try {
            Libs\RESTLib::initAccessHandling();
            $grpModel = new Groups\GroupsModel();
            $info = $grpModel->getGroupInfo($ref_id);
            $result['info'] = $info;
            $members = $grpModel->getGroupMembers($ref_id);
            $result['members'] = $members;
            $app->success($result);
        } catch (Libs\ReadFailed $e) {
            $app->halt(400, $e->getFormatedMessage());
        }
    });

});
