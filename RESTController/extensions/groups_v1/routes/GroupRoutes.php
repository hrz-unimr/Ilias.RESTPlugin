<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D. Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\groups_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;
use \RESTController\core\auth as Auth;
use \RESTController\extensions\groups_v1 as Groups;


$app->group('/v1', function () use ($app) {

    /**
     *  Returns all groups of the authorized user.
     */
    $app->get('/groups', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {

        $result = array();
        $accessToken = $app->request->getToken();
        $user_id = $accessToken->getUserId();

        try {
            Libs\RESTilias::initAccessHandling();
            $grpModel = new Groups\GroupsModel();
            $my_groups = $grpModel->getGroupsOfUser($user_id);
            $result['groups'] = $my_groups;
            $app->success($result);
        } catch (Libs\RESTException $e) {
            $app->halt(401, "Error: ".$e->getRESTMessage(), -5);
        }
    });

    /**
     * Get content description of a group identified by ref_id.
     */
    $app->get('/groups/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $result = array();
        $accessToken = $app->request->getToken();

        try {
            Libs\RESTilias::initAccessHandling();
            $grpModel = new Groups\GroupsModel();
            $info = $grpModel->getGroupInfo($ref_id);
            $result['info'] = $info;
            $content = $grpModel->getGroupContent($ref_id);
            $result['content'] = $content;
            $members = $grpModel->getGroupMembers($ref_id);
            $result['members'] = $members;
            $app->success($result);
        } catch (Libs\RESTException $e) {
            $app->halt(401, "Error: ".$e->getRESTMessage(), -5);
        }
    });

    /**
     * Adds the authenticated user as a member to the group specified by its parameter ref_id.
     */
    $app->get('/groups/join/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        global $ilUser;
        Libs\RESTilias::loadIlUser();
        $ilUser->setId((int)$authorizedUserId);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        try {
            //$ref_id = $request->getParameter("ref_id");
            $grpreg_model = new Groups\GroupsRegistrationModel();
            $grpreg_model->joinGroup($authorizedUserId, $ref_id);

            $result = array(
                'msg' => "User ".$authorizedUserId." subscribed to group with ref_id = " . $ref_id . " successfully.",
            );
            $app->success($result);
        } catch (Groups\SubscriptionFailed $e) {
            $app->halt(400, "Error: Subscribing user ".$authorizedUserId." to group with ref_id = ".$ref_id." failed. Exception:".$e->getMessage(), -15);
        }
    });

    /**
     * Removes the authenticated user from a group specified by the parameter "ref_id".
     */
    $app->get('/groups/leave/:ref_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($ref_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        global $ilUser;
        Libs\RESTilias::loadIlUser();
        $ilUser->setId((int)$authorizedUserId);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        try {
            $crsreg_model = new Groups\GroupsRegistrationModel();
            $crsreg_model->leaveGroup($authorizedUserId, $ref_id);
            $app->success(array("msg"=>"User ".$authorizedUserId." has left group with ref_id = " . $ref_id . "."));

        } catch (Groups\CancelationFailed $e) {
            $app->halt(400, 'Error: Could not perform action for user '.$authorizedUserId.". ".$e->getMessage(), -15);
        }
    });

});
