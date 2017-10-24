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
  // If no limit is set on news count, use this value
  const DEFAULT_LIMIT   = 100;


  /**
   * Returns any type of news items for a user.
   * For ilias internal news the user
   * @param $accessToken
   * @return array
   */
  public static function getAllNews($accessToken, $settings = null) {
    // Extract user name
    $userId = $accessToken->getUserId();

    // Fetch news froma all sources
    $result = array();
    $result['ilias'] = self::getILIASNews($userId, $settings);

    // Return collected news
    return $result;
  }


  /**
   * Returns all course and group (CaG) related news for a user.
   * Note: only a subset of news properties are chosen.
   * @param $userId
   * @return array
   */
  public static function getILIASNews($userId, $settings = null) {
    // Load ILIAS user (AccessHandling not strictly required!)
    $ilUser = Libs\RESTilias::loadIlUser();
    Libs\RESTilias::initAccessHandling();

    // Use custom filter or apply personal-desktop settings
    include_once('Services/News/classes/class.ilNewsItem.php');
    if ($settings && $settings['period'])
      $period = $settings['period'];
    else
      $period = \ilNewsItem::_lookupUserPDPeriod($userId);

    // Fecth all news items, with given settings
    $newsItems  = \ilNewsItem::_getNewsItemsOfUser($userId, false, true, $period);
    usort($newsItems, function($a, $b) {
      return $a['update_date'] > $b['update_date'];
    });

    // Limit the maximum number of news and allow to access more news by
    // Either start at last known news-id or at a fixed offset
    if ($settings && $settings['lastid']) {
      // Search index of news with given lastId, to be use as offset value
      $offset = 0; $index  = 0;
      foreach ($newsItems as $newsItem) {
        if ($newsItem['id'] == $settings['lastid']) {
          $offset = $index + 1;
          break;
        }
        ++$index;
      }

    }
    // Use offset value given as setting-parameter instead
    else
      $offset = ($settings && $settings['offset']) ? $settings['offset'] : 0;
    $limit  = ($settings && $settings['limit'])  ? abs($settings['limit']) : self::DEFAULT_LIMIT;

    // Applay limit and offset
    $newsItems = array_slice($newsItems, $offset, $limit);

    // Extract values for all news-items
    $result = array();
    foreach ($newsItems as $newsItem) {
      // Fetch the newsId
      $id = intval($newsItem['id']);

      // Generate actual news object (filter 'null'-values)
      $result[$id] = array_filter(
        array(
          'id'            => $id,
          'ref_id'        => intval($newsItem['ref_id']),
          'sub_id'        => ($newsItem['context_sub_obj_id'] != 0) ? intval($newsItem['context_sub_obj_id']) : null,
          'ref_type'      => $newsItem['context_obj_type'],
          'user_id'       => intval($newsItem['user_id']),
          'title'         => $newsItem['title'],
          'is_title'      => $newsItem['content_is_lang_var'] != 1,
          'creation_date' => substr_replace($newsItem['creation_date'], 'T', 10, 1),
          'update_date'   => substr_replace($newsItem['update_date'], 'T', 10, 1),
          'content'       => $newsItem['content'],
          'is_content'    => ($newsItem['content']) ? $newsItem['content_text_is_lang_var'] != 1 : null,
          'content_long'  => $newsItem['content_long']
        ),
        function($value) { return !is_null($value); }
      );
    }

    // Return all ILIAS news
    return $result;
  }
}
