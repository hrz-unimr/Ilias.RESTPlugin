<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


class RepositoryAdminModel extends Libs\RESTModel
{
    /**
     * Reads top 1 read event which occurred on the object.
     *
     * @param $obj_id int the object id.
     * @return timestamp
     */
    protected static function getLatestReadEventTimestamp($obj_id) {
        global $ilDB;

        $sql = Libs\RESTDatabase::safeSQL('SELECT last_access FROM read_event WHERE obj_id = %d ORDER BY last_access DESC LIMIT 1', $obj_id);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        return $row['last_access'];
    }

    
    /**
     * Provides object properties as stored in table object_data.
     *
     * @param $obj_id
     * @param $fields array of strings; to query all fields please specify 'array('*')'
     * @return mixed
     */
    protected static function getObjectData($obj_id, $fields) {
        global $ilDB;

        $sql = Libs\RESTDatabase::safeSQL('SELECT '. implode(', ', $fields) .' FROM object_data WHERE object_data.obj_id = %d', $obj_id);
        $query = $ilDB->query($sql);
        $row = $ilDB->fetchAssoc($query);

        return $row;
    }


    /**
     * Reads top-k read events which occurred on the object.
     *
     * Tries to deliver a list with max -k items
     * @param $obj_id int the object id.
     * @return timestamp
     */
    protected static function getTopKReadEventTimestamp($obj_id, $k) {
        global $ilDB;

        $sql = Libs\RESTDatabase::safeSQL('SELECT last_access FROM read_event WHERE obj_id = %d ORDER BY last_access DESC LIMIT %d', $obj_id, $k);
        $query = $ilDB->query($sql);
        $list = array();
        $cnt = 0;
        while ($row = $ilDB->fetchAssoc($query)){
            $list[] = $row['last_access'];
            $cnt = $cnt + 1;
            if ($cnt == $k) break;
        }

        return $list;
    }

    public function getChildrenOfRoot()
    {

        $tree = new \ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChilds(ROOT_FOLDER_ID);
        return $childs;
    }

    public function getChildren($ref_id)
    {

        $tree = new \ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChilds($ref_id);
        return $childs;
    }

    public function getCategoryListOfRootTree()
    {
        $tree = new \ilTree(ROOT_FOLDER_ID);

        $root = $tree->getNodeData(1);
        $childs = $tree->getSubTree($root, false, array('cat')); // 'crs','grp'
        //getSubTree($a_node,$a_with_data = true, $a_type = '')
        return $childs;
    }

    /**
     * Returns a list representation of a repository subtree. The node of the subtree must be specified by a ref_id.
     * Wrapper for getRekNode.
     *
     * @param $ref_id - the reference id of the ilias repository object
     */
    public function getSubTree($ref_id)
    {
        return $this->getRekNode($ref_id, 0, array('cat','crs'), 0, 1000);
    }

    /**
     * Returns a list representation of a repository subtree. The node of the subtree must be specified by a ref_id.
     * Wrapper for getRekNode.
     *
     * @param $ref_id - the reference id of the ilias repository object
     */
    public function getSubTreeDepth($ref_id, $depth)
    {
        return $this->getRekNode($ref_id, 0, array('cat','crs'), 0, $depth);
    }
    /**
     * This method returns a list of repository items which belongs to the subtree of the item specified by its $ref_id.
     *
     * Each item in the result list has the following descriptions:
     *  - create_date
     *  - description
     *  - title
     *  - type
     *  - ref_id
     * The list of descriptions is furthermore extended to have the following additional field:
     * - children_ref_ids
     * - parent_ref_id
     *
     * @param $ref_id - the reference id of the ilias repository object
     * @param $parent_ref_id - the parent ref_id
     * @param $a_types - array of strings that filter the types of objects that should be queried
     * @param $ct_level - current depth level
     * @param $max_level - maximal level of depth to descend within the subtree
     */
    public function getRekNode($ref_id, $parent_ref_id, $a_types, $ct_level, $max_level)
    {
       // echo 'Running getRekNode ('.$ref_id.','.$parent_ref_id.') \n';
        // Step: get node data
        $obj_id = Libs\RESTilias::getObjId($ref_id);
        $node_data = self::getObjectData($obj_id, array('create_date','description','title','type'));
        $node_data['ref_id'] = $ref_id;

        // Step: get children (crs, cat)
        $tree = new \ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChildsByTypeFilter($ref_id, $a_types);
        $a_children_ref_ids = array();
        foreach ($childs as $item) {
            $a_children_ref_ids[] = $item['ref_id'];
        }
        // Step: add children ref ids to node data
        $node_data['children'] = $a_children_ref_ids;
        $node_data['parent'] = $parent_ref_id;

        // Step: for each children ref_id call this function
        $childresults = array();
        if ($ct_level < $max_level) {
            foreach ($a_children_ref_ids as $item_ref_id) {
                $childresult = $this->getRekNode($item_ref_id, $ref_id, $a_types, $ct_level + 1, $max_level); // result: array('id1'=>{child1}, 'id1sub1'=>{child11}, ...);
                $childresults = $childresults + $childresult; // '+' in contrast to array_merge preserves numerical keys
            }
        }
        // Step: return merge this node data with result arrays from child calls
        $result = array($ref_id => $node_data) + $childresults;
        return $result;
    }


    /**
     * Returns a list representation of a repository subtree. The node of the subtree must be specified by a ref_id.
     * Only those items will be queried that are no older than $last_k_month.
     *
     * @param $ref_id - number, the reference id of the ilias repository object
     * @param $last_k_month -  number
     */
    public function getSubTreeWithinTimespan($ref_id, $last_k_month)
    {
        $span_in_sec = 60*60*24*30*$last_k_month;
        return $this->getRekNodeTimespan($ref_id, 0, array('cat','crs'), 0, 1000, $span_in_sec);
    }

    /**
     * Returns a list representation of a repository subtree. The node of the subtree must be specified by a ref_id.
     * Only those items will be queried that are no older than $last_k_month.
     * Furthermore only a subtree of depth $maxDepth will be queried.
     *
     * @param $ref_id - number, the reference id of the ilias repository object
     * @param $last_k_month -  number
     * @param $maxDepth - number
     */
    public function getSubTreeWithinTimespanDepth($ref_id, $last_k_month, $maxDepth)
    {
        $span_in_sec = 60*60*24*30*$last_k_month;
        return $this->getRekNodeTimespan($ref_id, 0, array('cat','crs'), 0, $maxDepth, $span_in_sec);
    }

    /**
     * This method returns a list of repository items which belongs to the subtree of the item specified by its $ref_id.
     * In contrast to getRekNode this method filters items that have not been used within a certain time span.
     *
     * Each item in the result list has the following descriptions:
     *  - create_date
     *  - description
     *  - title
     *  - type
     *  - ref_id
     * The list of descriptions is furthermore extended to have the following additional field:
     * - children_ref_ids
     * - parent_ref_id
     *
     * @param $ref_id - the reference id of the ilias repository object
     * @param $parent_ref_id - the parent ref_id
     * @param $a_types - array of strings that filter the types of objects that should be queried
     * @param $ct_level - current depth level
     * @param $max_level - maximal level of depth to descend within the subtree
     * @param $max_timeinterval - items will be filtered which have not been accessed (read) within the last $max_timeinterval seconds
     */
    public function getRekNodeTimespan($ref_id, $parent_ref_id, $a_types, $ct_level, $max_level, $max_timeinterval)
    {
        // echo 'Running getRekNode ('.$ref_id.','.$parent_ref_id.') \n';
        // Step: get node data
        $obj_id = Libs\RESTilias::getObjId($ref_id); //Libs\RESTLib::getObjIdFromRef($ref_id);
        $node_data = self::getObjectData($obj_id, array('create_date','description','title','type'));
        $node_data['ref_id'] = '$ref_id';

        // Step: get children (crs, cat)
        $tree = new \ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChildsByTypeFilter($ref_id, $a_types);
        $a_children_ref_ids = array();
        //$a_timestamps = array();
        foreach ($childs as $item) {
            // Check if the current item has been read within the last $max_timeinterval
            $ct_obj_id = Libs\RESTilias::getObjId($item['ref_id']);
            $ct_last_read = self::getLatestReadEventTimestamp($ct_obj_id);
            if (time() - $max_timeinterval < $ct_last_read) {
               // echo 'within! last read event: '.date('Y-m-d H-i-s', $ct_last_read).'\n';
                //$a_timestamps [] = date('Y-m-d H-i-s', $ct_last_read);
                $a_children_ref_ids[] = $item['ref_id'];
            } else {
               // echo 'NOT within! last read event: '.date('Y-m-d H-i-s', $ct_last_read).'\n';
            }
        }
        // Step: add children ref ids to node data
        $node_data['children'] = $a_children_ref_ids;
        $node_data['parent'] = $parent_ref_id;
        //$node_data['children_ts'] = $a_timestamps;

        // Step: for each children ref_id call this function
        $childresults = array();
        if ($ct_level < $max_level) {
            foreach ($a_children_ref_ids as $item_ref_id) {
                $childresult = $this->getRekNodeTimespan($item_ref_id, $ref_id, $a_types, $ct_level + 1, $max_level, $max_timeinterval); // result: array('id1'=>{child1}, 'id1sub1'=>{child11}, ...);
                $childresults = $childresults + $childresult; // '+' in contrast to array_merge preserves numerical keys
            }
        }
        // Step: return merge this node data with result arrays from child calls
        $result = array($ref_id => $node_data) + $childresults;
        return $result;
    }



    /**
     * The purpose of this function is to provide data for decision making.
     * In particular it is necessary to decide which kind of containers should be displayed on the mobile phone.
     * The difficulty consists of deciding what is an 'old' or 'not used' item.
     */
    public function getRepositoryReadEvents($ref_id)
    {
         return $this->getRepositoryReadEventsHelperRec($ref_id, 0, array('cat','crs'), 0, 1000, 10);
    }


    protected function getRepositoryReadEventsHelperRec($ref_id, $parent_ref_id, $a_types, $ct_level, $max_level, $k)
    {
        // echo 'Running getRekNode ('.$ref_id.','.$parent_ref_id.') \n';
        // Step: get node data
        $obj_id = Libs\RESTilias::getObjId($ref_id);
        $node_data = array();
        //$node_data = self::getObjectData($obj_id, array('create_date','description','title','type'));
        $obj_id = Libs\RESTilias::getObjId($ref_id);
        $node_data['obj_id'] = $obj_id;
        $a_timestamps = self::getTopKReadEventTimestamp($obj_id, $k);
        $node_data['timestamps'] = $a_timestamps;

        // Step: get children (crs, cat)
        $tree = new \ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChildsByTypeFilter($ref_id, $a_types);
        $a_children_ref_ids = array();
        //$a_timestamps = array();
        foreach ($childs as $item) {

            $a_children_ref_ids[] = $item['ref_id'];

        }
        // Step: add children ref ids to node data
       // $node_data['children'] = $a_children_ref_ids;
       // $node_data['parent'] = '$parent_ref_id';
        //$node_data['children_ts'] = $a_timestamps;

        // Step: for each children ref_id call this function
        $childresults = array();
        if ($ct_level < $max_level) {
            foreach ($a_children_ref_ids as $item_ref_id) {
                $childresult = $this->getRepositoryReadEventsHelperRec($item_ref_id, $ref_id, $a_types, $ct_level + 1, $max_level, $k); // result: array('id1'=>{child1}, 'id1sub1'=>{child11}, ...);
                $childresults = $childresults + $childresult; // '+' in contrast to array_merge preserves numerical keys
            }
        }
        // Step: return merge this node data with result arrays from child calls
        $result = array($ref_id => $node_data) + $childresults;
        return $result;
    }


    // ------------------------------------------------------------------------------------------------------------------

    /**
     * Creates a new category specified by title and description.
     * @param $parent_ref_id - the parent ref id must be specified.
     *
     */
    public function createNewCategoryAsUser($parent_ref_id, $title, $desc)
    {
        Libs\RESTilias::loadIlUser();

        global    $ilUser;
        $ilUser->setId(6);
        $ilUser->read();
        Libs\RESTilias::initAccessHandling();

        include_once('Modules/Category/classes/class.ilObjCategory.php');
        $newObj = new \ilObjCategory();
        $newObj->setType('cat');
        $newObj->setTitle($title);
        $newObj->setDescription($desc);
        $newObj->create(true); // true for upload
        $newObj->createReference();
        $newObj->putInTree($parent_ref_id);
        $newObj->setPermissions($parent_ref_id);

        return $newObj->getRefId() ? $newObj->getRefId() : 0;
    }

}
?>
