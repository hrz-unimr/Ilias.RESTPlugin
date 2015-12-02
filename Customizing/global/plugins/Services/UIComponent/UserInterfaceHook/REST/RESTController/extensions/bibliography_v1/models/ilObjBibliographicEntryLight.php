<?php
namespace RESTController\extensions\bibliography_v1;
require_once('./Modules/Bibliographic/classes/class.ilBibliographicEntry.php');
/**
 * Helper: Lightweight / Template-less variant of ilBibliographicEntry
 */
class ilObjBibliographicEntryLight extends \ilBibliographicEntry
{

    /**
     * @param      $file_type
     * @param null $entry_id
     *
     * @return ilBibliographicEntry
     */
    public static function getInstanceLight($file_type, $entry_id = NULL) {
        if (!$entry_id) {
            return new static($file_type, $entry_id);
        }

        if (!isset(self::$instances[$entry_id])) {
            self::$instances[$entry_id] = new static($file_type, $entry_id);
        }

        return self::$instances[$entry_id];
    }

    /**
     * @param      $file_type
     * @param null $entry_id
     */
    function __construct($file_type, $entry_id = NULL) {
        $this->file_type = $file_type;
        if ($entry_id) {
            $this->setEntryId($entry_id);
            $this->doReadLight();
        }
    }

    /**
    * Read data from database tables il_bibl_entry and il_bibl_attribute
    */
    function doReadLight() {
        global $ilDB;
        //table il_bibl_entry
        $set = $ilDB->query("SELECT * FROM il_bibl_entry " . " WHERE id = " . $ilDB->quote($this->getEntryId(), "integer"));
        while ($rec = $ilDB->fetchAssoc($set)) {
            $this->setType($rec['type']);
        }
        $this->setAttributes($this->loadAttributes());
        //$this->setOverwiew(); // we do not want the template overhead
    }
}