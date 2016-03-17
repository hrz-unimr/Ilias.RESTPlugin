<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
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
class MobileFeedbackModel extends Libs\RESTModel {

    const TABLE = 'mobile_feedback';

    /**
     * Creates a new feedback item.
     * @param $user_id
     * @param $message
     * @param $environment
     * @return mixed
     */
    function createFeedbackItem($user_id, $message, $environment)
    {
        $a_columns = array(
            "userid" => array("text", $user_id),
            "message" => array("text", $message),
            "environment" => array("text", $environment));
        self::getDB()->insert(self::TABLE, $a_columns);
        return self::getDB()->getLastInsertId();
    }

    /**
     * Returns all feedback items.
     * @return array
     */
    function getFeedbackItems()
    {
        $sql = Libs\RESTDatabase::safeSQL("SELECT * FROM ".self::TABLE);
        $set = self::getDB()->query($sql);

        if ($set == null) {
            return array();
        }
        while($row = self::getDB()->fetchAssoc($set))
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
        $sql = Libs\RESTDatabase::safeSQL('SELECT * FROM '.self::TABLE.' WHERE id = %d', $item_id);
        $set = self::getDB()->query($sql);

        if ($set != null && $row = self::getDB()->fetchAssoc($set)) {
            return $row;
        }
    }

    /**
     * Updates a feedback entry
     * @param $id
     * @param $fieldname
     * @param $newval
     */
    public function updateFeedbackItem($id, $fieldname, $newval)
    {
        $sql = Libs\RESTDatabase::safeSQL('UPDATE '.self::TABLE.' SET %s = %s WHERE id = %d', $fieldname, $newval, $id);
        $numAffRows = self::getDB()->manipulate($sql);
        return $numAffRows;
    }

    /**
     * Deletes a feedback entry.
     * @param $id
     * @return int
     */
    public function deleteFeedbackItem($id)
    {
        $sql = Libs\RESTDatabase::safeSQL('DELETE FROM '.self::TABLE.' WHERE id = %d', $id);
        $numAffRows = self::getDB()->manipulate($sql);
        return $numAffRows;
    }

    /**
     * This method creates a new mobile-feedback database table.
     * (Should be invoked by an administrator only).
     */
    function createMobileFeedbackDatabaseTable()
    {
        if (self::getDB()->tableExists(self::TABLE) == false) { // TODO better throw special exception here

            $fields = array(
                'id' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'notnull' => true
                ),
                'userid' => array(
                    'type' => 'text',
                    'length' => 512,
                    'fixed' => false,
                    'notnull' => false
                ),
                'message' => array(
                    'type' => 'text',
                    'length' => 1024,
                    'fixed' => false,
                    'notnull' => false
                ),
                'environment' => array(
                    'type' => 'text',
                    'length' => 1024,
                    'notnull' => true
                )
            );
            self::getDB()->createTable(self::TABLE, $fields, false);
            self::getDB()->addPrimaryKey(self::TABLE, array("id"));
            self::getDB()->manipulate('ALTER TABLE ' . self::TABLE . ' CHANGE id id INT NOT NULL AUTO_INCREMENT');
            return true;
        } else {
            return false;
        }

    }

}
