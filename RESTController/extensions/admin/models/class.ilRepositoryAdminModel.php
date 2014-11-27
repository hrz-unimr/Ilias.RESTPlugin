<?php

class ilRepositoryAdminModel
{
    public function getChildrenOfRoot()
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();

        $tree = new ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChilds(ROOT_FOLDER_ID);
        return $childs;
    }

    public function getChildren($ref_id)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();

        $tree = new ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChilds($ref_id);
        return $childs;
    }

    public function getCategoryListOfRootTree()
    {
        $tree = new ilTree(ROOT_FOLDER_ID);

        $root = $tree->getNodeData(1);
        $childs = $tree->getSubTree($root, false, array('cat')); // 'crs','grp'
        //getSubTree($a_node,$a_with_data = true, $a_type = "")
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
       // echo "Running getRekNode (".$ref_id.",".$parent_ref_id.") \n";
        // Step: get node data
        $obj_id = ilRestLib::refid_to_objid($ref_id);
        $node_data = ilRestLib::getObjectData($obj_id, array('create_date','description','title','type'));
        $node_data['ref_id'] = "$ref_id";

        // Step: get children (crs, cat)
        $tree = new ilTree(ROOT_FOLDER_ID);
        $childs = $tree->getChildsByTypeFilter($ref_id, $a_types);
        $a_children_ref_ids = array();
        foreach ($childs as $item) {
            $a_children_ref_ids[] = $item['ref_id'];
        }
        // Step: add children ref ids to node data
        $node_data['children'] = $a_children_ref_ids;
        $node_data['parent'] = "$parent_ref_id";

        // Step: for each children ref_id call this function
        $childresults = array();
        if ($ct_level < $max_level) {
            foreach ($a_children_ref_ids as $item_ref_id) {
                $childresult = $this->getRekNode($item_ref_id, $ref_id, $a_types, $ct_level + 1, $max_level); // result: array('id1'=>{child1}, 'id1sub1'=>{child11}, ...);
                $childresults = $childresults + $childresult; // "+" in contrast to array_merge preserves numerical keys
            }
        }
        // Step: return merge this node data with result arrays from child calls
        $result = array("$ref_id" => $node_data) + $childresults;
        return $result;
    }


    public function createNewCategoryAsUser($parent_ref_id, $title, $desc)
    {
        ilRestLib::initSettings(); // (SYSTEM_ROLE_ID in initSettings needed if user = root)
        ilRestLib::initDefaultRestGlobals();
        ilRestLib::initGlobal("ilUser", "ilObjUser", "./Services/User/classes/class.ilObjUser.php");
        global    $ilUser;
        $ilUser->setId(6);
        $ilUser->read();
        ilRestLib::initAccessHandling();

        include_once("Modules/Category/classes/class.ilObjCategory.php");
        $newObj = new ilObjCategory();
        $newObj->setType('cat');
        $newObj->setTitle($title);
        $newObj->setDescription($desc);
        $newObj->create(true); // true for upload
        $newObj->createReference();
        $newObj->putInTree($parent_ref_id);
        $newObj->setPermissions($parent_ref_id);

        return $newObj->getRefId() ? $newObj->getRefId() : "0";
    }

}
?>