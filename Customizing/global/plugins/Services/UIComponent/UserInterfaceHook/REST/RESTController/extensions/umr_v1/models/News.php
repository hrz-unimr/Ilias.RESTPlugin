<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\umr_v1;


// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/**
 * News-stream model
 */
class News extends Libs\RESTModel {

    /**
     * Returns any type of news items for a user.
     * For ilias internal news the user
     * @param $accessToken
     * @return array
     */
    public static function getAllNews($accessToken)
    {
        $result = array();
        // Extract user name
        $userId       = $accessToken->getUserId();

        $result["cag"] = self::getCaGNewsForUser($userId);
        return $result;
    }

    /**
     * Returns all course and group (CaG) related news for a user.
     * Note: only a subset of news properties are chosen.
     * @param $user_id
     * @return array
     */
    public static function getCaGNewsForUser($user_id)
    {
        Libs\RESTLib::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        Libs\RESTLib::initAccessHandling();

        include_once("./Services/News/classes/class.ilNewsItem.php");
        $per = \ilNewsItem::_lookupUserPDPeriod($ilUser->getId());

        $nitem = new \ilNewsItem();
        $news_items = $nitem->_getNewsItemsOfUser($ilUser->getId(), false, true, $per);

        // filter
        $cag_news_fields = array("id","title","content","content_is_lang_var", "content_text_is_lang_var","content_long","creation_date","update_date","ref_id","user_id");
        $filtered_news_items = array();

        $nNewsLimit = 100;
        $cnt = 0;
        foreach ($news_items as $news_id => $news_array) {
            //self::getApp()->log->debug('news items key value : '.$key);
            $item = array();
            foreach ($news_array as $key => $value) {
                if (in_array($key, $cag_news_fields) == true) {
                    $item[$key] = $value;
                }
            }
            $filtered_news_items[$news_id] = $item;
            $cnt++;

            if ($cnt >= $nNewsLimit) {
                self::getApp()->log->debug('nNews limit exceeded. cnt = '.$cnt);
                break;
            }
        }
        return $filtered_news_items;
    }

}