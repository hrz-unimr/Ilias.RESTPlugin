<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\groups_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('Services/Utilities/classes/class.ilUtil.php');
require_once('Modules/Group/classes/class.ilObjGroup.php');
require_once('Services/Object/classes/class.ilObjectFactory.php');
require_once('Services/Object/classes/class.ilObjectActivation.php');
require_once('Modules/LearningModule/classes/class.ilObjLearningModule.php');
require_once('Modules/LearningModule/classes/class.ilLMPageObject.php');

include_once('Modules/Group/classes/class.ilGroupMembershipMailNotification.php');


class GroupsRegistrationModel extends Libs\RESTModel
{
    protected $waiting_list;
    protected $participants;
    protected $container;

    /**
     * Subscribes a user to a course.
     *
     * @param user_id - the id of a user
     * @param ref_id - ref_id of a group object
     */
    public function joinGroup($user_id, $ref_id)
    {
        $this->user_id = $user_id;
        $this->ref_id = $ref_id;
        $this->obj_id = Libs\RESTilias::getObjId($ref_id);
        $this->container = \ilObjectFactory::getInstanceByRefId($this->ref_id, false);

        Libs\RESTilias::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read(); // Throws Exception on failure, catch in route!
        Libs\RESTilias::initAccessHandling();

        $this->initParticipants();
        $this->initWaitingList();
        if ($this->checkSubscribeConditions() == true) {
            $this->add();
        }

        return true;
    }

    /**
     * Unsubscribes (or removes) a user from a course
     *
     * @param user_id - the id of a user
     * @param ref_id - ref_id of a course object
     */
    public function leaveGroup($user_id, $ref_id)
    {
        $this->container = \ilObjectFactory::getInstanceByRefId($ref_id, false);
        Libs\RESTilias::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        $this->container->leaveGroup();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /* Find methods regarding the subscription of groups below */

    /**
     * see original methods at class.ilCourseRegistrationGUI.php
     */
    protected function checkSubscribeConditions()
    {
        global $ilUser;
        if($this->waiting_list->isOnList($ilUser->getId())) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Init group participants
     *
     * @access protected
     */
    protected function initParticipants()
    {
        include_once('Modules/Group/classes/class.ilGroupParticipants.php');
        $this->participants = \ilGroupParticipants::_getInstanceByObjId($this->container->getId());//$this->obj_id);
    }


    /**
     * @see ilRegistrationGUI::initWaitingList()
     * @access protected
     */
    protected function initWaitingList()
    {
        include_once('Modules/Group/classes/class.ilGroupWaitingList.php');
        $this->waiting_list = new \ilGroupWaitingList($this->container->getId()); //$this->obj_id);
    }

    /**
     * add user
     *
     * @access protected
     * @param
     * @return
     */
    protected function add()
    {
        global $ilUser,$tree, $rbacreview, $lng, $ilCtrl;

        // set aggreement accepted
        $this->setAccepted(true);

        include_once('Modules/Group/classes/class.ilGroupWaitingList.php');
        $free = max(0,$this->container->getMaxMembers() - $this->participants->getCountMembers());
        $waiting_list = new \ilGroupWaitingList($this->container->getId());
        if(
            $this->container->isMembershipLimited() and
            $this->container->isWaitingListEnabled() and
            (!$free or $waiting_list->getCountUsers()))
        {
            $waiting_list->addToList($ilUser->getId());
            $info = sprintf($this->lng->txt('grp_added_to_list'),
                $this->container->getTitle(),
                $waiting_list->getPosition($ilUser->getId()));

            $this->participants->sendNotification(
                ilGroupMembershipMailNotification::TYPE_WAITING_LIST_MEMBER,
                $ilUser->getId()
            );
            ilUtil::sendSuccess($info,true);
            $ilCtrl->setParameterByClass("ilrepositorygui", "ref_id",
                $tree->getParentId($this->container->getRefId()));
            $ilCtrl->redirectByClass("ilrepositorygui", "");
        }


        switch($this->container->getRegistrationType())
        {
            case GRP_REGISTRATION_REQUEST:

                $this->participants->addSubscriber($ilUser->getId());
                $this->participants->updateSubscriptionTime($ilUser->getId(),time());
                $this->participants->updateSubject($ilUser->getId(),ilUtil::stripSlashes($_POST['subject']));

                $this->participants->sendNotification(
                    ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION_REQUEST,
                    $ilUser->getId()
                );

                ilUtil::sendSuccess($this->lng->txt("application_completed"),true);
                $ilCtrl->setParameterByClass("ilrepositorygui", "ref_id",
                    $tree->getParentId($this->container->getRefId()));
                $ilCtrl->redirectByClass("ilrepositorygui", "");
                break;

            default:

                $this->participants->add($ilUser->getId(),IL_GRP_MEMBER);
                $this->participants->sendNotification(
                    \ilGroupMembershipMailNotification::TYPE_NOTIFICATION_REGISTRATION,
                    $ilUser->getId()
                );
                $this->participants->sendNotification(
                    \ilGroupMembershipMailNotification::TYPE_SUBSCRIBE_MEMBER,
                    $ilUser->getId()
                );

                include_once('Modules/Forum/classes/class.ilForumNotification.php');
                \ilForumNotification::checkForumsExistsInsert($this->container->getRefId(), $ilUser->getId());

                break;
        }
    }

    /**
     * (Cloned from Membership/classes/class.ilRegistrationGUI.php)
     * Set Agreement accepted
     *
     * @access private
     * @param bool
     */
    protected function setAccepted($a_status)
    {
        global $ilUser;

        include_once('Services/Membership/classes/class.ilMemberAgreement.php');
        $this->agreement = new \ilMemberAgreement($ilUser->getId(),$this->container->getId());
        $this->agreement->setAccepted($a_status);
        $this->agreement->setAcceptanceTime(time());
        $this->agreement->save();
    }

}
