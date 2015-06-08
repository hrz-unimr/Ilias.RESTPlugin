<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\mobile_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;

/**
 * Class MobileFeedbackModel
 * This model class introduces operations for storing and retrieval of feedback
 * related information.
 *
 * @package RESTController\extensions\mobile_v1
 */
class MobileFeedbackModel
{
    /**
     * Creates a new feedback item.
     * @param $title
     * @param $description
     * @return bool
     */
    function createFeedbackItem($title, $description)
    {
        // TODO
        global $ilDB;
        $a_columns = array(
            "title" => array("text", $title),
            "description" => array("text", $description));
        $ilDB->insert("mobile_feedback", $a_columns);
        return $ilDB->getLastInsertId();
    }

    /**
     * Returns all feedback items.
     * @return array
     */
    function getFeedbackItems()
    {
        global $ilDB;
        $query = "SELECT * FROM dev_items";
        $set = $ilDB->query($query);
        while($row = $ilDB->fetchAssoc($set))
        {
            $res[] = $row;
        }
        return $res;
    }

    /**
     * Returns a feedback item specified by $item_id.
     * @param $item_id
     */
    function getFeedbackItem($item_id)
    {
        // TODO
    }


    /**
     * Updates a feedback entry
     * @param $id
     * @param $fieldname
     * @param $newval
     */
    public function updateFeedbackItem($id, $fieldname, $newval)
    {
        // TODO
       /* global $ilDB;
        $sql = "UPDATE dev_items SET $fieldname = \"$newval\" WHERE id = $id";
        $numAffRows = $ilDB->manipulate($sql);
        return $numAffRows;
       */
    }

    /**
     * Deletes a feedback entry.
     * @param $id
     * @return int
     */
    public function deleteFeedbackItem($id)
    {
        global $ilDB;
        $sql = "DELETE FROM mobile_feedback WHERE id =".$ilDB->quote($id, "integer");
        $numAffRows = $ilDB->manipulate($sql);
        return $numAffRows;
    }

    /**
     * This method creates a new mobile-feedback database table.
     * (Should only be invoked by an administrator).
     */
    function createMobileFeedbackDatabaseTable()
    {
        // TODO
        /*CREATE TABLE IF NOT EXISTS `mobile_feedback` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(1000) NOT NULL,
          `description` varchar(1024) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB;
        */
    }

}
