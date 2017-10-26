<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\courses_v2;

// Include required ILIAS classes
require_once('Modules/Course/classes/class.ilObjCourse.php');
require_once('Services/Repository/classes/class.ilRepUtil.php');


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs            as Libs;
use \RESTController\libs\Exceptions as LibExceptions;


/**
 * <DocIt!!!>
 */
class Admin extends Libs\RESTModel {
  // Error messages and ids
  const MSG_TIME_CONVERT           = 'Failed to convert given date \'{{data}}\' in field {{field}} from {{format_str}} to ilDate/ilDateTime object.';
  const ID_TIME_CONVERT            = 'RESTController\\extensions\\courses_v2\\Admin::ID_TIME_CONVERT';
  const MSG_RBAC_CREATE_CRS_DENIED = 'Permission to create course under parent-object denied by RBAC-System.' ;
  const ID_RBAC_CREATE_CRS_DENIED  = 'RESTController\\extensions\\courses_v2\\Admin::ID_RBAC_CREATE_CRS_DENIED';


  /**
   * Function: HasCourseValue($userData, $field)
   *  Checks if the given user-data has a value for the given field.
   *
   * Paramters:
   *  userData <USER-DATA> - User data
   *  field <String> - Field inside user data
   *
   * Return:
   *  <Bool> - User-data contains a value for the given field
   */
  protected static function HasCourseValue($userData, $field) {
    return (is_array($userData) && array_key_exists($field, $userData));
  }


  /**
   * <DocIt!!!>
   */
  protected static function TransformTime($data, $field, $format = IL_CAL_DATETIME) {
    $format_convert = array();
    $format_convert[IL_CAL_DATETIME]    = 'IL_CAL_DATETIME';
    $format_convert[IL_CAL_DATE]        = 'IL_CAL_DATE';
    $format_convert[IL_CAL_UNIX]        = 'IL_CAL_UNIX';
    $format_convert[IL_CAL_ISO_8601]    = 'IL_CAL_ISO_8601';

    try {
      if (array_key_exists($field, $data)) {
        if ($format === IL_CAL_ISO_8601) {
          // Fix broken ILIAS date conversion when given string is NOT ISO 8601
          $converted = \DateTime::createFromFormat(\DateTime::ISO8601, $data[$field]);
          if ($converted)
            return new \ilDateTime($converted->getTimeStamp(), IL_CAL_UNIX);
          else
            throw new LibExceptions\Parameter(
              self::MSG_TIME_CONVERT,
              self::ID_TIME_CONVERT,
              array(
                'data'       => $data[$field],
                'field'      => $field,
                'format'     => $format,
                'format_str' => $format_convert[$format],
              )
            );
        }
        elseif ($format === IL_CAL_DATE)
          return new \ilDate($data[$field], IL_CAL_DATE);
        else
          return new \ilDateTime($data[$field], $format);
      }
    }
    catch (\Exception $e) {
      throw new LibExceptions\Parameter(
        self::MSG_TIME_CONVERT,
        self::ID_TIME_CONVERT,
        array(
          'data'       => $data[$field],
          'field'      => $field,
          'format'     => $format,
          'format_str' => $format_convert[$format],
        )
      );
    }
  }


  /**
   * <DocIt!!!>
   */
  protected static function TransformInput($crsData) {
    //
    if (self::HasCourseValue($crsData, 'id'))
      $crsData['id']        = intval($crsData['id']);
    if (self::HasCourseValue($crsData, 'ref_id'))
      $crsData['ref_id']    = intval($crsData['ref_id']);
    if (self::HasCourseValue($crsData, 'owner'))
      $crsData['owner']     = intval($crsData['owner']);
    if (self::HasCourseValue($crsData, 'view_mode'))
      $crsData['view_mode'] = intval($crsData['view_mode']);
    if (self::HasCourseValue($crsData, 'course_start'))
      $crsData['course_start'] = self::TransformTime($crsData, 'course_start', IL_CAL_DATE);
    if (self::HasCourseValue($crsData, 'course_end'))
      $crsData['course_end']   = self::TransformTime($crsData, 'course_end', IL_CAL_DATE);
    if (self::HasCourseValue($crsData, 'cancel_end'))
      $crsData['cancel_end']   = self::TransformTime($crsData, 'cancel_end', IL_CAL_DATE);

    //
    if (self::HasCourseValue($crsData, 'activation')) {
      $activation = $crsData['activation'];

      if (self::HasCourseValue($activation, 'start'))
        $activation['start'] =  self::TransformTime($activation, 'start', IL_CAL_ISO_8601)->get(IL_CAL_UNIX);
      if (self::HasCourseValue($activation, 'end'))
        $activation['end']   = self::TransformTime($activation, 'end', IL_CAL_ISO_8601)->get(IL_CAL_UNIX);

      $crsData['activation'] = $activation;
    }

    //
    if (self::HasCourseValue($crsData, 'subscription')) {
      $subscription = $crsData['subscription'];

      if (self::HasCourseValue($subscription, 'limitation_type'))
        $subscription['limitation_type'] = intval($subscription['limitation_type']);
      if (self::HasCourseValue($subscription, 'type'))
        $subscription['type']            = intval($subscription['type']);
      if (self::HasCourseValue($subscription, 'start'))
        $subscription['start']           = self::TransformTime($subscription, 'start', IL_CAL_ISO_8601)->get(IL_CAL_UNIX);
      if (self::HasCourseValue($subscription, 'end'))
        $subscription['end']             = self::TransformTime($subscription, 'end', IL_CAL_ISO_8601)->get(IL_CAL_UNIX);

      $crsData['subscription'] = $subscription;
    }

    //
    if (self::HasCourseValue($crsData, 'location')) {
      $location = $crsData['location'];

      if (self::HasCourseValue($location, 'latitude'))
        $location['latitude']  = floatval($location['latitude']);
      if (self::HasCourseValue($location, 'longitude'))
        $location['longitude'] = floatval($location['longitude']);
      if (self::HasCourseValue($location, 'zoom'))
        $location['zoom']      = intval($location['zoom']);

      $crsData['location'] = $location;
    }

    return $crsData;
  }


  /**
   * <DocIt!!!>
   */
  protected static function ValidateInput($crsData) {
    // TODO: Implement validation
    //  Both course_start and end need to be given if one is given
  }


  /**
   * <DocIt!!!>
   */
  protected static function TransformOutput($crsData) {
    //
    if (self::HasCourseValue($crsData, 'id'))
      $crsData['id']              = intval($crsData['id']);
    if (self::HasCourseValue($crsData, 'ref_id'))
      $crsData['ref_id']          = intval($crsData['ref_id']);
    if (self::HasCourseValue($crsData, 'owner'))
      $crsData['owner']           = intval($crsData['owner']);
    if (self::HasCourseValue($crsData, 'created'))
      $crsData['created']         = (new \ilDateTime($crsData['created'], IL_CAL_DATETIME))->get(IL_CAL_ISO_8601);
    if (self::HasCourseValue($crsData, 'updated'))
      $crsData['updated']         = (new \ilDateTime($crsData['updated'], IL_CAL_DATETIME))->get(IL_CAL_ISO_8601);
    if (self::HasCourseValue($crsData, 'view_mode'))
      $crsData['view_mode']       = intval($crsData['view_mode']);

    if (self::HasCourseValue($crsData, 'order')) {
      $order = $crsData['order'];

      if (self::HasCourseValue($order, 'type'))
        $order['type'] = intval($order);
      if (self::HasCourseValue($order, 'direction'))
        $order['direction'] = intval($order);
      if (self::HasCourseValue($order, 'new_position'))
        $order['new_position'] = intval($order);
      if (self::HasCourseValue($order, 'new_order'))
        $order['new_order'] = intval($order);

      $crsData['order'] = $order;
    }

    if (self::HasCourseValue($crsData, 'order_type'))
      $crsData['order_type']      = intval($crsData['order_type']);
    if (self::HasCourseValue($crsData, 'order_type'))
      $crsData['order_direction'] = intval($crsData['order_direction']);

    //
    if (self::HasCourseValue($crsData, 'member_limit')) {
      $member_limit = $crsData['member_limit'];

      if (self::HasCourseValue($member_limit, 'max'))
        $member_limit['max'] = intval($member_limit['max']);
      if (self::HasCourseValue($member_limit, 'min'))
        $member_limit['min'] = intval($member_limit['min']);

      $crsData['member_limit'] = $member_limit;
    }

    //
    if (self::HasCourseValue($crsData, 'activation')) {
      $activation = $crsData['activation'];

      if (self::HasCourseValue($activation, 'start'))
        $activation['start'] = (new \ilDateTime($activation['start'], IL_CAL_UNIX))->get(IL_CAL_ISO_8601);
      if (self::HasCourseValue($crsData['activation'], 'end'))
        $activation['end']   = (new \ilDateTime($activation['end'], IL_CAL_UNIX))->get(IL_CAL_ISO_8601);

      $crsData['activation'] = $activation;
    }

    //
    if (self::HasCourseValue($crsData, 'subscription')) {
      $subscription = $crsData['subscription'];

      if (self::HasCourseValue($subscription, 'start'))
        $subscription['start']      = (new \ilDateTime($subscription['start'], IL_CAL_UNIX))->get(IL_CAL_ISO_8601);
      if (self::HasCourseValue($subscription, 'end'))
        $subscription['end']        = (new \ilDateTime($subscription['end'], IL_CAL_UNIX))->get(IL_CAL_ISO_8601);
      if (self::HasCourseValue($subscription, 'type'))
        $subscription['type']       = intval($subscription['type']);
      if (self::HasCourseValue($subscription, 'limitation'))
        $subscription['limitation'] = intval($subscription['limitation']);

      $crsData['subscription'] = $subscription;
    }

    //
    if (self::HasCourseValue($crsData, 'location')) {
      $location = $crsData['location'];

      if (self::HasCourseValue($location, 'latitude'))
        $location['latitude']  = floatval($location['latitude']);
      if (self::HasCourseValue($location, 'longitude'))
        $location['longitude'] = floatval($location['longitude']);
      if (self::HasCourseValue($location, 'zoom'))
        $location['zoom']      = intval($location['zoom']);

      $crsData['location'] = $location;
    }

    //
    if (self::HasCourseValue($crsData, 'services')) {
      $services = $crsData['services'];

      if (self::HasCourseValue($services, 'members'))
        $services['members']  = boolval($services['members']);
      if (self::HasCourseValue($services, 'mail_type'))
        $services['mail_type']  = intval($services['mail_type']);
      if (self::HasCourseValue($services, 'notification'))
        $services['notification']  = boolval($services['notification']);
      if (self::HasCourseValue($services, 'abo'))
        $services['abo']  = boolval($services['abo']);
      if (self::HasCourseValue($services, 'metadata'))
        $services['metadata']  = boolval($services['metadata']);
      if (self::HasCourseValue($services, 'ratings'))
        $services['ratings']  = boolval($services['ratings']);
      if (self::HasCourseValue($services, 'calendar'))
        $services['calendar']  = boolval($services['calendar']);
      if (self::HasCourseValue($services, 'tags'))
        $services['tags']  = boolval($services['tags']);

      $crsData['services'] = $services;
    }

    return $crsData;
  }



  /**
   * <DocIt!!!>
   */
  public static function GetCourseData($refId) {
    include_once('Services/Container/classes/class.ilContainerSortingSettings.php');
    include_once('Services/Calendar/classes/class.ilObjCalendarSettings.php');
    include_once('Services/Container/classes/class.ilContainer.php');
    global $rbacsystem, $ilSetting;

    // Check that object (of type course or course-reference) exists
    if (!\ilObject::_exists($refId, true, 'crs') && !\ilObject::_exists($refId, true, 'crsr'))
      throw new LibExceptions\ilObject(
        Libs\RESTilias::MSG_NO_OBJECT_BY_REF,
        Libs\RESTilias::ID_NO_OBJECT_BY_REF,
        array(
          'ref_id' => $refId
        )
      );

    // Check for required permissions on object
    // We check for write here, since this method also return admin information
    if (!$rbacsystem->checkAccess('read', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_READ_DENIED,
        Libs\RESTilias::ID_RBAC_READ_DENIED,
        array(
          'object' => 'course-object',
          'ref_id' => $refId
        )
      );
    $writable = $rbacsystem->checkAccess('write', $refId);

    // Load course-object to fetch data
    $obj  = new \ilObjCourse($refId);
    $crsData = array();

    // ilObject settings
    $crsData['id']          = $obj->getId();
    $crsData['ref_id']      = $obj->getRefId();
    $crsData['title']       = $obj->getTitle();
    $crsData['description'] = $obj->getLongDescription();
    $crsData['owner']       = $obj->getOwner();
    $crsData['created']     = $obj->getCreateDate();
    $crsData['updated']     = $obj->getLastUpdateDate();
    if ($writable)
      $crsData['in_trash']   = $obj->_isInTrash($obj->getRefId());
    if ($writable)
      $crsData['pass_check'] = $obj->getStatusDetermination();

    //
    $settings = new \ilContainerSortingSettings($obj->getId());
    $crsData['order'] = array();
    $crsData['order']['type']         = $obj->getOrderType();
    $crsData['order']['direction']    = $settings->getSortDirection();
    if ($obj->getOrderType() == \ilContainer::SORT_MANUAL){
      $crsData['order']['new_position'] = $settings->getSortNewItemsPosition();
      $crsData['order']['new_order']    = $settings->getSortNewItemsOrder();
    }

    //
    $items                       = $obj->getSubItems();
    if (is_array($items) && array_key_exists('_all', $items)) {
      $crsData['children']       = array_map(function($item) {
        return intval($item['ref_id']);
      }, $items['_all']);

      $crsData['sub_items']      = array_reduce($items['_all'], function ($result, $item) {
        $result[$item['type']]   = $result[$item['type']] ?: array();
        $result[$item['type']][] = intval($item['ref_id']);

        return $result;
      }, array());
    }

    // ilObjCourse methods
    $crsData['view_mode']      = $obj->getViewMode();
    if ($obj->getCourseStart())
      $crsData['course_start'] = $obj->getCourseStart()->get(IL_CAL_DATE);
    if ($obj->getCourseEnd())
      $crsData['course_end']   = $obj->getCourseEnd()->get(IL_CAL_DATE);
    if ($obj->getCancellationEnd())
      $crsData['cancel_end']   = $obj->getCancellationEnd()->get(IL_CAL_DATE);
    $crsData['information']    = $obj->getImportantInformation();
    $crsData['syllabus']       = $obj->getSyllabus();
    $crsData['offline']        = $obj->getOfflineStatus();
    $crsData['active']         = $obj->isActivated();
    if ($writable && $obj->isRegistrationAccessCodeEnabled())
      $crsData['registration_accesscode'] = $obj->getRegistrationAccessCode();

    //
    $crsData['contact'] = array();
    $crsData['contact']['name']           = $obj->getContactName();
    $crsData['contact']['consultation']   = $obj->getContactConsultation();
    $crsData['contact']['phone']          = $obj->getContactPhone();
    $crsData['contact']['email']          = $obj->getContactEmail();
    $crsData['contact']['responsibility'] = $obj->getContactResponsibility();

    //
    if ($writable) {
      if ($obj->getActivationType() === 2) {
        $crsData['activation'] = array();
        $crsData['activation']['start']      = $obj->getActivationStart();
        $crsData['activation']['end']        = $obj->getActivationEnd();
        $crsData['activation']['visibility'] = $obj->getActivationVisibility();
      }
      else
        $crsData['activation'] = false;
    }

    //
    if ($writable) {
      if ($obj->isSubscriptionMembershipLimited()) {
        $crsData['member_limit'] = array();
        $crsData['member_limit']['min']         = $obj->getSubscriptionMinMembers();
        $crsData['member_limit']['max']         = $obj->getSubscriptionMaxMembers();
        $crsData['member_limit']['autofill']    = $obj->hasWaitingListAutoFill();
        $crsData['member_limit']['waitinglist'] = $obj->enabledWaitingList();
      }
      else
        $crsData['member_limit'] = false;
    }

    //
    if ($writable) {
      if (intval($obj->getSubscriptionLimitationType()) !== 0) {
        $crsData['subscription'] = array();
        $crsData['subscription']['limitation']  = $obj->getSubscriptionLimitationType();
        $crsData['subscription']['type']        = $obj->getSubscriptionType();
        if (intval($obj->getSubscriptionLimitationType()) === 2) {
          $crsData['subscription']['start']     = $obj->getSubscriptionStart();
          $crsData['subscription']['end']       = $obj->getSubscriptionEnd();
        }
        if (intval($obj->getSubscriptionType()) === 4)
          $crsData['subscription']['password']  = $obj->getSubscriptionPassword();
      }
    }

    //
    if ($obj->getEnableCourseMap()) {
      $crsData['location'] = array();
      $crsData['location']['latitude']  = $obj->getLatitude();
      $crsData['location']['longitude'] = $obj->getLongitude();
      $crsData['location']['zoom']      = $obj->getLocationZoom();
    }
    else
      $crsData['location'] = false;

    //
    $crsData['services'] = array();
    $crsData['services']['members']      = $obj->getShowMembers();
    $crsData['services']['mail_type']    = $obj->getMailToMembersType();
    $crsData['services']['notification'] = $obj->getAutoNotification();
    $crsData['services']['abo']          = $obj->getAboStatus();
    $crsData['services']['metadata']     = \ilContainer::_lookupContainerSetting($obj->getId(), 'cont_custom_md', false);
    $crsData['services']['ratings']      = \ilContainer::_lookupContainerSetting($obj->getId(), 'cont_auto_rate_new_obj', false);
    if (\ilCalendarSettings::_getInstance()->isEnabled())
      $crsData['services']['calendar']   = \ilCalendarSettings::lookupCalendarActivated($obj->getId());
    if ($ilSetting->get('block_activated_news'))
      $crsData['services']['news']       = \ilContainer::_lookupContainerSetting($obj->getId(), 'cont_show_news', true);
    $tags = new \ilSetting('tags');
    if ($tags->get('enable', false))
      $crsData['services']['tags']       = \ilContainer::_lookupContainerSetting($obj->getId(), 'cont_tag_cloud', false);

    // Cleanup and return data
    return self::TransformOutput($crsData);
  }


  /**
   * <DocIt!!!>
   */
  protected static function EditCourseObject($obj, $crsData) {
    include_once('Services/Container/classes/class.ilContainerSortingSettings.php');
    include_once('Services/Calendar/classes/class.ilCalendarSettings.php');
    include_once('Services/Container/classes/class.ilContainer.php');
    global $ilUser, $rbacsystem, $ilSetting;

    // Check for required permissions on object
    if (!$rbacsystem->checkAccess('write', $obj->getRefId()))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_WRITE_DENIED,
        Libs\RESTilias::ID_RBAC_WRITE_DENIED,
        array(
          'object' => 'course-object'
        )
      );

    // Update generic parameters
    if (self::HasCourseValue($crsData, 'title'))
      $obj->setTitle($crsData['title']);
    if (self::HasCourseValue($crsData, 'description'))
      $obj->setDescription($crsData['description']);
    if (self::HasCourseValue($crsData, 'view_mode'))
      $obj->setViewMode($crsData['view_mode']);
    if (self::HasCourseValue($crsData, 'information'))
      $obj->setImportantInformation($crsData['information']);
    if (self::HasCourseValue($crsData, 'syllabus'))
      $obj->setSyllabus($crsData['syllabus']);
    if (self::HasCourseValue($crsData, 'offline'))
      $obj->setOfflineStatus($crsData['offline']);

    //
    if (self::HasCourseValue($crsData, 'order')) {
      $order    = $crsData['order'];

      if (is_array($order)) {
        $settings = new \ilContainerSortingSettings($obj->getId());

        if (self::HasCourseValue($order, 'type'))
          $obj->setOrderType($order['type']);
        if (self::HasCourseValue($order, 'direction'))
          $obj->setSortDirection($order['direction']);
        if (self::HasCourseValue($order, 'new_position'))
          $obj->setSortNewItemsPosition($order['new_position']);
        if (self::HasCourseValue($order, 'new_order'))
          $obj->setSortNewItemsOrder($order['new_order']);

        $settings->update();
      }
      else
        $obj->setOrderType($order);
    }

    //
    if (self::HasCourseValue($crsData, 'course_start')) {
      if ($crsData['course_start'])
        $obj->setCourseStart($crsData['course_start']);
      else
        $obj->setCourseStart(null);
    }
    if (self::HasCourseValue($crsData, 'course_end')) {
      if ($crsData['course_end'])
        $obj->setCourseEnd($crsData['course_end']);
      else
        $obj->setCourseEnd(null);
    }
    if (self::HasCourseValue($crsData, 'cancel_end')) {
      if ($crsData['cancel_end'])
        $obj->setCancellationEnd($crsData['cancel_end']);
      else
        $obj->setCancellationEnd(null);
    }

    //
    if (self::HasCourseValue($crsData, 'owner')) {
      $userId = $ilUser->getId();
      if ($userId === $obj->getOwner() || Libs\RESTilias::isAdmin($userId))
        $obj->setOwner($crsData['owner']);
    }

    //
    if (self::HasCourseValue($crsData, 'registration_accesscode')) {
      if ($crsData['registration_accesscode']) {
        $obj->enableRegistrationAccessCode(true);
        $obj->setRegistrationAccessCode($crsData['registration_accesscode']);
      }
      else {
        $obj->enableRegistrationAccessCode(false);
        $obj->setRegistrationAccessCode('');
      }
    }

    //
    $syncMembersStatus = false;
    if (self::HasCourseValue($crsData, 'pass_check')) {
      if ($crsData['pass_check'] === \ilObjCourse::STATUS_DETERMINATION_LP && $obj->getStatusDetermination() !== \ilObjCourse::STATUS_DETERMINATION_LP)
        $syncMembersStatus = true;
      $obj->setStatusDetermination($crsData['pass_check']);
    }


    // Update contact parameters
    if (self::HasCourseValue($crsData, 'contact')) {
      $contact = $crsData['contact'];

      if (self::HasCourseValue($contact, 'name'))
        $obj->setContactName($contact['name']);
      if (self::HasCourseValue($contact, 'consultation'))
        $obj->setContactConsultation($contact['consultation']);
      if (self::HasCourseValue($contact, 'phone'))
        $obj->setContactPhone($contact['phone']);
      if (self::HasCourseValue($contact, 'email'))
        $obj->setContactEmail($contact['email']);
      if (self::HasCourseValue($contact, 'responsibility'))
        $obj->setContactResponsibility($contact['responsibility']);
    }

    // Update activation parameters
    if (self::HasCourseValue($crsData, 'activation')) {
      $activation = $crsData['activation'];

      if (is_array($activation)) {
        $obj->setActivationType(2);
        if (self::HasCourseValue($activation, 'start'))
          $obj->setActivationStart($activation['start']);
        if (self::HasCourseValue($activation, 'end'))
          $obj->setActivationEnd($activation['end']);
        if (self::HasCourseValue($activation, 'visibility'))
          $obj->setActivationVisibility($activation['visibility']);
      }
      elseif ($activation === false)
        $obj->setActivationType(1);
    }

    //
    if (self::HasCourseValue($crsData, 'member_limit')) {
      $member_limit = $crsData['member_limit'];

      if  (is_array($member_limit)) {
        $obj->enableSubscriptionMembershipLimitation(1);
        if (self::HasCourseValue($member_limit, 'min'))
           $obj->setSubscriptionMinMembers($member_limit['min']);
        if (self::HasCourseValue($member_limit, 'max'))
          $obj->setSubscriptionMaxMembers($member_limit['max']);
        if (self::HasCourseValue($member_limit, 'autofill'))
          $obj->setWaitingListAutoFill($member_limit['autofill']);
        if (self::HasCourseValue($member_limit, 'waitinglist'))
          $obj->enableWaitingList($member_limit['waitinglist']);
      }
      elseif ($member_limit === false)
        $obj->enableSubscriptionMembershipLimitation(0);
    }

    // Update subscription parameters
    if (self::HasCourseValue($crsData, 'subscription')) {
      $subscription = $crsData['subscription'];

      if (is_array($subscription)) {
        if (self::HasCourseValue($subscription, 'limitation'))
          $obj->setSubscriptionLimitationType($subscription['limitation']);
        if (self::HasCourseValue($subscription, 'type'))
          $obj->setSubscriptionType($subscription['type']);
        if (self::HasCourseValue($subscription, 'start'))
          $obj->setSubscriptionStart($subscription['start']);
        if (self::HasCourseValue($subscription, 'end'))
          $obj->setSubscriptionEnd($subscription['end']);
        if (self::HasCourseValue($subscription, 'password'))
          $obj->setSubscriptionPassword($subscription['password']);
      }
      elseif ($subscription === false)
        $obj->setSubscriptionLimitationType(0);
    }

    // Update location parameters
    if (self::HasCourseValue($crsData, 'location')) {
      $location = $crsData['location'];

      if (is_array($location)) {
        $obj->setEnableCourseMap(1);
        if (self::HasCourseValue($location, 'latitude'))
          $obj->setLatitude($location['latitude']);
        if (self::HasCourseValue($location, 'longitude'))
          $obj->setLongitude($location['longitude']);
        if (self::HasCourseValue($location, 'zoom'))
          $obj->setLocationZoom($location['zoom']);
      }
      elseif ($location === false)
        $obj->setEnableCourseMap(0);
    }

    //
    if (self::HasCourseValue($crsData, 'services')) {
      $services = $crsData['services'];

      if (self::HasCourseValue($services, 'members'))
        $obj->setShowMembers($services['members']);
      if (self::HasCourseValue($services, 'mail_type'))
        $obj->setMailToMembersType($services['mail_type']);
      if (self::HasCourseValue($services, 'notification'))
        $obj->setAutoNotification($services['notification']);
      if (self::HasCourseValue($services, 'abo'))
        $obj->setAboStatus($services['abo']);
      if (self::HasCourseValue($services, 'ratings'))
        \ilContainer::_writeContainerSetting($obj->getId(), 'cont_auto_rate_new_obj', $services['ratings']);
      if (self::HasCourseValue($services, 'metadata'))
        \ilContainer::_writeContainerSetting($obj->getId(), 'cont_custom_md', $services['metadata']);
      if (self::HasCourseValue($services, 'calendar'))
        if (\ilCalendarSettings::_getInstance()->isEnabled() || !$services['calendar'])
          \ilContainer::_writeContainerSetting($obj->getId(), 'cont_show_calendar', $services['calendar']);
      if (self::HasCourseValue($services, 'news'))
        if ($ilSetting->get('block_activated_news') || !$services['news'])
          \ilContainer::_writeContainerSetting($obj->getId(), 'cont_show_news', $services['news']);
      if (self::HasCourseValue($services, 'tags')) {
        $tags = new \ilSetting('tags');
        if ($tags->get('enable', false) || !$services['tags'])
          \ilContainer::_writeContainerSetting($obj->getId(), 'cont_tag_cloud', $services['tags']);
      }
    }

    // Force changes to DB
    $obj->update();

    //
    if ($syncMembersStatus)
      $obj->syncMembersStatusWithLP();

    // Return crs-object on success
    return $obj;
  }


  /**
   * <DocIt!!!>
   */
  public static function CreateCourse($parentRefId, $crsData) {
    global $rbacsystem, $ilAccess, $ilUser;

    // Check for required permissions on object
    if (!$ilAccess->checkAccess('create_crs', '', $parentRefId))
      throw new LibExceptions\RBAC(
        self::MSG_RBAC_CREATE_CRS_DENIED,
        self::ID_RBAC_CREATE_CRS_DENIED
      );

    // Convert parameters from sensible input values to what-ever ILIAS deems worthy
    self::ValidateInput($crsData);
    $crsData = self::TransformInput($crsData);

    // Create new course object
    $obj = new \ilObjCourse();
    $obj->setTitle($crsData['title']);
    if ($crsData['owner'])
      $obj->setOwner($crsData['owner']);
    else
      $obj->setOwner($ilUser->getId());

    // Create course object and store in DB
    $obj->create();
    $obj->createReference();
    $obj->putInTree($parentRefId);
    $obj->setPermissions($parentRefId);

    // Edit newly generated course object
    return self::EditCourseObject($obj, $crsData);
  }


  /**
   * <DocIt!!!>
   */
  public static function EditCourse($refId, $crsData) {
    // Check that object (of type course or course-reference) exists
    if (!\ilObject::_exists($refId, true, 'crs') && !\ilObject::_exists($refId, true, 'crsr'))
      throw new LibExceptions\ilObject(
        Libs\RESTilias::MSG_NO_OBJECT_BY_REF,
        Libs\RESTilias::ID_NO_OBJECT_BY_REF,
        array(
          'ref_id' => $refId
        )
      );

    // Convert parameters from sensible input values to what-ever ILIAS deems worthy
    self::ValidateInput($crsData);
    $crsData = self::TransformInput($crsData);

    // Fetch and edit object
    $obj = new \ilObjCourse($refId);
    return self::EditCourseObject($obj, $crsData);
  }


  /**
   * <DocIt!!!>
   */
  public static function DeleteCourse($refId, $fromSystem = false) {
    global $tree, $rbacsystem, $ilSetting;

    // Check that object (of type course or course-reference) exists
    if (!\ilObject::_exists($refId, true, 'crs') && !\ilObject::_exists($refId, true, 'crsr'))
      throw new LibExceptions\ilObject(
        Libs\RESTilias::MSG_NO_OBJECT_BY_REF,
        Libs\RESTilias::ID_NO_OBJECT_BY_REF,
        array(
          'ref_id' => $refId
        )
      );

    // Check for required permissions on object
    if (!$rbacsystem->checkAccess('delete', $refId))
      throw new LibExceptions\RBAC(
        Libs\RESTilias::MSG_RBAC_DELETE_DENIED,
        Libs\RESTilias::ID_RBAC_DELETE_DENIED,
        array(
          'object' => 'course-object'
        )
      );

    // Delete object from tree
    if (!$tree->isDeleted($refId))
      \ilRepUtil::deleteObjects($refId, $refId);

    // Remove from trash if trash is enabled, deleting is requested and user is admin
    if ($ilSetting->get('enable_trash') && $fromSystem)
      if (Libs\RESTilias::isAdmin($userId))
        \ilRepUtil::removeObjectsFromSystem(array($refId));

    // Success!
    return $refId;
  }
}
