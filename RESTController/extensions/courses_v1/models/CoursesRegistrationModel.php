<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('Services/Utilities/classes/class.ilUtil.php');
require_once('Modules/Course/classes/class.ilObjCourse.php');
require_once('Services/Object/classes/class.ilObjectFactory.php');
require_once('Services/Object/classes/class.ilObjectActivation.php');
require_once('Modules/LearningModule/classes/class.ilObjLearningModule.php');
require_once('Modules/LearningModule/classes/class.ilLMPageObject.php');
//require_once('Modules/Course/classes/class.ilCourseConstants.php');


class CoursesRegistrationModel extends Libs\RESTModel
{
    protected $waiting_list;
    protected $participants;
    protected $container;

    /**
     * Subscribes a user to a course.
     *
     * @param user_id - the id of a user
     * @param ref_id - ref_id of a course object
     */
    public function joinCourse($user_id, $ref_id)
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
    public function leaveCourse($user_id, $ref_id)
    {
        $this->container = \ilObjectFactory::getInstanceByRefId($ref_id, false);
        Libs\RESTilias::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();
        if ($this->checkUnsubscribeConditions() == false)
            throw new \Exception('User cannot leave the course, because he is the last course admin.');
        else
            $this->performUnsubscribeObject();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /* Find methods regarding the subscription of courses below */

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
     * Init course participants
     *
     * @access protected
     */
    protected function initParticipants()
    {
        include_once('Modules/Course/classes/class.ilCourseParticipants.php');
        $this->participants = \ilCourseParticipants::_getInstanceByObjId($this->container->getId());//$this->obj_id);
    }


    /**
     * @see ilRegistrationGUI::initWaitingList()
     * @access protected
     */
    protected function initWaitingList()
    {
        include_once('Modules/Course/classes/class.ilCourseWaitingList.php');
        $this->waiting_list = new \ilCourseWaitingList($this->container->getId()); //$this->obj_id);
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
        global $ilUser,$tree, $ilCtrl;

        // set aggreement accepted
        $this->setAccepted(true);

        include_once('Modules/Course/classes/class.ilCourseWaitingList.php');
        $free = max(0,$this->container->getSubscriptionMaxMembers() - $this->participants->getCountMembers());
        $waiting_list = new \ilCourseWaitingList($this->container->getId());
        if($this->container->isSubscriptionMembershipLimited() and $this->container->enabledWaitingList() and (!$free or $waiting_list->getCountUsers()))
        {
            $waiting_list->addToList($ilUser->getId());
            $info = sprintf($this->lng->txt('crs_added_to_list'),
                $waiting_list->getPosition($ilUser->getId()));
                \ilUtil::sendSuccess($info,true);

            $this->participants->sendNotification($this->participants->NOTIFY_SUBSCRIPTION_REQUEST,$ilUser->getId());
            $this->participants->sendNotification($this->participants->NOTIFY_WAITING_LIST,$ilUser->getId());
            $ilCtrl->setParameterByClass('ilrepositorygui', 'ref_id',
                $tree->getParentId($this->container->getRefId()));
            $ilCtrl->redirectByClass('ilrepositorygui', '');
        }

        switch($this->container->getSubscriptionType())
        {
            case IL_CRS_SUBSCRIPTION_CONFIRMATION:
                $this->participants->addSubscriber($ilUser->getId());
                $this->participants->updateSubscriptionTime($ilUser->getId(),time());
                $this->participants->updateSubject($ilUser->getId(), \ilUtil::stripSlashes($_POST['subject']));
                $this->participants->sendNotification($this->participants->NOTIFY_SUBSCRIPTION_REQUEST,$ilUser->getId());

                \ilUtil::sendSuccess($this->lng->txt('application_completed'),true);
                $ilCtrl->setParameterByClass('ilrepositorygui', 'ref_id',
                    $tree->getParentId($this->container->getRefId()));
                $ilCtrl->redirectByClass('ilrepositorygui', '');
                break;

            default:

                if($this->container->isSubscriptionMembershipLimited() && $this->container->getSubscriptionMaxMembers())
                {
                    $success = $GLOBALS['rbacadmin']->assignUserLimited(
                        \ilParticipants::getDefaultMemberRole($this->container->getRefId()),
                        $ilUser->getId(),
                        $this->container->getSubscriptionMaxMembers(),
                        array(\ilParticipants::getDefaultMemberRole($this->container->getRefId()))
                    );
                    if(!$success)
                    {
                        // The maximum number of participants has been exceeded.
                        //ilUtil::sendFailure($this->lng->txt('crs_subscription_failed_limit'));
                        return FALSE;
                    }
                }

                $this->participants->add($ilUser->getId(),IL_CRS_MEMBER);
                $this->participants->sendNotification($this->participants->NOTIFY_ADMINS,$ilUser->getId());
                $this->participants->sendNotification($this->participants->NOTIFY_REGISTERED,$ilUser->getId());

                include_once('Modules/Forum/classes/class.ilForumNotification.php');
                \ilForumNotification::checkForumsExistsInsert($this->container->getRefId(), $ilUser->getId());

                if($this->container->getType() == 'crs')
                {
                    $this->container->checkLPStatusSync($ilUser->getId());
                }

                // You have joined the course
                return TRUE;
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

        /*include_once('Modules/Course/classes/Export/class.ilCourseDefinedFieldDefinition.php');
        if(!$this->privacy->confirmationRequired($this->type) and !\ilCourseDefinedFieldDefinition::_hasFields($this->container->getId()))
        {
            return true;
        }
        */
        include_once('Services/Membership/classes/class.ilMemberAgreement.php');
        $this->agreement = new \ilMemberAgreement($ilUser->getId(),$this->container->getId());
        $this->agreement->setAccepted($a_status);
        $this->agreement->setAcceptanceTime(time());
        $this->agreement->save();
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /* Find methods regarding the unsubscription of courses below */

    /**
     * see original class.ilObjCourseGUI.php > leaveObject
     */
    protected function checkUnsubscribeConditions()
    {
        global $ilUser;
        //$this->checkPermission('leave');
        if($this->container->getMembersObject()->isLastAdmin($ilUser->getId()) == true) {
            //ilUtil::sendFailure($this->lng->txt('crs_min_one_admin')); // 'There has to be at least one administrator assigned to this course.'
            return false;
        }
        return true;
    }

    protected function performUnsubscribeObject()
    {
        global $ilUser, $ilCtrl;
        // CHECK ACCESS
       // $this->checkPermission('leave');
        $this->container->getMembersObject()->delete($ilUser->getId());
        $this->container->getMembersObject()->sendUnsubscribeNotificationToAdmins($ilUser->getId());
        $this->container->getMembersObject()->sendNotification($this->container->getMembersObject()->NOTIFY_UNSUBSCRIBE,$ilUser->getId());
        //ilUtil::sendSuccess($this->lng->txt('crs_unsubscribed_from_crs'),true); // 'You have been unsubscribed from this course'
    }

}
