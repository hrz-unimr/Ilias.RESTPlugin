<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\news_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;

require_once('Services/User/classes/class.ilObjUser.php');

/**
 * Class NewsModel
 * This model class provides operations regarding the ilias internal news system.
 *
 * @package RESTController\extensions\news_v1
 */
class NewsModel extends Libs\RESTModel {

    /**
     * Retrieves the ilias personal desktop news for a user.
     * Note: code heavily inspired by Services/News/classes/ilPDNewsGUI.php
     * @param $user_id
     */
    public function getPDNewsForUser($user_id)
    {
        Libs\RESTilias::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        $ref_ids = array();
        $obj_ids = array();
        $pd_items = $ilUser->getDesktopItems();
        foreach($pd_items as $item)
        {
            $ref_ids[] = $item["ref_id"];
            $obj_ids[] = $item["obj_id"];
        }

        $sel_ref_id = ($_GET["news_ref_id"] > 0)
            ? $_GET["news_ref_id"]
            : $ilUser->getPref("news_sel_ref_id");

        include_once('Services/News/classes/class.ilNewsItem.php');
        $per = ($_SESSION["news_pd_news_per"] != "")
            ? $_SESSION["news_pd_news_per"]
            : \ilNewsItem::_lookupUserPDPeriod($ilUser->getId());
        $news_obj_ids = \ilNewsItem::filterObjIdsPerNews($obj_ids, $per);

        // related objects (contexts) of news
        //$contexts[0] = $lng->txt("news_all_items");
        $contexts[0] = "news_all_items";

        $conts = array();
        $sel_has_news = false;
        foreach ($ref_ids as $ref_id)
        {
            $obj_id = \ilObject::_lookupObjId($ref_id);
            $title = \ilObject::_lookupTitle($obj_id);

            $conts[$ref_id] = $title;
            if ($sel_ref_id == $ref_id)
            {
                $sel_has_news = true;
            }
        }

        $cnt = array();
        $nitem = new \ilNewsItem();
        $news_items = $nitem->_getNewsItemsOfUser($ilUser->getId(), false,
            true, $per, $cnt);

        // reset selected news ref id, if no news are given for id
        if (!$sel_has_news)
        {
            $sel_ref_id = "";
        }
        asort($conts);
        foreach($conts as $ref_id => $title)
        {
            $contexts[$ref_id] = $title." (".(int) $cnt[$ref_id].")";
        }


        if ($sel_ref_id > 0)
        {
            $obj_id = \ilObject::_lookupObjId($sel_ref_id);
            $obj_type = \ilObject::_lookupType($obj_id);
            $nitem->setContextObjId($obj_id);
            $nitem->setContextObjType($obj_type);
            $news_items = $nitem->getNewsForRefId($sel_ref_id, false,
                false, $per, true);
        }

        return $news_items;
    }
}